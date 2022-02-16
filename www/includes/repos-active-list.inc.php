<?php

$listColor = 'color1'; // initialise des variables permettant de changer la couleur dans l'affichage de la liste des repos

$repoStatus = 'active';

/**
 *  Cas où on trie par groupes
 */
if (FILTER_BY_GROUPS == "yes") {
    /**
     *  Récupération de tous les noms de groupes
     */
    $mygroup = new Group('repo');
    $groupsList = $mygroup->listAllWithDefault();

    /**
     *  On va afficher le tableau de repos seulement si la commande précédente a trouvé des groupes dans le fichier (résultat non vide)
     */
    if (!empty($groupsList)) {
        foreach($groupsList as $groupName) {
            echo '<div class="repos-list-group">';

            echo "<h3>$groupName</h3>";

            /**
             *  Récupération de la liste des repos du groupe
             */
            $reposList = $mygroup->listRepos($groupName);

            if (!empty($reposList)) {
                // echo '<pre>';
                // print_r($reposList);
                // echo '</pre>';

                $reposList = group_by("Name", $reposList);

                /**
                 *  Traitement de la liste des repos
                 */
                processList($reposList);

            } else {
                echo '<p>Il n\'y a aucun repo dans ce groupe</p>';
            }
            echo '</div>';
        }
    }
}

/**
 *  Cas où on ne trie pas par groupes
 */
if (FILTER_BY_GROUPS == "no") {
    /**
     *  Affichage de l'en-tête du tableau
     */
    printHead();

    $myrepo = new Repo();
    $reposList = $myrepo->listAll();
    unset($myrepo);

    if (!empty($reposList)) {
        /**
         *  Traitement de la liste des repos
        */
        processList($reposList);
    }
}
?>