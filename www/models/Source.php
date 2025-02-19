<?php

class Source extends Model {
    public $name;

    public function __construct() {
        /**
         *  Ouverture d'une connexion à la base de données
         */
        $this->getConnection('main');
    }

    /**
     *  Ajouter un nouveau repo source
     */
    public function new(string $name, string $urlType = null, string $url, string $existingGpgKey = null, string $gpgKeyURL = null, string $gpgKeyText = null) {

        $name = Common::validateData($name);

        /**
         *  On vérifie que le nom du repo source ne contient pas de caractères invalides
         */
        if (!Common::is_alphanumdash($name)) {
            throw new Exception('Le nom du repo source ne peut pas contenir de caractères spéciaux hormis le tiret - et l\'underscore _');
        }

        /**
         *  Formattage de l'URL passée
         */
        $url = trim($url); // Suppression des espaces si il y en a (ça ne devrait pas)
        $url = stripslashes($url); // Suppression des anti-slash

        /**
         *  Si l'URL contient des caractères non-autorisés ou si elle ne commence pas par http(s) alors elle est invalide
         */
        if (!Common::is_alphanumdash($url, array('=', ':', '/', '.', '?', '$', '&'))) {
            throw new Exception('L\'URL du repo source contient des caractères invalides');
        }
        if (!preg_match('#^https?://#', $url)) {
            throw new Exception('L\'URL du repo source doit commencer par <b>http(s)://</b>');
        }

        /**
         *  Sur Redhat/Centos, on crée un fichier dans /etc/yum.repos.d/repomanager/
         */
        if (OS_FAMILY == "Redhat") {
            if (file_exists(REPOMANAGER_YUM_DIR."/${name}.repo")) {
                throw new Exception("Un repo source <b>$name</b> existe déjà");
            }

            /**
             *  On récupère la clé GPG, il s'agit soit une clé existante, soit au format url, soit au format texte à importer. Si les deux sont renseignés on affiche une erreur (c'est l'un ou l'autre)
             */
            if (!empty($existingGpgKey) AND !empty($gpgKeyURL) AND !empty($gpgKeyText)) {
                throw new Exception('Vous ne pouvez pas renseigner plusieurs types de clé GPG à la fois');

            /**
             *  Cas où c'est une clé existante
             */
            } elseif (!empty($existingGpgKey)) { // On recupère le nom de la clé existante
                $existingGpgKey = Common::validateData($existingGpgKey);

                /**
                 *  Si la clé renseignée n'existe pas, on quitte
                 */
                if (!file_exists(RPM_GPG_DIR."/$existingGpgKey")) {
                    throw new Exception('La clé GPG renseignée n\'existe pas');
                }

            /**
             *  Cas où c'est une URL vers une clé GPG
             */
            } elseif (!empty($gpgKeyURL)) { // On recupère l'url de la clé gpg
                $gpgKeyURL = Common::validateData($gpgKeyURL);

                /**
                 *  Formattage de l'URL
                 */
                $gpgKeyURL = trim($gpgKeyURL); // Suppression des espaces si il y en a (ça ne devrait pas)
                $gpgKeyURL = stripslashes($gpgKeyURL); // Suppression des anti-slash

                /**
                 *  Si l'URL contient des caractères invalide alors on quitte 
                 */
                if (!Common::is_alphanumdash($gpgKeyURL, array(':', '/', '.'))) {
                    throw new Exception('L\'URL de la clé GPG contient des caractères invalides');
                }

                /**
                 *  Si l'URL ne commence pas par http(s) ou par file:// (pour désigner un fichier sur le serveur) alors elle est invalide
                 */
                if (!preg_match('#^https?://#', $gpgKeyURL) AND !preg_match('/^file:\/\/\//', $gpgKeyURL)) {
                    throw new Exception('L\'URL de la clé GPG est invalide');
                }

            /**
             *  Cas où on importe une clé au format texte ASCII
             */
            } elseif (!empty($gpgKeyText)) { // On récupère la clé gpg au format texte
                $gpgKeyText = Common::validateData($gpgKeyText);

                /**
                 *  Si le 'pavé' de texte ASCII contient des caractères invalide alors on quitte
                 *  Ici on autorise tous les caractères qu'on peut possiblement retrouver dans une clé GPG au format ASCII
                 */
                if (!Common::is_alphanum($gpgKeyText, array('-', '=', '+', '/', ' ', ':', '.', '(', ')', "\n", "\r"))) {
                    throw new Exception('La clé GPG au format ASCII contient des caractères invalides');
                }

                /**
                 *  Si le contenu qu'on tente d'importer est un fichier sur le disque alors on quitte
                 */
                if (file_exists($gpgKeyText)) {
                    throw new Exception('La clé GPG renseignée doit être au format texte ASCII');
                }

                /**
                 *  On importe la clé gpg au format texte dans le répertoire par défaut où rpm stocke ses clés gpg importées (et dans un sous-répertoire repomanager)
                 */
                $newGpgFile = "REPOMANAGER-RPM-GPG-KEY-${name}";
                if (file_exists(RPM_GPG_DIR."/${newGpgFile}")) {
                    throw new Exception("Un fichier GPG du même nom existe déjà dans le trousseau de repomanager");
                } else {
                    file_put_contents(RPM_GPG_DIR."/${newGpgFile}", $gpgKeyText); // ajout de la clé gpg à l'intérieur du fichier gpg
                }
            }

            /**
             *  Récupération du type d'URL
             */
            $urlType = Common::validateData($urlType);
            if ($urlType != 'baseurl' AND $urlType != 'mirrorlist' AND $urlType != 'metalink') {
                throw new Exception('Le type d\'URL renseigné est invalide');
            }

            /**
             *  On génère la conf qu'on va injecter dans le fichier de repo
             */
            $newRepoFileConf  = "[$name]".PHP_EOL;
            $newRepoFileConf .= 'enabled=1'.PHP_EOL;
            $newRepoFileConf .= "name=Repo source $name sur ".WWW_HOSTNAME.PHP_EOL;

            /**
             *  Forge l'url en fonction de son type (baseurl, mirrorlist...)
             */
            $newRepoFileConf .= "${urlType}=${url}".PHP_EOL;

            /**
             *  Si on a renseigné une clé GPG alors on active gpgcheck
             */
            if (!empty($existingGpgKey) OR !empty($gpgKeyURL) OR !empty($gpgKeyText)) {
                $newRepoFileConf .= "gpgcheck=1".PHP_EOL;
            }

            /**
             *  On indique le chemin vers la clé GPG existante si indiqué
             */
            if (!empty($existingGpgKey)) {
                $newRepoFileConf .= "gpgkey=file://".RPM_GPG_DIR."/${existingGpgKey}".PHP_EOL;
            }
            /**
             *  On indique l'url vers la clé GPG si indiqué
             */
            if (!empty($gpgKeyURL)) {
                $newRepoFileConf .= "gpgkey=${gpgKeyURL}".PHP_EOL;
            }
            /**
             *  On indique le chemin vers la clé GPG importée
             */
            if (!empty($gpgKeyText)) {
                $newRepoFileConf .= "gpgkey=file://".RPM_GPG_DIR."/${newGpgFile}".PHP_EOL;
            }

            /**
             *  Ecriture de la configuration dans le fichier de repo source
             */
            file_put_contents(REPOMANAGER_YUM_DIR."/${name}.repo", $newRepoFileConf.PHP_EOL);
        }


        /**
         *  Sur Debian, on ajoute l'URL en BDD
         */
        if (OS_FAMILY == "Debian") {
            /**
             *  On vérifie qu'un repo source du même nom n'existe pas déjà en BDD
             */
            try {
                $stmt = $this->db->prepare("SELECT Name FROM Sources WHERE Name=:name");
                $stmt->bindValue(':name', $name);
                $result = $stmt->execute();
            } catch(Exception $e) {
                Common::dbError($e);
            }

            /**
             *  Si le résultat n'est pas vide alors un repo existe déjà
             */
            if ($this->db->isempty($result) === false) {
                throw new Exception("Un repo source <b>$name</b> existe déja");
            }

            /**
             *  Si une clé GPG a été transmise alors on l'importe
             */
            if (!empty($gpgKeyText)) {
                $gpgKeyText = Common::validateData($gpgKeyText);
                $gpgKeyText = trim($gpgKeyText);

                /**
                 *  Si le 'pavé' de texte ASCII contient des caractères invalides alors on quitte 
                 *  Ici on autorise tous les caractères qu'on peut possiblement retrouver dans une clé GPG au format ASCII
                 */
                if (!Common::is_alphanum($gpgKeyText, array('-', '=', '+', '/', ' ', ':', '.', '(', ')', "\n", "\r"))) {
                    throw new Exception('La clé GPG au format ASCII contient des caractères invalides');
                }

                /**
                 *  Si le contenu qu'on tente d'importer est un fichier sur le disque alors on quitte
                 */
                if (file_exists($gpgKeyText)) {
                    throw new Exception('La clé GPG renseignée doit être au format texte ASCII');
                }

                /**
                 *  Création d'un fichier temporaire dans lequel on injecte la clé GPG à importer
                 */
                $gpgTempFile = TEMP_DIR."/repomanager_newgpgkey.tmp";
                file_put_contents($gpgTempFile, $gpgKeyText);

                /**
                 *  Import du fichier temporaire dans le trousseau de repomanager
                 */
                exec("gpg --no-default-keyring --keyring ".GPGHOME."/trustedkeys.gpg --import $gpgTempFile", $output, $result);

                /**
                 *  Suppression du fichier temporaire
                 */
                unlink($gpgTempFile);

                /**
                 *  Si erreur lors de l'import, on affiche un message d'erreur
                 */
                if ($result != 0) {
                    throw new Exception("Erreur lors de l'import de la clé GPG");                    
                }           
            }

            try {
                $stmt = $this->db->prepare("INSERT INTO sources ('Name', 'Url') VALUES (:name, :url)");
                $stmt->bindValue(':name', $name);
                $stmt->bindValue(':url', $url);
                $stmt->execute();
            } catch(Exception $e) {
                Common::dbError($e);
            }
        }
    }

    /**
     *  Supprimer un repo source
     */
    public function delete(string $name) {
        $name = Common::validateData($name);

        if (OS_FAMILY == "Redhat") {
            if (file_exists(REPOMANAGER_YUM_DIR."/${name}.repo")) {
                if (!unlink(REPOMANAGER_YUM_DIR."/${name}.repo")) {
                    throw new Exception("Erreur lors de la suppression du repo source <b>$name</b>");
                }
            }
        }
        if (OS_FAMILY == "Debian") {
            try {
                $stmt = $this->db->prepare("DELETE FROM sources WHERE Name = :name");
                $stmt->bindValue(':name', $name);
                $stmt->execute();
            } catch(Exception $e) {
                Common::dbError($e);
            }
        }
    }

    /**
     *  Renommer un repo source
     */
    public function rename(string $name, string $newName) {
        $name = Common::validateData($name);
        $newName = Common::validateData($newName);

        /**
         *  Si le nom actuel et le nouveau nom sont les mêmes, on ne fait rien
         */
        if ($name == $newName) {
            throw new Exception('Vous devez renseigner un nom différent de l\'actuel');
        }

        /**
         *  On vérifie que le nom ainsi que le nouveau nom ne contiennent pas de caractères invalides
         */
        if (Common::is_alphanumdash($name) === false) {
            throw new Exception('Erreur : le nom du repo source contient des caractères invalides');
        }
        if (Common::is_alphanumdash($newName) === false) {
            throw new Exception('Erreur : le nouveau nom du repo source contient des caractères invalides');
        }

        /**
         *  Sur Redhat, le renommage consiste à changer le nom du fichier de repo source ainsi que le nom du repo à l'intérieur de ce fichier
         */
        if (OS_FAMILY == "Redhat") {
            /**
             *  Si un fichier portant le même nom que $newName existe déjà alors on ne peut pas renommer le fichier
             */
            if (file_exists(REPOMANAGER_YUM_DIR."/${newName}.repo")) {
                throw new Exception("Erreur : un repo source du même nom <b>$newName<b> existe déjà");
            }

            /**
             *  Renommage
             */
            if (file_exists(REPOMANAGER_YUM_DIR."/${name}.repo")) {
                if (!rename(REPOMANAGER_YUM_DIR."/${name}.repo", REPOMANAGER_YUM_DIR."/${newName}.repo")) {
                    throw new Exception('Impossible de renommer le repo source');
                }
                $content = file_get_contents(REPOMANAGER_YUM_DIR."/${newName}.repo");
                $content = str_replace("[$name]", "[$newName]", $content);
                $content = str_replace("Repo source $name", "Repo source $newName", $content);
                file_put_contents(REPOMANAGER_YUM_DIR."/${newName}.repo", $content);
                unset($content);
            }
        }

        /**
         *  Sur Debian, les repos sources sont stockés en BDD
         */
        if (OS_FAMILY == "Debian") {
            /**
             *  On vérifie si un repo source du même nom existe déjà
             */
            try {
                $stmt = $this->db->prepare("SELECT Name FROM sources WHERE Name = :newname");
                $stmt->bindValue(':newname', $newName);
                $result = $stmt->execute();
            } catch(Exception $e) {
                Common::dbError($e);
            }
            if ($this->db->isempty($result) === false) {
                throw new Exception("Un repo source <b>$newName</b> existe déjà");
            }

            try {
                $stmt = $this->db->prepare("UPDATE sources SET Name = :newname WHERE Name = :name");
                $stmt->bindValue(':newname', $newName);
                $stmt->bindValue(':name', $name);
                $stmt->execute();
            } catch(Exception $e) {
                Common::dbError($e);
            }
        }
    }

    /**
     *  Modification de l'url du repo source (Debian uniquement)
     */
    public function editUrl(string $sourceName, string $url)
    {
        $sourceName = Common::validateData($sourceName);

        /**
         *  Formattage de l'URL passée
         */
        $url = trim($url); // Suppression des espaces si il y en a (ça ne devrait pas)
        $url = stripslashes($url); // Suppression des anti-slash
        $url = strtolower($url); // converti tout en minuscules

        /**
         *  On vérifie que l'url ne contient pas de caractères invalides
         */
        if (Common::is_alphanumdash($url, array(':', '/', '.', '?', '&')) === false) {
            throw new Exception("L'URL saisie contient des caractères invalides");
        }

        /**
         *  On vérifie que l'url commence par http(s)://
         */
        if (!preg_match('#^https?://#', $url)) {
            throw new Exception("L'URL saisie doit commencer par http(s)://");
        }

        try {
            $stmt = $this->db->prepare("UPDATE sources SET Url = :url WHERE Name = :name");
            $stmt->bindValue(':url', $url);
            $stmt->bindValue(':name', $sourceName);
            $stmt->execute();
        } catch(Exception $e) {
            Common::dbError($e);
        }
    }

    /**
     *  Modifier la configuration d'un repo source (Redhat uniquement)
     */
    public function configureSource(string $sourceName, array $options, string $comments = null) {
        $sourceName = Common::validateData($sourceName);
        $sourceFile = REPOMANAGER_YUM_DIR."/${sourceName}.repo"; // Le fichier dans lequel on va écrire

        /**
         *  On initialise le contenu du fichier en mettant le nom du repo source en crochet (standard des fichiers .repo)
         */
        $content = "[${sourceName}]".PHP_EOL;

        foreach ($options as $option) {
            $optionName = Common::validateData($option['name']);
            $optionValue = $option['value'];

            /**
             *  On vérifie que le nom de l'option est valide, càd qu'il ne contient pas de caractère spéciaux
             */
            if (Common::is_alphanumdash($optionName) === false) {
                throw new Exception("Le paramètre <b>$optionName</b> contient des caractère invalides");
            }

            if (empty($optionValue)) {
                /**
                 *  Si le nom du paramètre est 'gpgcheck' ou 'enabled' ou pkg_gpgcheck ou etc... alors il faut set une valeur de 0, sinon on set la valeur à ''
                 */
                if ($optionName == 'gpgcheck' OR
                    $optionName == 'enabled' OR
                    $optionName == 'pkg_gpgcheck' OR
                    $optionName == 'sslverify' OR
                    $optionName == 'repo_gpgcheck' OR
                    $optionName == 'countme')
                {
                    $optionValue = '0';
                }

                if ($optionName == 'autorefresh') {
                    $optionValue = 'no';
                }

            } elseif (!empty($optionValue)) {
                /**
                 *  Si le nom du paramètre est 'gpgcheck' ou 'enabled' ou pkg_gpgcheck ou etc... alors sa valeur ne peut être que '1' ou '0'
                 *  Si la valeur est non-vide et qu'elle vaut 'yes' alors on la set à '1' conformément à la syntaxe des fichiers .repo, sinon dans tous les autres cas on la set à '0' (fait plus haut)
                 */
                if ($optionName == 'gpgcheck' OR
                    $optionName == 'enabled' OR
                    $optionName == 'pkg_gpgcheck' OR
                    $optionName == 'sslverify' OR
                    $optionName == 'repo_gpgcheck' OR
                    $optionName == 'countme')
                {
                    $optionValue = '1';
                }

                /**
                 *  Dans le cas où le paramètre se nomme baseurl, mirrorlist ou metalink, on accepte + de caractères spéciaux car sa valeur est souvent une url pouvant comporter des slashs, des ? et des $
                 *  Note : ne pas autoriser les parenthèses pour éviter l'injection de code et la tentative d'utilisation de la fonction exec() par exemple. Si possible voir pour échapper le caractère $
                 */
                if ($optionName == 'baseurl' OR $optionName == 'mirrorlist' OR $optionName == 'metalink') {
                    $optionValue = trim($optionValue);          // Suppression des espaces si il y en a (ça ne devrait pas)
                    $optionValue = stripslashes($optionValue);  // Suppression des anti-slash
                    if (Common::is_alphanumdash($optionValue, array(':', '/', '.', '?', '$', '&', '=')) === false) {
                        throw new Exception("La valeur du paramètre <b>$optionName</b> contient des caractères invalides");
                    }
                    /**
                     *  Si la valeur ne commence pas par http(s):// alors le paramètre est invalide
                     */
                    if (!preg_match('#^https?://#', $optionValue)) {
                        throw new Exception("La valeur du paramètre <b>$optionName</b> doit commencer par http(s)://");
                    }

                /**
                 *  Paramètre gpgkey
                 */
                } elseif ($optionName == 'gpgkey') {
                    $optionValue = trim($optionValue);         // Suppression des espaces si il y en a (ça ne devrait pas)
                    $optionValue = stripslashes($optionValue); // Suppression des anti-slash
                    /**
                     *  La clé gpg peut être un fichier ou une url, donc on accepte certains caractères
                     */
                    if (Common::is_alphanumdash($optionValue, array(':', '/', '.')) === false) {
                        throw new Exception("La valeur du paramètre <b>$optionName</b> contient des caractères invalides");
                    }
                    /**
                     *  Si la valeur ne commence pas par http(s):// ou par file:/// alors le paramètre est invalide
                     */
                    if (!preg_match('#^https?://#', $optionValue) AND !preg_match('/^file:\/\/\//', $optionValue)) {
                        throw new Exception("La valeur du paramètre <b>$optionName</b> doit commencer par http(s):// ou file:///");
                    }
                
                /**
                 *  Paramètre metadata_expire
                 */
                } elseif ($optionName == 'metadata_expire') {
                    if (!is_numeric($optionValue)) {
                        throw new Exception("La valeur du paramètre <b>$optionName</b> doit être un chiffre");
                    }

                /**
                 *  Paramètres sslcacert, sslclientcert, sslclientkey
                 *  Le paramètre doit être un chemin vers un fichier
                 */
                } elseif ($optionName == 'sslcacert' OR $optionName == 'sslclientcert' OR $optionName == 'sslclientkey') {
                    /**
                     *  Vérifie que le fichier existe
                     */
                    if (!file_exists($optionValue)) {
                        throw new Exception("Le fichier <b>$optionValue</b> du paramètre <b>$optionName</b> n'existe pas");
                    }

                    /**
                     *  Vérifie que le fichier est accessible en lecture
                     */
                    if (!is_readable($optionValue)) {
                        throw new Exception("Le fichier <b>$optionValue</b> du paramètre <b>$optionName</b> n'est pas accessible en lecture");
                    }

                /**
                 *  Paramètre autorefresh
                 */
                } elseif ($optionName == 'autorefresh') {
                    $optionValue = 'yes';

                /**
                 *  Tous les autres types paramètres
                 */
                } else {
                    if (Common::is_alphanumdash($optionValue, array('.', ' ', ':', '/', '&', '?', '=')) === false) {
                        throw new Exception("La valeur du paramètre <b>$optionName</b> contient des caractères invalides");
                    }
                    /**
                     *  Si la valeur commence par un slash, ce n'est pas bon... cela pourrait être un chemin de fichier sur le système
                     */
                    if (preg_match('#^/#', $optionValue)) {
                        throw new Exception("La valeur du paramètre <b>$optionName</b> est invalide");
                    }
                }
            }

            /**
             *  Si il n'y a pas eu d'erreurs jusque là alors on forge la ligne du paramètre avec son nom et sa valeur, séparés par un égal '='
             *  Sinon on forge la même ligne mais en laissant la valeur vide afin que l'utilisateur puisse la resaisir
             */
            $content .= $optionName . "=" . $optionValue . PHP_EOL;
        }

        /**
         *  Si des commentaires ont été saisis dans le bloc de textarea 'Notes' alors on ajoute un dièse # avant chaque ligne afin de l'inclure en tant que commentaire dans le fichier
         */
        if (!empty($comments)) {
            $comments = explode(PHP_EOL, Common::validateData($comments));
            foreach ($comments as $comment) {
                $content .= "#".$comment.PHP_EOL;
            }
        }

        /**
         *  Enfin, on écrit le contenu dans le fichier .repo
         */
        file_put_contents(REPOMANAGER_YUM_DIR."/${sourceName}.repo", $content);

        unset($content);
    }

    /**
     *  Supprimer une clé GPG
     */
    public function removeGpgKey(string $gpgkey)
    {
        $gpgkey = Common::validateData($gpgkey);

        /**
         *  Cas Redhat
         *  La clé GPG est située un fichier dans /etc/pki/rpm-gpg/repomanager/
         */
        if (OS_FAMILY == "Redhat") {
            if (!file_exists('/etc/pki/rpm-gpg/repomanager/'.$gpgkey)) {
                throw new Exception("La clé GPG <b>".$gpgkey."</b> n'existe pas");
            }

            if (!unlink('/etc/pki/rpm-gpg/repomanager/'.$gpgkey)) {
                throw new Exception("Impossible de supprimer la clé GPG <b>".$gpgkey."</b>");
            }
        }

        /**
         *  Cas Debian
         *  La clé GPG est présente dans le trousseau gpg 
         */
        if (OS_FAMILY == "Debian") {
            /**
             *  On supprime la clé du trousseau, à partir de son ID
             */
            exec("gpg --no-default-keyring --keyring ".GPGHOME."/trustedkeys.gpg --no-greeting --delete-key --batch --yes $gpgkey", $output, $result);
            if ($result != 0) {
                throw new Exception("Erreur lors de la suppression de la clé GPG <b>$gpgkey</b>");
            }
        }
    }

/**
 *  LISTER TOUS LES REPOS SOURCES
 */
    public function listAll() {
        $query = $this->db->query("SELECT * FROM sources");

        while ($datas = $query->fetchArray(SQLITE3_ASSOC)) $sources[] = $datas;

        /**
         *  Retourne un array avec les noms des groupes
         */
        if (!empty($sources)) return $sources;
    }
}
?>