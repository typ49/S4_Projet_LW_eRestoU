<?php

// chargement des bibliothèques de fonctions
require_once('bibli_erestou.php');
require_once('bibli_generale.php');

// bufferisation des sorties
ob_start();

// démarrage ou reprise de la session
print_r($_POST);
// affichage de l'entête
affEntete('Modifier votre commentaire');
// affichage de la barre de navigation
affNav();
$bd = bdConnect();

afficherModifier($bd);

affPiedDePage();



//_______________________________________________________________

function affModifier ($bd){
    echo '<h2>Modifier votre commentaire</h2>';
    //récupere la date du repas dans $_POST
    $date = $_POST['dateRepas'];
}