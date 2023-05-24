<?php

// chargement des bibliothèques de fonctions
require_once('bibli_generale.php');
require_once('bibli_erestou.php');

// bufferisation des sorties
ob_start();

// démarrage ou reprise de la session
session_start();

//supprime le commentaire et renvoie à la page commentaire
if (isset($_POST['supprimer'])) {
    $bd = bdConnect();
    $sql = 'DELETE FROM commentaire WHERE coUsager = ' . $_SESSION['usID'] . ' AND coDateRepas = "' . $_POST['dateRepas'] . '"';
    $res = bdSendRequest($bd, $sql);
    header('location: commentaire.php');
}
header('location: commentaire.php');