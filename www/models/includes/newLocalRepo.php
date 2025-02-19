<?php
trait newLocalRepo {
    /**
     *  NOUVEAU REPO LOCAL
     */
    public function exec_newLocalRepo() {

        if ($this->repo->description == "nodescription") $this->repo->description = '';
        if ($this->repo->group == "nogroup") $this->repo->group = '';

        ob_start();

        /**
         *  1. Génération du tableau récapitulatif de l'opération
         */
        if (OS_FAMILY == "Redhat") echo '<h3>CREATION D\'UN NOUVEAU REPO LOCAL</h3>';
        if (OS_FAMILY == "Debian") echo '<h3>CREATION D\'UNE NOUVELLE SECTION DE REPO LOCAL</h3>';

        echo "<table class=\"op-table\">
        <tr>
            <th>NOM DU REPO :</th>
            <td><b>{$this->repo->name}</b></td>
        </tr>";
        if (OS_FAMILY == "Debian") {
            echo "<tr>
                <th>DISTRIBUTION :</th>
                <td><b>{$this->repo->dist}</b></td>
            </tr>
            <tr>
                <th>SECTION :</th>
                <td><b>{$this->repo->section}</b></td>
            </tr>";
        }
        if (!empty($this->repo->gpgResign)) {
            echo "<tr>
                <th>SIGNATURE DU REPO AVEC GPG :</th>
                <td><b>{$this->repo->gpgResign}</b></td>
            </tr>";
        }
        if (!empty($this->repo->description)) {
            echo "<tr>
                <th>DESCRIPTION :</th>
                <td><b>{$this->repo->description}</b></td>
            </tr>";
        }
        if (!empty($this->repo->group)) {
            echo "<tr>
                <th>AJOUT À UN GROUPE :</th>
                <td><b>{$this->repo->group}</b></td>
            </tr>";
        }
        echo '</table>';

        $this->log->steplog(1);
        $this->log->steplogInitialize('createRepo');
        $this->log->steplogTitle('CREATION DU REPO');
        $this->log->steplogLoading();

        /** 
         *  2. On vérifie que le nom du repo n'est pas vide
         */
        if (empty($this->repo->name)) throw new Exception('le nom du repo ne peut être vide');

        /**
         *  3. Création du répertoire avec le nom du repo, et les sous-répertoires permettant d'acceuillir les futurs paquets
         */
        if (OS_FAMILY == "Redhat") {
            if (!file_exists(REPOS_DIR."/{$this->repo->dateFormatted}_{$this->repo->name}/Packages")) {
                if (!mkdir(REPOS_DIR."/{$this->repo->dateFormatted}_{$this->repo->name}/Packages", 0770, true)) throw new Exception("impossible de créer le répertoire du repo {$this->repo->name}");
            }
        }
        if (OS_FAMILY == "Debian") {
            if (!file_exists(REPOS_DIR."/{$this->repo->name}/{$this->repo->dist}/{$this->repo->dateFormatted}_{$this->repo->section}/pool/{$this->repo->section}")) {
                if (!mkdir(REPOS_DIR."/{$this->repo->name}/{$this->repo->dist}/{$this->repo->dateFormatted}_{$this->repo->section}/pool/{$this->repo->section}", 0770, true)) throw new Exception('impossible de créer le répertoire de la section');
            }
        }

        /**
         *   4. Création du lien symbolique
         */
        if (OS_FAMILY == "Redhat") exec("cd ".REPOS_DIR."/ && ln -sfn {$this->repo->dateFormatted}_{$this->repo->name}/ {$this->repo->name}_{$this->repo->env}", $output, $result);            
        if (OS_FAMILY == "Debian") exec("cd ".REPOS_DIR."/{$this->repo->name}/{$this->repo->dist}/ && ln -sfn {$this->repo->dateFormatted}_{$this->repo->section}/ {$this->repo->section}_{$this->repo->env}", $output, $result);
        if ($result != 0) throw new Exception('impossible de créer le repo');

        /**
         *  5. Insertion en BDD du nouveau repo
         */
        try {
            if (OS_FAMILY == "Redhat") $stmt = $this->repo->db->prepare("INSERT INTO repos (Name, Source, Env, Date, Time, Description, Signed, Type, Status) VALUES (:name, :source, :env, :date, :time, :description, :signed, 'local', 'active')");
            if (OS_FAMILY == "Debian") $stmt = $this->repo->db->prepare("INSERT INTO repos (Name, Source, Dist, Section, Env, Date, Time, Description, Signed, Type, Status) VALUES (:name, :source, :dist, :section, :env, :date, :time, :description, :signed, 'local', 'active')");
            $stmt->bindValue(':name', $this->repo->name);
            $stmt->bindValue(':source', $this->repo->name); // C'est un repo local, la source porte alors le même nom que le repo
            $stmt->bindValue(':env', $this->repo->env);
            $stmt->bindValue(':date', $this->repo->date);
            $stmt->bindValue(':time', $this->repo->time);
            $stmt->bindValue(':description', $this->repo->description);
            $stmt->bindValue(':signed', 'no');
            if (OS_FAMILY == "Debian") {
                $stmt->bindValue(':dist', $this->repo->dist);
                $stmt->bindValue(':section', $this->repo->section);
            }
            $stmt->execute();
        } catch(Exception $e) {
            Common::dbError($e);
        }
        unset($stmt);

        /**
         *  6. Application des droits sur le nouveau repo créé
         */
        exec("find ".REPOS_DIR."/{$this->repo->name}/ -type f -exec chmod 0660 {} \;");
        exec("find ".REPOS_DIR."/{$this->repo->name}/ -type d -exec chmod 0770 {} \;");
        exec("chown -R ".WWW_USER.":repomanager ".REPOS_DIR."/{$this->repo->name}/");

        $this->log->steplogOK();

        

        /**
         *  7. Ajout de la section à un groupe si un groupe a été renseigné
         */
        if (!empty($this->repo->group)) {
            $this->log->steplog(2);
            $this->log->steplogInitialize('createRepo');
            $this->log->steplogTitle('AJOUT A UN GROUPE');
            $this->log->steplogLoading();

            try {
                if (OS_FAMILY == "Redhat") $stmt = $this->repo->db->prepare("SELECT repos.Id AS repoId, groups.Id AS groupId FROM repos, groups WHERE repos.Name=:name AND repos.Status = 'active' AND groups.Name=:groupname");
                if (OS_FAMILY == "Debian") $stmt = $this->repo->db->prepare("SELECT repos.Id AS repoId, groups.Id AS groupId FROM repos, groups WHERE repos.Name=:name AND repos.Dist=:dist AND repos.Section=:section AND repos.Status = 'active' AND groups.Name=:groupname");
                $stmt->bindValue(':name', $this->repo->name);
                if (OS_FAMILY == "Debian") {
                    $stmt->bindValue(':dist', $this->repo->dist);
                    $stmt->bindValue(':section', $this->repo->section);
                }
                $stmt->bindValue(':groupname', $this->repo->group);
                $result = $stmt->execute();
            } catch(Exception $e) {
                Common::dbError($e);
            }

            while ($data = $result->fetchArray(SQLITE3_ASSOC)) {
                $repoId = $data['repoId'];
                $groupId = $data['groupId'];
            }

            if (empty($repoId)) throw new Exception("impossible de récupérer l'id du repo {$this->repo->name}");
            if (empty($groupId)) throw new Exception("impossible de récupérer l'id du groupe {$this->repo->group}");
            
            try {
                $stmt = $this->repo->db->prepare("INSERT INTO group_members (Id_repo, Id_group) VALUES (:repoId, :groupId)");
                $stmt->bindValue(':repoId', $repoId);
                $stmt->bindValue(':groupId', $groupId);
                $stmt->execute();
            } catch(Exception $e) {
                Common::dbError($e);
            }

            $this->log->steplogOK();
        }

        /**
         *  8. Génération du fichier de conf repo en local (ces fichiers sont utilisés pour les profils)
         */
        $this->repo->generateConf('default');
    }
}
?>