<?php

// chargement des bibliothèques de fonctions
require_once('bibli_erestou.php');
require_once('bibli_generale.php');

// bufferisation des sorties
ob_start();

// démarrage ou reprise de la session
session_start();


// affichage de l'entête
affEntete('Espace commentaire');
// affichage de la barre de navigation
affNav();
$bd = bdConnect();

afficherCommentaire($bd);

affPiedDePage();


//_______________________________________________________________

// afficher tous les commentaire fait par l'utilisateurs connecter
function afficherCommentaire($bd){
    $sql = 'SELECT * FROM commentaire where coUsager = ' . $_SESSION['usID'] . '';
    $res = bdSendRequest($bd, $sql);
    if (mysqli_num_rows($res)== 0) {
        echo '<p>Vous n\'avez pas encore fait de commentaire</p>';
    } else {
        affCommentairesL($bd, false, false);
       
    }
}