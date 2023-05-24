<?php

// chargement des bibliothèques de fonctions
require_once('bibli_erestou.php');
require_once('bibli_generale.php');

// bufferisation des sorties
ob_start();

// démarrage ou reprise de la session
session_start();
// affichage de l'entête
affEntete('Modifier votre commentaire');
// affichage de la barre de navigation
affNav();
$bd = bdConnect();

affModifier($bd);
if (isset($_POST['modifier'])) {
    modifierCommentaire($bd);
}

affPiedDePage();



//_______________________________________________________________

// affiche le formulaire de modification du commentaire
function affModifier ($bd){
    echo '<h2>Modifier votre commentaire du '.date('d-m-Y',strtotime($_POST['dateRepas'])).'</h2>';
    echo '<form action="" method="post">',
    '<p><textarea name="commentaire" id="commentaire" cols="30" rows="10" placeholder="ajouter un commentaire"></textarea></p>',
    '<p><input type="hidden" name="dateRepas" value="' . $_POST['dateRepas'] . '"></p>',
    '<p><select name="note" id="note">',
    '<option value="0">0</option>',
    '<option value="1">1</option>',
    '<option value="2">2</option>',
    '<option value="3">3</option>',
    '<option value="4">4</option>',
    '<option value="5">5</option>',
    '</select>  / 5</p>',
    '<p><input type="submit" name="modifier" value="Modifier"></p>',
    '</form>',
    '<p><a href="commentaire.php">Retour</a></p>';
}

// modifie le commentaire
function modifierCommentaire($bd){
    $sql = 'UPDATE commentaire SET coTexte = "' . $_POST['commentaire'] . '", coNote = ' . $_POST['note'] . ' WHERE coUsager = ' . $_SESSION['usID'] . ' AND coDateRepas = "' . $_POST['dateRepas'] . '"';
    bdSendRequest($bd, $sql);
    header('location: commentaire.php');
}