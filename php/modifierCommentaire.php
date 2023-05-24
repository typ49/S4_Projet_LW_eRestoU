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

function modifierCommentaire($bd, $texte, $id){
    $sql = 'UPDATE commentaire SET coTexte = "' . $texte . '" WHERE coUsager = ' . $id . ' AND coDateRepas = "' . $_SESSION['date'] . '"';
    $res = bdSendRequest($bd, $sql);
    if ($res === false) {
        echo '<p>Erreur lors de la modification du commentaire</p>';
    } else {
        echo '<p>Le commentaire a bien été modifié</p>';
    }
}

function afficherModifier($bd){
    //affiche les cookies
    echo '<form action="" method="post">',
    '<p><textarea name="modif" id="modif" cols="30" rows="10" placeholder="modifier votre commentaire"></textarea></p>',
    '<p><input type="submit" value="Modifier"></p>',
    '<p><a href="commentaire.php">Retour</a></p>',
    '</form>';
    if (!isset($_POST['modif'])) {
        $_SESSION['date'] = $_POST['dateRepas'];
        print_r($_SESSION);
    } else {
        modifierCommentaire($bd, $_POST['modif'], $_SESSION['usID']);
    }
}