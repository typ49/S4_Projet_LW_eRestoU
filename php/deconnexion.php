<?php

require_once 'bibli_erestou.php';

// démarrage ou reprise de la session
// pas besoin de démarrer la bufferisation des sorties
session_start();
$referer = $_SERVER['HTTP_REFERER'] ?? 'menu.php';
// Vérification de la page précédente
if (strpos($referer, 'mon_espace.php') !== false) {
    $referer = 'menu.php';
}
sessionExit($referer);
//redirection vers la page appelante

