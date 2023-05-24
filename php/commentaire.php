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
if (isset($_POST['commentaire']) ) {
    traitement_commentaire($bd, $_SESSION['usID']);
}

afficherCommentaire($bd);
ajouterCommentaire($bd, $_SESSION['usID']);

affPiedDePage();


//_______________________________________________________________

// afficher tous les commentaire fait par l'utilisateurs connecter
function afficherCommentaire($bd){
    $sql = 'SELECT * FROM commentaire where coUsager = ' . $_SESSION['usID'] . '';
    $res = bdSendRequest($bd, $sql);
    if (mysqli_num_rows($res)== 0) {
        echo '<p>Vous n\'avez pas encore fait de commentaire</p>';
    } else {
        affCommentairesL($bd, false);
       
    }
}

// ajouter un commentaire
function ajouterCommentaire($bd, $id){
    echo '<h2>Ajouter un commentaire</h2>';
    // récuperer toute les dates distinct de repas de l'utilisateur sauf celle déjà commentée
    $sql = 'SELECT DISTINCT reDate FROM repas WHERE reUsager = ' . $id . ' AND reDate NOT IN (SELECT coDateRepas FROM commentaire WHERE coUsager = ' . $id . ')';
    $res = bdSendRequest($bd, $sql);
    $date = array();
    while ($tab = mysqli_fetch_assoc($res)) {
        $date[] = $tab['reDate'];
    }
    // afficher les dates de repas de l'utilisateur dans un select
    echo '<form action="" method="post">',
    '<p><select name="dateRepas" id="dateRepas">';
    foreach ($date as $value) {
        echo '<option value="' . $value . '">' . date('d-m-Y',strtotime($value)) . '</option>';
    }
    echo '</select></p>',
    '<p><textarea name="commentaire" id="commentaire" cols="30" rows="10" placeholder="ajouter un commentaire"></textarea></p>',
    // la note
    '<p><select name="note" id="note">',
    '<option value="0">0</option>',
    '<option value="1">1</option>',
    '<option value="2">2</option>',
    '<option value="3">3</option>',
    '<option value="4">4</option>',
    '<option value="5">5</option>',
    '</select>  / 5</p>',
    '<p><input type="submit" value="Ajouter"></p>',
    '</form>';
    
    
}

function traitement_commentaire($bd, $id) {
    $sql = 'INSERT INTO commentaire (coUsager, coDateRepas, coTexte, coDatePublication, coNote) VALUES (' . $id . ', "' . $_POST['dateRepas'] . '", "' . $_POST['commentaire'] . '" , "' . DATE_AUJOURDHUI . '", "' . $_POST['note'] . '" )';
    $res = bdSendRequest($bd, $sql);
    if ($res === false) {
        echo '<p>Erreur lors de l\'ajout du commentaire</p>';
    } else {
        echo '<p>Le commentaire a bien été ajouté</p>';
    }
}