<?php

require_once 'bibli_erestou.php';

// démarrage ou reprise de la session
// pas besoin de démarrer la bufferisation des sorties
session_start();
$referer = $_SERVER['HTTP_REFERER'] ?? 'menu.php';
sessionExit($referer);
//redirection vers la page appelante

