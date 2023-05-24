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

function affUnCommentaire($usager, $dateRepas, $texte, $datePublication, $note, $nom, $prenom, $isModifiable)
{
    $anneePublication = substr($datePublication, 0, 4);
    $moisPublication = substr($datePublication, 4, 2);
    $jourPublication = substr($datePublication, 6, 2);
    $heurePublication = substr($datePublication, 8, 2);
    $minutePublication = substr($datePublication, 10, 2);

    //conversion mois en lettre
    $moisPublication = getTableauMois()[(int) $moisPublication];

    //on retire les 0 inutiles des jours et heures
    $jourPublication = (int) $jourPublication;
    $heurePublication = (int) $heurePublication;

    
    

    $publication = "publié le $jourPublication $moisPublication $anneePublication à $heurePublication h $minutePublication";

    $image = "../upload/{$dateRepas}_$usager.jpg";
    echo '<article>',
        (is_file($image)) ? "<img src=\"$image\" alt=\"Photo illustrant le commentaire\">" : "",
        "<h5>Commentaire de $prenom $nom $publication </h5>",
        "<p> $texte </p>",
        "<footer>Note : $note / 5<br>",
        "<form action='./modifierCommentaire.php' id='modif' method='post'>",
        "<input type='hidden' name='dateRepas' value='$dateRepas'>",
        "<input type='submit' name='modifier' value='modifier'>",
        "</form>",
        "<form action='./supprimerCommentaire.php' id='suppr' method='post'>",
        "<input type='hidden' name='dateRepas' value='$dateRepas'>",
        "<input type='submit' name='supprimer' value='supprimer'>",
        "</form>",
        "</footer>",
        '</article>';
}

function affCommentairesL($bd, bool $commander, bool $isdate)
{
    // on récupère tout les commentaires de la date sélectionnée
    $date = dateConsulteeL();
    if (is_string($date)) {
        return;
    }
    $date = (string) $date;
    if ($isdate == true) {
        $sql = "SELECT usNom, usPrenom, coUsager, coTexte, coDatePublication, coNote, coDateRepas,
                (SELECT COUNT(*) FROM commentaire WHERE coDateRepas LIKE '$date') AS nbCommentaires,
                (SELECT AVG(coNote) FROM commentaire WHERE coDateRepas LIKE '$date') AS moyenne
                FROM commentaire INNER JOIN usager ON usID = coUsager 
                WHERE coDateRepas LIKE '$date' ORDER BY coDatePublication DESC";

        $res = bdSendRequest($bd, $sql);


        if ($row = mysqli_fetch_assoc($res)) {
            $moyenne = floatval(number_format($row['moyenne']));


            $nbCommentaires = $row['nbCommentaires'];

            $pluriel = ($nbCommentaires > 1) ? "s" : "";

            echo "<h4>Commentaire$pluriel sur ce menu</h4>",
                "<p>Note moyenne de ce menu : $moyenne/5 sur la base de $nbCommentaires commentaire$pluriel";


            do {
                //gestion des injections : 
                $row['coUsager'] = htmlProtegerSorties($row['coUsager']);
                $row['coTexte'] = htmlProtegerSorties($row['coTexte']);
                $row['coDatePublication'] = htmlProtegerSorties($row['coDatePublication']);
                $row['coNote'] = htmlProtegerSorties($row['coNote']);
                $row['usNom'] = htmlProtegerSorties($row['usNom']);
                $row['usPrenom'] = htmlProtegerSorties($row['usPrenom']);

                affUnCommentaire($row['coUsager'], $row['coDateRepas'], $row['coTexte'], $row['coDatePublication'], $row['coNote'], $row['usNom'], $row['usPrenom'], false);
            } while ($row = mysqli_fetch_assoc($res));
        }
        mysqli_free_result($res);
    } else {
        echo "<h4>Mes Commentaires</h4>";
        $sql = "SELECT usNom, usPrenom, coUsager, coTexte, coDatePublication, coNote, coDateRepas FROM commentaire, usager WHERE coUsager = $_SESSION[usID] AND usID = coUsager ORDER BY coDatePublication DESC";

        $res = bdSendRequest($bd, $sql);

        if ($row = mysqli_fetch_assoc($res)) {
            do {
                //gestion des injections : 
                $row['coUsager'] = htmlProtegerSorties($row['coUsager']);
                $row['coTexte'] = htmlProtegerSorties($row['coTexte']);
                $row['coDatePublication'] = htmlProtegerSorties($row['coDatePublication']);
                $row['coNote'] = htmlProtegerSorties($row['coNote']);
                $row['usNom'] = htmlProtegerSorties($row['usNom']);
                $row['usPrenom'] = htmlProtegerSorties($row['usPrenom']);

                affUnCommentaire($row['coUsager'], $row['coDateRepas'], $row['coTexte'], $row['coDatePublication'], $row['coNote'], $row['usNom'], $row['usPrenom'], false);
            } while ($row = mysqli_fetch_assoc($res));
        }
    }

    // affiche le lien si l'utilisateur est connecté et si il a commander
    if ($commander == true) {
        echo "<a href='./commentaire.php' id=\"ajouter-commentaire\">espace commentaire</a>";
    }
    
}

/**
 * Vérifie la validité des paramètres reçus dans l'URL, renvoie la date affichée ou l'erreur détectée
 *
 * La date affichée est initialisée avec la date courante ou actuelle.
 * Les éventuels paramètres jour, mois, annee, reçus dans l'URL, permettent respectivement de modifier le jour, le mois, et l'année de la date affichée.
 *
 * @return int|string      string en cas d'erreur, int représentant la date affichée au format AAAAMMJJ sinon
 */
function dateConsulteeL(): int|string
{
    if (!parametresControle('GET', [], ['jour', 'mois', 'annee'])) {
        return 'Nom de paramètre invalide détecté dans l\'URL.';
    }

    // date d'aujourd'hui
    list($jour, $mois, $annee) = getJourMoisAnneeFromDate(DATE_AUJOURDHUI);

    // vérification si les valeurs des paramètres reçus sont des chaînes numériques entières
    foreach ($_GET as $cle => $val) {
        if (!estEntier($val)) {
            return 'Valeur de paramètre non entière détectée dans l\'URL.';
        }
        // modification du jour, du mois ou de l'année de la date affichée
        $$cle = (int) $val;
    }

    if ($annee < 1000 || $annee > 9999) {
        return 'La valeur de l\'année n\'est pas sur 4 chiffres.';
    }
    if (!checkdate($mois, $jour, $annee)) {
        return "La date demandée \"$jour/$mois/$annee\" n'existe pas.";
    }
    if ($annee < ANNEE_MIN) {
        return 'L\'année doit être supérieure ou égale à ' . ANNEE_MIN . '.';
    }
    if ($annee > ANNEE_MAX) {
        return 'L\'année doit être inférieure ou égale à ' . ANNEE_MAX . '.';
    }
    return $annee * 10000 + $mois * 100 + $jour;
}
