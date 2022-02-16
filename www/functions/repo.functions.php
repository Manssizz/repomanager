<?php
/**
 * 	Fonctions liées à l'affichage des listes de repos
 */

function group_by($key, $data) {
    $result = array();

    foreach($data as $val) {
        if(array_key_exists($key, $val)){
            $result[$val[$key]][] = $val;
        }else{
            $result[""][] = $val;
        }
    }

    return $result;
}

function processList(array $reposList) {
    global $repoStatus;

    $repoLastName = '';
    $repoLastDist = '';
    $repoLastSection = '';
    $repoLastEnv = '';

    foreach($reposList as $repoArray) {

        if (OS_FAMILY == 'Redhat') echo '<div class="repos-list-group-flex-div-redhat">';
        if (OS_FAMILY == 'Debian') echo '<div class="repos-list-group-flex-div-debian">';

        foreach ($repoArray as $repo) {
            $repoId     = $repo['Id'];
            $repoName   = $repo['Name'];
            $repoSource = $repo['Source'];
            if (OS_FAMILY == "Debian") {
                $repoDist    = $repo['Dist'];
                $repoSection = $repo['Section'];
            }
            if ($repoStatus == 'active') {
                $repoEnv = $repo['Env'];
            }
            $repoDate        = DateTime::createFromFormat('Y-m-d', $repo['Date'])->format('d-m-Y');
            $repoTime        = $repo['Time'];
            $repoType        = $repo['Type'];
            $repoSigned      = $repo['Signed'];
            $repoDescription = $repo['Description'];



            /**
             *  On transmets ces infos à la fonction printRepoLine qui va se charger d'afficher la ligne du repo
             */
            if ($repoStatus == 'active') {
                if (OS_FAMILY == "Redhat") printRepoLine(compact('repoId', 'repoName', 'repoSource', 'repoEnv', 'repoDate', 'repoTime', 'repoDescription', 'repoType', 'repoSigned', 'repoLastName'));
                if (OS_FAMILY == "Debian") printRepoLine(compact('repoId', 'repoName', 'repoDist', 'repoSection', 'repoSource', 'repoEnv', 'repoDate', 'repoTime', 'repoDescription', 'repoType', 'repoSigned', 'repoLastName', 'repoLastDist', 'repoLastSection'));
            }
            if ($repoStatus == 'archived') {
                if (OS_FAMILY == "Redhat") printRepoLine(compact('repoId', 'repoName', 'repoSource', 'repoDate', 'repoTime', 'repoDescription', 'repoType', 'repoSigned', 'repoLastName'));
                if (OS_FAMILY == "Debian") printRepoLine(compact('repoId', 'repoName', 'repoDist', 'repoSection', 'repoSource', 'repoDate', 'repoTime', 'repoDescription', 'repoType', 'repoSigned', 'repoLastName', 'repoLastDist', 'repoLastSection'));
            }

            if (!empty($repoName)) {
                $repoLastName = $repoName;
            }
            if (!empty($repoDist)) {
                $repoLastDist = $repoDist;
            }
            if (!empty($repoSection)) {
                $repoLastSection = $repoSection;
            }
        }
        echo '</div>';
    }
}

/**
 *  Affiche la ligne d'un repo
 */
function printRepoLine($repoData = []) {
    global $repoLastName;
    global $repoLastDist;
    global $repoLastSection;
    global $repoStatus;

    $printRepoName = '';
    $printRepoDist = '';
    $printRepoSection = '';
    $printAction = '';
    $printEmptyLine = 'no';

    $arrayContent = array();
    $line = array();

	/**
	 * 	Récupère les infos concernant le repo passées en argument
	 */
    extract($repoData);

    if ($repoLastName == $repoName) {
        $printRepoName = 'no';
    } else {
        $printRepoName = 'yes';
    }

    if (OS_FAMILY == "Debian") {
        // if ($repoName == $repoLastName AND !empty($repoLastDist) AND $repoDist != $repoLastDist) {
        //     $printRepoName = 'yes';
        // }
        if ($repoName == $repoLastName AND !empty($repoLastDist) AND $repoDist == $repoLastDist) {
            $printRepoDist = 'no';
        } else {
            $printRepoDist = 'yes';
        }
        if ($repoName == $repoLastName AND !empty($repoLastDist) AND $repoDist == $repoLastDist AND !empty($repoLastSection) AND $repoSection == $repoLastSection) {
            $printRepoSection = 'no';
        } else {
            $printRepoSection = 'yes';
        }

        if ($repoName == $repoLastName AND $repoLastDist != $repoDist) {
            $printEmptyLine = 'yes';
        }
    }

    if ($repoName == $repoLastName AND !empty($repoLastDist) AND $repoDist == $repoLastDist AND !empty($repoLastSection) AND $repoSection == $repoLastSection) {
        $printAction = 'no';
    } else {
        $printAction = 'yes';
    }

    if ($printEmptyLine == 'yes') {
        echo '<div class="item"></div>';
        echo '<div class="item"></div>';
        echo '<div class="item"></div>';
        echo '<div class="item"></div>';
        echo '<div class="item"></div>';
        echo '<div class="item"></div>';
        echo '<div class="item"></div>';
        echo '<div class="item"></div>';
        echo '<div class="item"></div>';
        echo '<div class="item"></div>';
        echo '<div class="item"></div>';
        echo '<div class="item"></div>';
        echo '<div class="item"></div>';
        echo '<div class="item"></div>';
    }

    // echo '<tr>';
        /**
         *  Affichage des icones d'opérations
         */
        // echo '<td class="td-10">';
        //     if ($repoStatus == 'active') {
        //         /**
        //          *  Affichage de l'icone "corbeille" pour supprimer le repo
        //          *  Pour Redhat, on précise l'id du repo à supprimer
        //          *  Pour Debian, on précise le nom du repo puisque celui-ci n'a pas d'id directement (ce sont les sections qui ont des id en BDD)
        //          */
        //         if (Common::isadmin()) {
        //             if (OS_FAMILY == "Redhat") echo "<a href=\"operation.php?action=delete&id=${repoId}\"><img class=\"icon-lowopacity-red\" src=\"ressources/icons/bin.png\" title=\"Supprimer le repo ${repoName} (${repoEnv})\" /></a>";
        //             if (OS_FAMILY == "Debian") echo "<a href=\"operation.php?action=delete&id=${repoId}\"><img class=\"icon-lowopacity-red\" src=\"ressources/icons/bin.png\" title=\"Supprimer le repo ${repoName}\" /></a>";
        //         }
        //         /**
        //          *  Affichage de l'icone "dupliquer" pour dupliquer le repo
        //          */
        //         if (Common::isadmin()) {
        //             if (OS_FAMILY == "Redhat") echo "<a href=\"operation.php?action=duplicate&id=${repoId}&repoGroup=ask&repoDescription=ask\"><img class=\"icon-lowopacity\" src=\"ressources/icons/duplicate.png\" title=\"Dupliquer le repo ${repoName} (${repoEnv})\" /></a>";
        //             if (OS_FAMILY == "Debian") echo "<a href=\"operation.php?action=duplicate&id=${repoId}&repoGroup=ask&repoDescription=ask\"><img class=\"icon-lowopacity\" src=\"ressources/icons/duplicate.png\" title=\"Dupliquer le repo ${repoName} avec sa distribution ${repoDist} et sa section ${repoSection} (${repoEnv})\" /></a>";
        //         }
        //         /**
        //          *  Affichage de l'icone "terminal" pour afficher la conf repo à mettre en place sur les serveurs
        //          */
        //         if (OS_FAMILY == "Redhat") echo "<img class=\"client-configuration-button icon-lowopacity\" os_family=\"Redhat\" repo=\"$repoName\" env=\"$repoEnv\" repo_dir_url=\"".WWW_REPOS_DIR_URL."\" repo_conf_files_prefix=\"".REPO_CONF_FILES_PREFIX."\" www_hostname=\"".WWW_HOSTNAME."\" src=\"ressources/icons/code.png\" title=\"Afficher la configuration client\" />";
        //         if (OS_FAMILY == "Debian") echo "<img class=\"client-configuration-button icon-lowopacity\" os_family=\"Debian\" repo=\"$repoName\" dist=\"$repoDist\" section=\"$repoSection\" env=\"$repoEnv\" repo_dir_url=\"".WWW_REPOS_DIR_URL."\" repo_conf_files_prefix=\"".REPO_CONF_FILES_PREFIX."\" www_hostname=\"".WWW_HOSTNAME."\" src=\"ressources/icons/code.png\" title=\"Afficher la configuration client\" />";
                
        //         /**
        //          *  Affichage de l'icone 'update' pour mettre à jour le repo/section. On affiche seulement si l'env du repo/section = DEFAULT_ENV et si il s'agit d'un miroir
        //          */
        //         if (Common::isadmin() AND $repoType === "mirror" AND $repoEnv === DEFAULT_ENV) {
        //             if (OS_FAMILY == "Redhat") echo "<a href=\"operation.php?action=update&id=${repoId}&repoGpgCheck=ask&repoGpgResign=ask\"><img class=\"icon-lowopacity\" src=\"ressources/icons/update.png\" title=\"Mettre à jour le repo ${repoName} (${repoEnv})\" /></a>";
        //             if (OS_FAMILY == "Debian") echo "<a href=\"operation.php?action=update&id=${repoId}&repoGpgCheck=ask&repoGpgResign=ask\"><img class=\"icon-lowopacity\" src=\"ressources/icons/update.png\" title=\"Mettre à jour la section ${repoSection} (${repoEnv})\" /></a>";
        //         }
        //     }
        //     if ($repoStatus == 'archived') {
        //         if (Common::isadmin()) {
        //             if (OS_FAMILY == "Redhat") echo "<a href=\"operation.php?action=deleteArchive&id=${repoId}\"><img class=\"icon-lowopacity-red\" src=\"ressources/icons/bin.png\" title=\"Supprimer le repo archivé ${repoName}\" /></a>";
        //             if (OS_FAMILY == "Debian") echo "<a href=\"operation.php?action=deleteArchive&id=${repoId}\"><img class=\"icon-lowopacity-red\" src=\"ressources/icons/bin.png\" title=\"Supprimer la section archivée ${repoSection}\" /></a>";
                
        //             /**
        //              *  Affichage de l'icone "remise en production du repo"
        //              */
        //             if (OS_FAMILY == "Redhat") echo "<a href=\"operation.php?action=restore&id=${repoId}&repoDescription=${repoDescription}&repoNewEnv=ask\"><img class=\"icon-lowopacity-red\" src=\"ressources/icons/arrow-circle-up.png\" title=\"Restaurer le repo archivé ${repoName} en date du ${repoDate}\" /></a>";
        //             if (OS_FAMILY == "Debian") echo "<a href=\"operation.php?action=restore&id=${repoId}&repoDescription=${repoDescription}&repoNewEnv=ask\"><img class=\"icon-lowopacity-red\" src=\"ressources/icons/arrow-circle-up.png\" title=\"Restaurer la section archivée ${repoSection} en date du ${repoDate}\" /></a>";
        //         }
        //     }
        // echo '</td>';

    echo '<div class="item-repo">';
        if ($printRepoName == "yes") {
            echo $repoName;
        }
    echo '</div>';

    if (OS_FAMILY == "Debian") {
        // Si la vue simplifiée est activée (masquage du nom de repo si similaire au précédent) :
        // if (CONCATENATE_REPOS_NAME == "yes" AND $repoName === $repoLastName AND $repoDist === $repoLastDist) {
        //     if ($repoStatus == 'active') echo '<td class="td-fit"></td>';
        //     echo '<td class="td-30"></td>';
        // } else {
        //     if ($repoStatus == 'active') {
        //         echo '<td class="td-fit">';
        //         if (Common::isadmin()) echo "<a href=\"operation.php?action=deleteDist&id=${repoId}\"><img class=\"icon-verylowopacity-red\" src=\"ressources/icons/bin.png\" title=\"Supprimer la distribution ${repoDist}\" /></a>"; // td de toute petite taille, permettra d'afficher une icone 'corbeille' avant chaque distribution
        //         echo '</td>';
        //     }
        //     echo "<td class=\"td-30\">$repoDist</td>";
        // }

        // if ($repoName === $repoLastName AND $repoDist !== $repoLastDist) {
        //     // if ($repoStatus == 'active') {
        //     //     echo '<td class="td-fit">';
        //     //     if (Common::isadmin()) echo "<a href=\"operation.php?action=deleteDist&id=${repoId}\"><img class=\"icon-verylowopacity-red\" src=\"ressources/icons/bin.png\" title=\"Supprimer la distribution ${repoDist}\" /></a>"; // td de toute petite taille, permettra d'afficher une icone 'corbeille' avant chaque distribution
        //     //     echo '</td>';
        //     // }
        //     echo "<div class=\"item\">$repoDist</div>";
        // }

        if ($printRepoDist == 'yes' OR $printRepoSection == 'yes') {
            echo '<div class="item-dist-section">';
                echo '<div class="item-dist-section-sub">';
                    if ($printRepoDist == 'yes') {
                        echo '<span class="item-dist"><img src="ressources/icons/link.png" class="icon" />'.$repoDist.'</span>';
                    }
                    if ($printRepoSection == 'yes') {
                        echo '<span class="item-section"><img src="ressources/icons/link.png" class="icon" />'.$repoSection.'</span>';
                    }
                echo '</div>';
            echo '</div>';
        } else {
            echo '<div class="item-dist-section"></div>';
        }



        // if ($repoStatus == 'active') {
        //     echo '<td class="td-fit">';
        //     if (Common::isadmin()) echo "<a href=\"operation.php?action=deleteSection&id=${repoId}\"><img class=\"icon-verylowopacity-red\" src=\"ressources/icons/bin.png\" title=\"Supprimer la section ${repoSection} (${repoEnv})\" /></a>"; // td de toute petite taille, permettra d'afficher une icone 'corbeille' avant chaque section
        //     echo '</td>';
        // }

        // Si la vue simplifiée est activée (masquage du nom de repo si similaire au précédent) :    
        // if ($repoName === $repoLastName AND $repoDist === $repoLastDist AND $repoSection === $repoLastSection) {
        //     echo '<div class=\"item\"></div>';
        // } else {
        //     echo "<div class=\"item\">$repoSection</div>";
        // }
        // if ($repoName == $repoLastName AND !empty($repoLastDist) AND $repoDist == $repoLastDist AND !empty($repoLastSection) AND $repoSection == $repoLastSection) {
        //     echo "<div class=\"item-section\"></div>";
        // } else {
        //     echo "<div class=\"item-section\">$repoSection</div>";
        // }
    }

    /**
     *  Affichage de l'env en couleur
     *  On regarde d'abord combien d'environnements sont configurés. Si il n'y a qu'un environement, l'env restera blanc.
     */
    if ($repoStatus == 'active') {
        // echo '<td class="td-fit">';
        // if (Common::isadmin() AND ENVS_TOTAL > 1) {
        //     /**
        //      *  Icone permettant d'ajouter un nouvel environnement, placée juste avant la date
        //      */           
        //     echo "<a href=\"operation.php?action=changeEnv&id=${repoId}&repoNewEnv=ask&repoDescription=ask\"><img class=\"icon-verylowopacity-red\" src=\"ressources/icons/link.png\" title=\"Faire pointer un nouvel environnement sur le repo $repoName du $repoDate\" /></a>"; // td de toute petite taille, permettra d'afficher une icone 'link' avant chaque date
        // }
        // echo '</td>';
        // echo "<div class=\"item\">$repoEnv</div>";
        echo '<div class="item-env"><input type="checkbox" class="icon-verylowopacity" value="'.$repoId.'">'.Common::envtag($repoEnv).'</div>';
    }

    /**
     *  Affichage de la date
     */
    echo '<div class="item-date" title="'.$repoDate.' '.$repoTime.'"><span>'.$repoDate.'</span></div>';

    /**
     *  Affichage de la taille
     */
    if (PRINT_REPO_SIZE == "yes") {
        if ($repoStatus == 'active') {
            if (OS_FAMILY == "Redhat") $repoSize = exec("du -hs ".REPOS_DIR."/${repoDate}_${repoName} | awk '{print $1}'");
            if (OS_FAMILY == "Debian") $repoSize = exec("du -hs ".REPOS_DIR."/${repoName}/${repoDist}/${repoDate}_${repoSection} | awk '{print $1}'");
        }
        if ($repoStatus == 'archived') {
            if (OS_FAMILY == "Redhat" AND PRINT_REPO_SIZE == "yes") $repoSize = exec("du -hs ".REPOS_DIR."/archived_${repoDate}_${repoName} | awk '{print $1}'");
            if (OS_FAMILY == "Debian" AND PRINT_REPO_SIZE == "yes") $repoSize = exec("du -hs ".REPOS_DIR."/${repoName}/${repoDist}/archived_${repoDate}_${repoSection} | awk '{print $1}'");
        }

        echo '<div class="item-info">';
            echo '<span>'.$repoSize.'</span><br>';
            /**
             *  Affichage de l'icone du type de repo (miroir ou local)
             */
            if ($repoType == "mirror") {
                echo "<img class=\"icon-lowopacity\" src=\"ressources/icons/world.png\" title=\"Type : miroir ($repoSource)\" />";
            } elseif ($repoType == "local") {
                echo '<img class="icon-lowopacity" src="ressources/icons/pin.png" title="Type : local" />';
            } else {
                echo '<img class="icon-lowopacity" src="ressources/icons/unknow.png" title="Type : inconnu" />';                
            }
            /**
             *  Affichage de l'icone de signature GPG du repo
             */
            if ($repoSigned == "yes") {
                echo '<img class="icon-lowopacity" src="ressources/icons/key.png" title="Repo signé avec GPG" />';
            } elseif ($repoSigned == "no") {
                echo '<img class="icon-lowopacity" src="ressources/icons/key2.png" title="Repo non-signé avec GPG" />';
            } else {
                echo '<img class="icon-lowopacity" src="ressources/icons/unknow.png" title="Signature GPG : inconnue" />';
            }
            /**
             *  Affichage de l'icone "statistiques"
             */
            if (CRON_STATS_ENABLED == "yes" AND $repoStatus == 'active') {
                if (OS_FAMILY == "Redhat") echo "<a href=\"stats.php?id=${repoId}\"><img class=\"icon-lowopacity\" src=\"ressources/icons/stats.png\" title=\"Voir les stats du repo $repoName (${repoEnv})\" /></a>";
                if (OS_FAMILY == "Debian") echo "<a href=\"stats.php?id=${repoId}\"><img class=\"icon-lowopacity\" src=\"ressources/icons/stats.png\" title=\"Voir les stats de la section $repoSection (${repoEnv})\" /></a>";
            }
            /**
             *  Affichage de l'icone "explorer"
             */
            if ($repoStatus == 'active') {
                if (OS_FAMILY == "Redhat") echo "<a href=\"explore.php?id=${repoId}&state=active\"><img class=\"icon-lowopacity\" src=\"ressources/icons/search.png\" title=\"Explorer le repo $repoName (${repoEnv})\" /></a>";
                if (OS_FAMILY == "Debian") echo "<a href=\"explore.php?id=${repoId}&state=active\"><img class=\"icon-lowopacity\" src=\"ressources/icons/search.png\" title=\"Explorer la section ${repoSection} (${repoEnv})\" /></a>";
            }
            if ($repoStatus == 'archived') {
                if (OS_FAMILY == "Redhat") echo "<a href=\"explore.php?id=${repoId}&state=archived\"><img class=\"icon-lowopacity\" src=\"ressources/icons/search.png\" title=\"Explorer le repo $repoName archivé (${repoDate})\" /></a>";
                if (OS_FAMILY == "Debian") echo "<a href=\"explore.php?id=${repoId}&state=archived\"><img class=\"icon-lowopacity\" src=\"ressources/icons/search.png\" title=\"Explorer la section archivée ${repoSection} (${repoDate})\" /></a>";
            }
            /**
             *  Affichage de l'icone "warning" si le répertoire du repo n'existe plus sur le serveur
             */
            if ($repoStatus == 'active') {
                if (OS_FAMILY == "Redhat") {
                    if (!is_dir(REPOS_DIR."/${repoDate}_${repoName}")) echo '<img class="icon" src="ressources/icons/warning.png" title="Le répertoire de ce repo semble inexistant sur le serveur" />';
                }
                if (OS_FAMILY == "Debian") {
                    if (!is_dir(REPOS_DIR."/$repoName/$repoDist/${repoDate}_${repoSection}")) echo '<img class="icon" src="ressources/icons/warning.png" title="Le répertoire de cette section semble inexistant sur le serveur" />';
                }
            }
            if ($repoStatus == 'archived') {
                if (OS_FAMILY == "Redhat") {
                    if (!is_dir(REPOS_DIR."/archived_${repoDate}_${repoName}")) echo '<img class="icon" src="ressources/icons/warning.png" title="Le répertoire de ce repo semble inexistant sur le serveur" />';
                }
                if (OS_FAMILY == "Debian") {
                    if (!is_dir(REPOS_DIR."/$repoName/$repoDist/archived_${repoDate}_${repoSection}")) echo '<img class="icon" src="ressources/icons/warning.png" title="Le répertoire de cette section semble inexistant sur le serveur" />';
                }
            }
        echo '</div>';
    }

    /**
     *  Affichage de la description
     */
    echo '<div class="item-desc"><input type="text" class="repoDescriptionInput" repo-id="'.$repoId.'" repo-status="'.$repoStatus.'" value="'.$repoDescription.'" /></div>';

    /**
     *  Affichage du bouton Action
     */
    if ($printAction == 'yes') {
        echo '<div class="item-action"><img src="ressources/icons/rocket.png" class="icon" /></div>';
    } else {
        echo '<div class="item-action"></div>';
    }

}
?>