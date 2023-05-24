<?php
/*********************************************************
 *        Bibliothèque de fonctions spécifiques          *
 *        à l'application eResto-U                       *
 *********************************************************/

// Force l'affichage des erreurs
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting( E_ALL );

// Phase de développement (IS_DEV = true) ou de production (IS_DEV = false)
define ('IS_DEV', true);

/** Constantes : les paramètres de connexion au serveur MariaDB */
define ('BD_NAME', 'e_RestoU_bd');
define ('BD_USER', 'e_RestoU_user');
define ('BD_PASS', 'e_RestoU_pass');
// define ('BD_NAME', 'merlet_erestou');
// define ('BD_USER', 'merlet_u');
// define ('BD_PASS', 'merlet_p');
define ('BD_SERVER', 'localhost');

// Définit le fuseau horaire par défaut à utiliser. Disponible depuis PHP 5.1
date_default_timezone_set('Europe/Paris');

define ('DATE_AUJOURDHUI', date('Ymd'));
define ('ANNEE_MAX', intdiv(DATE_AUJOURDHUI, 10000));
define ('ANNEE_MIN', ANNEE_MAX - 1);

// Nombre de plats de catégorie 'boisson'
define ('NB_CAT_BOISSON', 4);

// limites liées aux tailles des champs de la table usager
define('LMAX_LOGIN', 8);    // taille du champ usLogin de la table usager
define('LMAX_NOM', 50);      // taille du champ usNom de la table usager
define('LMAX_PRENOM', 80);   // taille du champ usPrenom de la table usager
define('LMAX_EMAIL', 80);   // taille du champ usMail de la table usager

define('LMIN_LOGIN', 4);

define('AGE_MINIMUM', 16);

define('LMIN_PASSWORD', 4);
define('LMAX_PASSWORD', 20);
//_______________________________________________________________
/**
 * Affiche le début du code HTML d'une page (de l'élément DOCTYPE jusqu'au tag de fermeture de l'élément header)
 *
 * @param  string  $title       Le titre de la page (<head> et <h1>)
 * @param  string  $css         Le nom du fichier de feuille de styles à inclure (situé dans le répertoire styles)
 * @param  string  $prefixe     Préfixe à utiliser pour construire les chemins par rapport à la page appelante (chemin vers le répertoire racine de l'application).
 *
 * @return void
 */
function affEntete(string $titre, string $css = 'eResto.css', string $prefixe = '..'): void {
    
    echo    '<!doctype html>',
            '<html lang="fr">',
                '<head>',
                    '<meta charset="UTF-8">',
                    '<title>eRestoU | ', $titre, '</title>',
                    '<link rel="stylesheet" type="text/css" href="', $prefixe, '/styles/', $css, '">',
                '</head>',
                '<body>',
                    '<div id="bcContenu">',
                        '<header>',
                            '<img src="', $prefixe,'/images/logo-eRestoU.png" id="logoRestoU" alt="Logo eResto-U">',
                            '<aside>Le resto-U 100% digital</aside>',
                            '<h1>', $titre, '</h1>',
                            '<a href="http://www.crous-bfc.fr" target="_blank"></a>',
                            '<a href="http://www.univ-fcomte.fr" target="_blank"></a>',
                        '</header>';
}


//_______________________________________________________________
/**
 *  Génération de la barre de navigation (élément nav)
 *
 * @param  string   $prefixe    Préfixe à utiliser pour construire les chemins par rapport à la page appelante (chemin vers le répertoire racine de l'application).
 *
 * @return  void
 */
function affNav(string $prefixe = '..'): void {
    $login = estAuthentifie() ? htmlProtegerSorties($_SESSION['usLogin']) : null;
    echo '<nav>',
            '<ul>',
                '<li><a href="', $prefixe, '/index.php"><span>&#x2630;</span> Accueil</a></li>',
                '<li><a href="', $prefixe, '/php/menu.php"><span>&#x2630;</span> Menus et repas</a></li>',
                $login !== null ?
                "<li><a href='{$prefixe}/php/mon_espace.php'><span>&#x2630;</span> Mon espace [{$login}]</a></li>" :
                "<li><a href='{$prefixe}/php/connexion.php'><span>&#x2630;</span> Connexion</a></li>",
            '</ul>',
        '</nav>',
        '<main>';
}


//_______________________________________________________________
/**
 *  Génération du pied de page.
 *
 * @return  void
 */
function affPiedDePage() : void{
    echo    '</main>',
            '<footer>&copy; Licence Informatique - Février 2023 - Université de Franche-Comté - CROUS de Franche-Comté</footer>',
        '</div>',
    '</body>',
    '</html>';
}

//_______________________________________________________________
/**
* Détermine si l'utilisateur est authentifié
*
* @return bool     true si l'utilisateur est authentifié, false sinon
*/
function estAuthentifie(): bool {
    return  isset($_SESSION['usID']);
}

//___________________________________________________________________
/**
 * Vérification des champs texte des formulaires
 * - utilisé par les pages commentaire.php et inscription.php
 *
 * @param  string        $texte     texte à vérifier
 * @param  string        $nom       chaîne à ajouter dans celle qui décrit l'erreur
 * @param  array         $erreurs   tableau dans lequel les erreurs sont ajoutées
 * @param  ?int          $long      longueur maximale du champ correspondant dans la base de données
 * @param  ?string       $expReg    expression régulière que le texte doit satisfaire
 *
 * @return  void
 */
function verifierTexte(string $texte, string $nom, array &$erreurs, ?int $long = null, ?string $expReg = null) : void{
    if (empty($texte)){
        $erreurs[] = "$nom ne doit pas être vide.";
    }
    else {
        if(strip_tags($texte) != $texte){
            $erreurs[] = "$nom ne doit pas contenir de tags HTML.";
        }
        else if ($expReg !== null && ! preg_match($expReg, $texte)){
            $erreurs[] = "$nom n'est pas valide.";
        }
        if ($long !== null && mb_strlen($texte, encoding:'UTF-8') > $long){
            $erreurs[] = "$nom ne peut pas dépasser $long caractères.";
        }
    }
}

//_______________________________________________________________
/**
 * Termine une session et effectue une redirection vers la page transmise en paramètre
 *
 * Cette fonction est appelée quand l'utilisateur se déconnecte "normalement" et quand une
 * tentative de piratage est détectée. On pourrait améliorer l'application en différenciant ces
 * 2 situations. Et en cas de tentative de piratage, on pourrait faire des traitements pour
 * stocker par exemple l'adresse IP, etc.
 *
 * @param string    $page URL de la page vers laquelle l'utilisateur est redirigé
 *
 * @return void
 */
function sessionExit(string $page = '../index.php'): void {

    // suppression de toutes les variables de session
    $_SESSION = array();

    if (ini_get("session.use_cookies")) {
        // suppression du cookie de session
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 86400,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }

    session_destroy();

    header("Location: $page");
    exit();
}


