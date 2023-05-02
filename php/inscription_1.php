<?php

// chargement des bibliothèques de fonctions
require_once('bibli_erestou.php');
require_once('bibli_generale.php');

// bufferisation des sorties
ob_start();


// génération de la page
affEntete('Réception des données saisies');
affNav();

echo '<section><h3>Avec une boucle foreach</h3><ul style="list-style-type: disc">';

foreach($_POST as $cle => $val){
    echo '<li>cle = ', $cle, ', valeur = ', $val, '</li>';
}

echo '</ul></section>';

echo '<section><h3>Avec var_dump()</h3>';

echo '<pre>';
var_dump($_POST);
echo '</pre>';

echo '</section>';

echo '<section><h3>Avec print_r()</h3>';

echo '<pre>', print_r($_POST, true), '</pre>';

echo '</section>';

affPiedDePage();

// facultatif car fait automatiquement par PHP
ob_end_flush();
