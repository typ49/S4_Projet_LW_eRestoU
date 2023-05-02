<?php

require_once 'bibli_generale.php';
require_once 'bibli_erestou.php';

// bufferisation des sorties
ob_start();

// démarrage ou reprise de la session
session_start();


// si l'utilisateur n'est pas authentifié, on le redirige sur la page index.php
if (! estAuthentifie()){
    header('Location: ../index.php');
    exit;
}

// affichage de l'entête
affEntete('Accès restreint');
// affichage de la barre de navigation
affNav();


$bd = bdConnect();

$sql = "SELECT *
        FROM usager
        WHERE usID = {$_SESSION['usID']}";

$res = bdSendRequest($bd, $sql);

$T = mysqli_fetch_assoc($res);

mysqli_free_result($res);
mysqli_close($bd);

$T = htmlProtegerSorties($T);

echo '<section>',
        '<h3>Accès restreint aux utilisateurs authentifiés</h3>';

echo    '<ul style="list-style-type: disc">',
            '<li><strong>ID : ', $_SESSION['usID'], '</strong></li>',
            '<li>SID : ', session_id(), '</li>';
foreach($T as $cle => $val){
    echo    '<li>', $cle, ' : ', $val, '</li>';
} 
echo    '</ul>',
    '</section>';

// affichage du pied de page
affPiedDePage();

// facultatif car fait automatiquement par PHP
ob_end_flush();


