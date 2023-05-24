<?php

// chargement des bibliothèques de fonctions
require_once('bibli_erestou.php');
require_once('bibli_generale.php');

// bufferisation des sorties
ob_start();

// démarrage ou reprise de la session
print_r($_POST);
session_start();
$dateRepas = $_POST['dateRepas'];
echo $dateRepas;
// affichage de l'entête
affEntete('Modifier votre commentaire');
// affichage de la barre de navigation
affNav();
$bd = bdConnect();

afficherModifier($bd, $dateRepas);

affPiedDePage();



//_______________________________________________________________

function modifierCommentaire($bd, $texte, $date, $id){
    
    $sql = 'UPDATE commentaire SET coTexte = "' . $texte . '" WHERE coUsager = ' . $id . ' AND coDateRepas = "' . $date . '"';
    $res = bdSendRequest($bd, $sql);
    if ($res === false) {
        echo '<p>Erreur lors de la modification du commentaire</p>';
    } else {
        echo '<p>Le commentaire a bien été modifié</p>';
    }
}

function afficherModifier($bd, $dateRepas){
    //affiche les cookies
    echo '<form action="" method="get">',
    '<p><textarea name="modif" id="modif" cols="30" rows="10" placeholder="modifier votre commentaire"></textarea></p>',
    '<p><input type="submit" value="Modifier"></p>',
    '<p><a href="commentaire.php">Retour</a></p>',
    '</form>';
    echo $dateRepas;
    if (!isset($_POST['modif'])) {
    } else {
        modifierCommentaire($bd, $_POST['modif'], $dateRepas, $_SESSION['usID']);
    }
}