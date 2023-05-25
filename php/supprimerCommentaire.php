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
    //supprimer le fichier image  ../upload/coDateRepas_coUsager.jpg
    $coDateRepas = $_POST['dateRepas'];
    $coUsager = $_SESSION['usID'];
    $coDateRepas = preg_replace('/[^A-Za-z0-9_\-]/', '_', $coDateRepas);
    $coUsager = preg_replace('/[^A-Za-z0-9_\-]/', '_', $coUsager);
    $newFileName = $coDateRepas . '_' . $coUsager . '.jpg';
    $target_dir = "../upload/";
    unlink($target_dir . $newFileName);


}
header('location: commentaire.php');