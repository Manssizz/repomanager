/**
 *  Events listeners
 */

/** 
 *  Event : afficher ou masquer la div qui gère les paramètres d'affichage (bouton "Affichage")
 */
$(document).on('click','#ReposListDisplayToggleButton, #DisplayCloseButton',function(){
    $("#divReposListDisplay").slideToggle('slow');
});

/**
 * 
 *  Fonctions utiles
 * 
 */

/**
 * Afficher un message d'alerte (success ou error)
 * @param {*} message 
 * @param {*} type 
 */
function printAlert(message, type) {
    if (type == "error") {
        $('footer').append('<div id="newalert" class="alert-error">'+message+'</div>');
    }
    if (type == "success") {
        $('footer').append('<div id="newalert" class="alert-success">'+message+'</div>');
    }

    window.setTimeout(function() {
        $('#newalert').fadeTo(1000, 0).slideUp(1000, function(){
            $('#newalert').remove();
        });
    }, 2500);
}

function deleteConfirm(message, myfunction) {
    /**
     *  D'abord on supprime toute alerte déjà active et qui ne serait pas fermée
     */
     $("#newdeletealert").remove();

    var $content = '<div id="newdeletealert" class="deleteAlert"><span class="deleteAlert-message">'+message+'</span><div class="deleteAlert-buttons-container"><span class="pointer btn-doDelete">Supprimer</span><span class="pointer btn-cancelDelete">Annuler</span></div></div>';

    $('footer').append($content);

    /**
     *  Si on clique sur le bouton 'Supprimer'
     */
    $('.btn-doDelete').click(function () {
        /**
         *  Exécution de la fonction passée en paramètre
         */
        myfunction();

        /**
         *  Puis suppression de l'alerte
         */
        $("#newdeletealert").slideToggle(150, function () {
            $("#newdeletealert").remove();
        });
    });

    /**
     *  Si on clique sur le bouton 'Annuler'
     */
    $('.btn-cancelDelete').click(function () {
        /**
         *  Suppression de l'alerte
         */
        $("#newdeletealert").slideToggle(150, function () {
            $("#newdeletealert").remove();
        });
    });
}

/**
 * Rechargement du contenu d'un élément, par son Id
 * @param {string} id 
 */
function reloadContentById(id){
    $('#'+id).load(location.href + ' #'+id+' > *');
}

/**
 * Rechargement du contenu d'un élément par sa classe
 * @param {string} className
 */
function reloadContentByClass(className){
    $('.'+className).load(location.href + ' .'+className+' > *');
}

/**
 *  Rechargement de la div 'nouveau repo'
 */
function reloadNewRepoDiv(){
    $("#newRepoDiv").load(" #newRepoDiv > *");

    if ($("#repoType_mirror").is(":checked")) {
        $(".type_mirror_input").show();
        $(".type_local_input").hide();
    } else {
        $(".type_mirror_input").hide();
        $(".type_local_input").show();
    }
}