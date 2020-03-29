



$(document).ready(function(){

    function afficheResultatRecherche(elementRecherche){
        let trieRecherche = $('#trier').val();
        let filtreRecherche = $('#filtrer').val();
        $.ajax({ 
            url: "mod_composant/ajax/ajax_barreRecherche.php", 
            method: "GET",
            dataType: "json",
            data : {trier : trieRecherche,
                element : elementRecherche,
                filtrer : filtreRecherche},
            success: function(data){
               $('#resultat-recherche').html(data);
            } ,
            error: function(data) {
                alert(" Probleme d'accès au serveur !");
            }
        });
    }
    
    $('#trier').change(function(){
        afficheResultatRecherche("");
    });

    $('#filtrer').change(function(){
        afficheResultatRecherche("");
    });

    $('#elementRecherche').keyup(function(){
        afficheResultatRecherche($(this).val());
    });

});