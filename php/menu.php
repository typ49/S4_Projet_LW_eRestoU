<?php

// chargement des bibliothèques de fonctions
require_once('bibli_erestou.php');
require_once('bibli_generale.php');

// bufferisation des sorties
ob_start();

// démarrage ou reprise de la session
session_start();

// affichage de l'entête
affEntete('Menus et repas');
// affichage de la barre de navigation
affNav();
$bd = bdConnect();
// si formulaire soumis, traitement de la demande d'inscription
$valid = false;
if (isset($_POST['btnCommander'])) {
    $erreurs = traitement_commande($bd); // ne revient pas quand les données soumises sont valides
} else {
    $erreurs = null;
}
// $erreur = NULL;
// contenu de la page 
affContenuL($bd, $erreurs);
mysqli_close($bd);

// affichage du pied de page
affPiedDePage();

// fin du script --> envoi de la page 
ob_end_flush();



//_______________________________________________________________
/**
 * Génération de la navigation entre les dates
 *
 * @param  int     $date   date affichée
 *
 * @return void
 */
function affNavigationDateL(int $date): void
{
    list($jour, $mois, $annee) = getJourMoisAnneeFromDate($date);

    // on détermine le jour précédent (ni samedi, ni dimanche)
    $jj = 0;
    do {
        $jj--;
        $dateVeille = getdate(mktime(12, 0, 0, $mois, $jour + $jj, $annee));
    } while ($dateVeille['wday'] == 0 || $dateVeille['wday'] == 6);
    // on détermine le jour suivant (ni samedi, ni dimanche)
    $jj = 0;
    do {
        $jj++;
        $dateDemain = getdate(mktime(12, 0, 0, $mois, $jour + $jj, $annee));
    } while ($dateDemain['wday'] == 0 || $dateDemain['wday'] == 6);

    $dateJour = getdate(mktime(12, 0, 0, $mois, $jour, $annee));
    $jourSemaine = array('Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi');

    // affichage de la navigation pour choisir le jour affiché
    echo '<h2>',
        $jourSemaine[$dateJour['wday']], ' ',
        $jour, ' ',
        getTableauMois()[$dateJour['mon'] - 1], ' ',
        $annee,
        '</h2>',

        // on utilise un formulaire qui renvoie sur la page courante avec une méthode GET pour faire apparaître les 3 paramètres sur l'URL
        '<form id="navDate" action="menu.php" method="GET">',
        '<a href="menu.php?jour=', $dateVeille['mday'], '&amp;mois=', $dateVeille['mon'], '&amp;annee=', $dateVeille['year'], '">Jour précédent</a>',
        '<a href="menu.php?jour=', $dateDemain['mday'], '&amp;mois=', $dateDemain['mon'], '&amp;annee=', $dateDemain['year'], '">Jour suivant</a>',
        'Date : ';

    affListeNombre('jour', 1, 31, 1, $jour);
    affListeMois('mois', $mois);
    affListeNombre('annee', ANNEE_MIN, ANNEE_MAX, 1, $annee);

    echo '<input type="submit" value="Consulter">',
        '</form>';
    // le bouton submit n'a pas d'attribut name. Par conséquent, il n'y a pas d'élément correspondant transmis dans l'URL lors de la soumission
    // du formulaire. Ainsi, l'URL de la page a toujours la même forme (http://..../php/menu.php?jour=7&mois=3&annee=2023) quel que soit le moyen
    // de navigation utilisé (formulaire avec bouton 'Consulter', ou lien 'précédent' ou 'suivant')
}

//_______________________________________________________________
/**
 * Récupération du menu de la date affichée
 *
 * @param mysqli    $bd             connexion à la base de données
 * @param int       $date           date affichée
 * @param array     $menu           menu de la date affichée (paramètre de sortie)
 * @param array     $repas          liste des identifiants des plats commandés par l'utilisateur (paramètre de sortie)
 *
 * @return bool                     true si le restoU est ouvert, false sinon
 */
function bdMenuL_connect($bd, int $date, array &$menu, array &$repas): bool
{

    // Récupération des plats qui sont proposés pour le menu (boissons incluses, divers exclus)
    $sql = "SELECT plID, plNom, plCategorie, plCalories, plCarbone
            FROM plat LEFT JOIN menu ON (plID=mePlat AND meDate=$date)
            WHERE mePlat IS NOT NULL OR plCategorie = 'boisson'";
    $sql_repas = "SELECT plID FROM repas, plat WHERE rePlat=plID AND reDate=$date AND reUsager={$_SESSION['usID']}";
    // $sql = "SELECT p.plID, p.plNom, p.plCategorie, p.plCalories, p.plCarbone
    //         FROM plat p 
    //         LEFT JOIN menu m ON (p.plID=m.mePlat AND m.meDate=$date)
    //         WHERE m.mePlat IS NOT NULL OR p.plCategorie = 'boisson'
    //         OR p.plID IN (
    //             SELECT r.rePlat 
    //             FROM repas r 
    //             WHERE r.reDate=$date and r.reUsager={$_SESSION['usID']}
    //         )";


    // envoi de la requête SQL
    $res = bdSendRequest($bd, $sql);
    $res_repas = bdSendRequest($bd, $sql_repas);

    // Quand le resto U est fermé, la requête précédente renvoie tous les enregistrements de la table Plat de
    // catégorie boisson : il y en a NB_CAT_BOISSON
    if (mysqli_num_rows($res) <= NB_CAT_BOISSON) {
        // libération des ressources
        mysqli_free_result($res);
        mysqli_free_result($res_repas);
        return false; // ==> fin de la fonction bdMenuL()
    }


    // tableau associatif contenant les constituants du menu : un élément par section
    $menu = array(
        'entrees' => array(),
        'plats' => array(),
        'accompagnements' => array(),
        'desserts' => array(),
        'boissons' => array()
    );

    // liste des identifiants des plats commandés par l'utilisateur
    $repas = array();



    // parcours des ressources de menu :
    while ($tab = mysqli_fetch_assoc($res)) {
        switch ($tab['plCategorie']) {
            case 'entree':
                $menu['entrees'][] = $tab;
                break;
            case 'viande':
            case 'poisson':
                $menu['plats'][] = $tab;
                break;
            case 'accompagnement':
                $menu['accompagnements'][] = $tab;
                break;
            case 'dessert':
            case 'fromage':
                $menu['desserts'][] = $tab;
                break;
            default:
                $menu['boissons'][] = $tab;
        }
    }

    // parcours des ressources de repas :
    while ($tab = mysqli_fetch_assoc($res_repas)) {
        $repas[] = $tab['plID'];
    }

    // libération des ressources
    mysqli_free_result($res);
    mysqli_free_result($res_repas);
    return true;
}


/**
 * Récupération du menu de la date affichée
 * 
 * @param mysqli $bd
 * @param mixed $date
 * @param mixed $menu
 * @return bool
 *
 */
function bdMenuL($bd, int $date, array &$menu): bool
{

    // Récupération des plats qui sont proposés pour le menu (boissons incluses, divers exclus)
    $sql = "SELECT plID, plNom, plCategorie, plCalories, plCarbone
            FROM plat LEFT JOIN menu ON (plID=mePlat AND meDate={$date})
            WHERE mePlat IS NOT NULL OR plCategorie = 'boisson'";

    // envoi de la requête SQL
    $res = bdSendRequest($bd, $sql);


    // Quand le resto U est fermé, la requête précédente renvoie tous les enregistrements de la table Plat de
    // catégorie boisson : il y en a NB_CAT_BOISSON
    if (mysqli_num_rows($res) <= NB_CAT_BOISSON) {
        // libération des ressources
        mysqli_free_result($res);

        // fermeture de la connexion au serveur de base de  données

        return false; // ==> fin de la fonction bdMenuL()
    }


    // tableau associatif contenant les constituants du menu : un élément par section
    $menu = array(
        'entrees' => array(),
        'plats' => array(),
        'accompagnements' => array(),
        'desserts' => array(),
        'boissons' => array()
    );

    // parcours des ressources :
    while ($tab = mysqli_fetch_assoc($res)) {
        switch ($tab['plCategorie']) {
            case 'entree':
                $menu['entrees'][] = $tab;
                break;
            case 'viande':
            case 'poisson':
                $menu['plats'][] = $tab;
                break;
            case 'accompagnement':
                $menu['accompagnements'][] = $tab;
                break;
            case 'dessert':
            case 'fromage':
                $menu['desserts'][] = $tab;
                break;
            default:
                $menu['boissons'][] = $tab;
        }
    }
    // libération des ressources
    mysqli_free_result($res);
    return true;
}

/**
 * Affichage d'un des constituants du menu.
 *
 * @param  array       $p               tableau associatif contenant les informations du plat en cours d'affichage
 * @param  string      $catAff          catégorie d'affichage du plat
 * @param  array       $repas     tableau contenant les identifiants des plats commandés par l'utilisateur
 *
 * @return void
 */
function affPlatL_connect(array $p, string $catAff, array $repas, bool $valid): void
{
    $enable = 'disabled';
    $date = dateConsulteeL();
    $aujourdhui = DATE_AUJOURDHUI;
    if (is_string($date)) {
        return;
    }
    if ($date == $aujourdhui && $valid == false) {
        $enable = '';

    }

    if ($catAff != 'accompagnements') { //radio bonton
        $name = "rad$catAff";
        $type = 'radio';
    } else { //checkbox
        $name = "cbaccompagnements[]";
        $type = 'checkbox';
    }
    $id = "{$name}{$p['plID']}";

    // protection des sorties contre les attaques XSS
    $p['plNom'] = htmlProtegerSorties($p['plNom']);

    // Ajouter l'attribut "checked" si le plat a été commandé par l'utilisateur
    $is_checked = '';
    if (in_array($p['plID'], $repas, true) == true) {
        $is_checked = 'checked';
    }

    if ($p['plID'] == 0) {
        if ($valid == false) {
            echo '<input id="', $id, '" name="', $name, '" type="', $type, '" value="aucune" ', $enable, '>',
                '<label for="', $id, '">',
                '<img src="../images/repas/', $p['plID'], '.jpg" alt="nothing" title="Pas de ', $catAff, '">Pas de ', $catAff, '<br><span></span>',
                '</label>';
        }
    } else {

        echo '<input id="', $id, '" name="', $name, '" type="', $type, '" value="', $p['plID'], '" ', $is_checked, ' ', $enable, '>',
            '<label for="', $id, '">',
            '<img src="../images/repas/', $p['plID'], '.jpg" alt="', $p['plNom'], '" title="', $p['plNom'], '">',
            $p['plNom'], '<br>', '<span>', $p['plCarbone'], 'kg eqCO2 / ', $p['plCalories'], 'kcal</span>',
            '</label>';
    }
}

/**
 * Affichage d'un des constituants du menu.
 *
 * @param  array       $p               tableau associatif contenant les informations du plat en cours d'affichage
 * @param  string      $catAff          catégorie d'affichage du plat
 * @param  array       $repas     tableau contenant les identifiants des plats commandés par l'utilisateur
 *
 * @return void
 */
function affPlatL(array $p, string $catAff): void
{
    if ($catAff != 'accompagnements') { //radio bonton
        $name = "rad$catAff";
        $type = 'radio';
    } else { //checkbox
        $name = "cb$catAff";
        $type = 'checkbox';
    }
    $id = "{$name}{$p['plID']}";

    // protection des sorties contre les attaques XSS
    $p['plNom'] = htmlProtegerSorties($p['plNom']);

    // Ajouter l'attribut "checked" si le plat a été commandé par l'utilisateur

    echo '<input id="', $id, '" name="', $name, '" type="', $type, '" value="', $p['plID'], '"disabled >',
        '<label for="', $id, '">',
        '<img src="../images/repas/', $p['plID'], '.jpg" alt="', $p['plNom'], '" title="', $p['plNom'], '">',
        $p['plNom'], '<br>', '<span>', $p['plCarbone'], 'kg eqCO2 / ', $p['plCalories'], 'kcal</span>',
        '</label>';
}

function init_form_commande($date, $aujourdhui, $valid)
{
    if ($date == $aujourdhui && $valid == false) {
        echo '<p class="notice">',
            '<img src="../images/notice.png" alt="notice" width="50" height="48">',
            'Tous les plateaux sont composés avec un verre, un couteau, une fouchette et une petite cuillère.',
            '</p>',
            '<form id="formCommande" action="menu.php" method="POST">',
            '<input type="hidden" name="date" value="', $date, '">';
    }
}

function btn_valider_commande($date, $aujourdhui, $valid)
{
    if ($date == $aujourdhui && $valid == false) {
        echo '<section>',
            '<h3>Validation</h3>',
            '<p class="attention">',
            '<img src="../images/attention.png" alt="attention" width="50" height="50">',
            'Attention, une fois la commande réalisée, il n\'est pas possible de la modifier.<br>',
            'Toute commande non-récupérée sera majorée d\'une somme forfaitaire de 10 euros.',
            '</p>',
            '<p class="center">',
            '<input type="submit" name="btnCommander" value="Commander">',
            '<input type="reset" name="btnAnnuler" value="Annuler">',
            '</p>',
            '</section>',
            '</form>';
    }
}

function affSupplement($date, $aujourdhui, $valid)
{
    if ($date == $aujourdhui && $valid == false) {
        echo '<section class="bcChoix">',
            '<h3>Suppléments</h3>',
            '<label>',
            '<img src="../images/repas/38.jpg" alt="Pain" title="Pain">Pain',
            '<input type="number" min="0" max="2" name="nbPains" value="0">',
            '</label>',
            '<label>',
            '<img src="../images/repas/39.jpg" alt="Serviette en papier" title="Serviette en papier">Serviette en papier',
            '<input type="number" min="1" max="5" name="nbServiettes" value="1">',
            '</label>',
            '</section>';
    }
}

/**
 * Vérifie les erreurs et saisies de commande et ajoute un repas dans la table repas.
 * si un plat est commander, les portions d'accompagnement valent 1, sinon elles valent 1.5
 * chaque plat est enregistrer la la table repas avec le nombre de portions commandées
 * 
 * 
 * @return array<string> Liste des erreurs rencontrées
 */
function traitement_commande($bd): array
{

    if (!parametresControle('POST', ['date'], ['cbentrees', 'cbplats', 'cbdesserts', 'cbboissons', 'cbaccompagnements', 'nbPains', 'nbServiettes']) == false) {
        return array('Erreur : paramètres manquants ou invalides.');
    }
    $erreurs = array();
    $date = dateConsulteeL();
    $aujourdhui = DATE_AUJOURDHUI;
    $date = (string) $date;
    $usager = $_SESSION['usID'];

    //vérifie Si l'utilisateur à déjà commander pour ce jour dans la base de données avant d'insérer
    $sql = "SELECT reDate FROM repas WHERE reDate=$aujourdhui AND reUsager=$usager";
    $res = bdSendRequest($bd, $sql);
    if (mysqli_num_rows($res) > 0) {
        return $erreurs;
    }

    if ($date != $aujourdhui) {
        $erreurs[] = 'Vous ne pouvez pas commander un repas pour une autre date que celle d\'aujourd\'hui';
        return $erreurs;
    }

    if (!isset($_POST["cbaccompagnements"])) {
        array_push($erreurs, 'Vous devez choisir un accompagnement');
    }

    if (!isset($_POST["radentrees"]) || !isset($_POST["radplats"]) || !isset($_POST["raddesserts"])) {
        array_push($erreurs, 'Si vous ne voulez pas de entrées/plats/desserts, veuillez choisir "Pas de entrées/plats/desserts"');
    }

    if (!isset($_POST["radboissons"])) {
        array_push($erreurs, 'Vous devez choisir une boisson');
    }

    //si il y a des erreurs, retourne $erreur
    if (count($erreurs) > 0) {
        return $erreurs;
    }


    $nbPortions = array();
    $nbPortions['radentrees'] = ($_POST['radentrees'] != "aucune") ? 1 : 0;
    $nbPortions['radplats'] = ($_POST['radplats'] != "aucune") ? 1 : 0;
    $nbPortions['raddesserts'] = ($_POST['raddesserts'] != "aucune") ? 1 : 0;
    $nbPortions['radboissons'] = 1;
    $nbPortions['nbPains'] = $_POST['nbPains'];
    $nbPortions['nbServiettes'] = $_POST['nbServiettes'];


    foreach ($nbPortions as $key => $value) {
        if ($value > 0) {
            $sql = "INSERT INTO repas (reDate, rePlat, reUsager, reNbPortions) VALUES ($date, {$_POST[$key]}, $usager, $value)";
            bdSendRequest($bd, $sql);
        }
    }
    //calucle portions accompagnement
    $portionAcc = ($_POST['radplats'] != "aucune") ? 1 : 1.5;
    //répartition de la portion par accompagnement selectionner
    $portionAcc = $portionAcc / count($_POST['cbaccompagnements']);
    // ajout des accompagnements
    foreach ($_POST['cbaccompagnements'] as $value) {
        $sql = "INSERT INTO repas (reDate, rePlat, reUsager, reNbPortions) VALUES ($date, $value, $usager, $portionAcc)";
        bdSendRequest($bd, $sql);
    }

    mysqli_free_result($res);
    return $erreurs;
}



//_______________________________________________________________
/**
 * Génère le contenu de la page.
 *
 * @return void
 */
function affContenuL($bd, ?array $err): void
{

    //affiche le contenue de $_POST[cbaccompagnements[]]
    // foreach ($_POST['cbaccompagnements'] as $value) {
    //     echo $value;
    // }

    
    
    $valid = false;
    $date = dateConsulteeL();
    $aujourdhui = DATE_AUJOURDHUI;


    // vérification de si une commande à déjà été passée
    if (isset($_SESSION['usID'])) {
        $sql = "SELECT * FROM repas WHERE reDate=$date AND reUsager={$_SESSION['usID']}";
        $res = bdSendRequest($bd, $sql);
        if (mysqli_num_rows($res) > 0) {
            $valid = true;
        }
        
        mysqli_free_result($res);
    }

    // si dateConsulteeL() renvoie une erreur
    if (is_string($date)) {
        echo '<h4 class="center nomargin">Erreur</h4>',
            '<p>', $date, '</p>',
            (strpos($date, 'URL') !== false) ?
            '<p>Il faut utiliser une URL de la forme :<br>http://..../php/menu.php?jour=7&mois=3&annee=2023</p>' : '';
        return; // ==> fin de la fonction affContenuL()
    }
    // si on arrive à ce point de l'exécution, alors la date est valide

    // Génération de la navigation entre les dates 
    
    affNavigationDateL($date);
    // menu du jour
    $menu = [];
    $repas = [];
    $none = ['plID' => 0, 'plNom' => 'Pas de '];
    // si la session est ouverte
    if (isset($_SESSION['usID'])) {
        $restoOuvert = bdMenuL_connect($bd, $date, $menu, $repas);
    } else {
        $restoOuvert = bdMenuL($bd, $date, $menu);
    }

    if (!$restoOuvert) {
        echo '<p>Aucun repas n\'est servi ce jour.</p>';
        return; // ==> fin de la fonction affContenuL()
    }

    // titre h3 des sections à afficher
    $h3 = array(
        'entrees' => 'Entrée',
        'plats' => 'Plat',
        'accompagnements' => 'Accompagnement(s)',
        'desserts' => 'Fromage/dessert',
        'boissons' => 'Boisson',
        'supplements' => 'Supplément(s)'
    );
    // afficher les erreurs
    if ($err != null) {
        echo '<div class="error">Les erreurs suivantes ont été relevées lors de votre inscription :',
            '<ul>';
        foreach ($err as $e) {
            echo '<li>', $e, '</li>';
        }
        echo '</ul>',
            '</div>';
    }
    if (isset($_SESSION['usID'])) {
        init_form_commande($date, $aujourdhui, $valid);
    }
    // affichage du menu
    foreach ($menu as $key => $value) {
        echo '<section class="bcChoix"><h3>', $h3[$key], '</h3>';
        if (isset($_SESSION['usID'])) {
            if ($date == $aujourdhui && ($key == 'entrees' || $key == 'plats' || $key == 'desserts')) {
                affPlatL_connect($none, $key, $repas, $valid);
            }
            foreach ($value as $p) {
                affPlatL_connect($p, $key, $repas, $valid);
            }
        } else {
            foreach ($value as $p) {
                affPlatL($p, $key);
            }
        }

        echo '</section>';
    }
    // // affichage du bouton de validation
    if (isset($_SESSION['usID'])) {
        affSupplement($date, $aujourdhui, $valid);
        btn_valider_commande($date, $aujourdhui, $valid);
    }
    affCommentairesL($bd, true);
}