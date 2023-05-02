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

// contenu de la page 
affContenuL();

// affichage du pied de page
affPiedDePage();

// fin du script --> envoi de la page 
ob_end_flush();


//_______________________________________________________________
/**
 * Vérifie la validité des paramètres reçus dans l'URL, renvoie la date affichée ou l'erreur détectée
 *
 * La date affichée est initialisée avec la date courante ou actuelle.
 * Les éventuels paramètres jour, mois, annee, reçus dans l'URL, permettent respectivement de modifier le jour, le mois, et l'année de la date affichée.
 *
 * @return int|string      string en cas d'erreur, int représentant la date affichée au format AAAAMMJJ sinon
 */
function dateConsulteeL() : int|string {
    if (!parametresControle('GET', [], ['jour', 'mois', 'annee'])){
        return 'Nom de paramètre invalide détecté dans l\'URL.';
    }

    // date d'aujourd'hui
    list($jour, $mois, $annee) = getJourMoisAnneeFromDate(DATE_AUJOURDHUI);

    // vérification si les valeurs des paramètres reçus sont des chaînes numériques entières
    foreach($_GET as $cle => $val){
        if (! estEntier($val)){
            return 'Valeur de paramètre non entière détectée dans l\'URL.';
        }
        // modification du jour, du mois ou de l'année de la date affichée
        $$cle = (int)$val;
    }

    if ($annee < 1000 || $annee > 9999){
        return 'La valeur de l\'année n\'est pas sur 4 chiffres.';
    }
    if (!checkdate($mois, $jour, $annee)) {
        return "La date demandée \"$jour/$mois/$annee\" n'existe pas.";
    }
    if ($annee < ANNEE_MIN){
        return 'L\'année doit être supérieure ou égale à '.ANNEE_MIN.'.';
    }
    if ($annee > ANNEE_MAX){
        return 'L\'année doit être inférieure ou égale à '.ANNEE_MAX.'.';
    }
    return $annee*10000 + $mois*100 + $jour;
}
//_______________________________________________________________
/**
 * Génération de la navigation entre les dates
 *
 * @param  int     $date   date affichée
 *
 * @return void
 */
function affNavigationDateL(int $date): void{
    list($jour, $mois, $annee) = getJourMoisAnneeFromDate($date);

    // on détermine le jour précédent (ni samedi, ni dimanche)
    $jj = 0;
    do {
        $jj--;
        $dateVeille = getdate(mktime(12, 0, 0, $mois, $jour+$jj, $annee));
    } while ($dateVeille['wday'] == 0 || $dateVeille['wday'] == 6);
    // on détermine le jour suivant (ni samedi, ni dimanche)
    $jj = 0;
    do {
        $jj++;
        $dateDemain = getdate(mktime(12, 0, 0, $mois, $jour+$jj, $annee));
    } while ($dateDemain['wday'] == 0 || $dateDemain['wday'] == 6);

    $dateJour = getdate(mktime(12, 0, 0, $mois, $jour, $annee));
    $jourSemaine = array('Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi');

    // affichage de la navigation pour choisir le jour affiché
    echo '<h2>',
            $jourSemaine[$dateJour['wday']], ' ',
            $jour, ' ',
            getTableauMois()[$dateJour['mon']-1], ' ',
            $annee,
        '</h2>',

        // on utilise un formulaire qui renvoie sur la page courante avec une méthode GET pour faire apparaître les 3 paramètres sur l'URL
        '<form id="navDate" action="menu.php" method="GET">',
            '<a href="menu.php?jour=', $dateVeille['mday'], '&amp;mois=', $dateVeille['mon'], '&amp;annee=',  $dateVeille['year'], '">Jour précédent</a>',
            '<a href="menu.php?jour=', $dateDemain['mday'], '&amp;mois=', $dateDemain['mon'], '&amp;annee=', $dateDemain['year'], '">Jour suivant</a>',
            'Date : ';

    affListeNombre('jour', 1, 31, 1, $jour);
    affListeMois('mois', $mois);
    affListeNombre('annee', ANNEE_MIN, ANNEE_MAX, 1, $annee);

    echo    '<input type="submit" value="Consulter">',
        '</form>';
        // le bouton submit n'a pas d'attribut name. Par conséquent, il n'y a pas d'élément correspondant transmis dans l'URL lors de la soumission
        // du formulaire. Ainsi, l'URL de la page a toujours la même forme (http://..../php/menu.php?jour=7&mois=3&annee=2023) quel que soit le moyen
        // de navigation utilisé (formulaire avec bouton 'Consulter', ou lien 'précédent' ou 'suivant')
}

//_______________________________________________________________
/**
 * Récupération du menu de la date affichée
 *
 * @param int       $date           date affichée
 * @param array     $menu           menu de la date affichée (paramètre de sortie)
 *
 * @return bool                     true si le restoU est ouvert, false sinon
 */
function bdMenuL(int $date, array &$menu) : bool {

    // ouverture de la connexion à la base de données
    $bd = bdConnect();

    // Récupération des plats qui sont proposés pour le menu (boissons incluses, divers exclus)
    $sql = "SELECT plID, plNom, plCategorie, plCalories, plCarbone
            FROM plat LEFT JOIN menu ON (plID=mePlat AND meDate=$date)
            WHERE mePlat IS NOT NULL OR plCategorie = 'boisson'";

    // envoi de la requête SQL
    $res = bdSendRequest($bd, $sql);

    // Quand le resto U est fermé, la requête précédente renvoie tous les enregistrements de la table Plat de
    // catégorie boisson : il y en a NB_CAT_BOISSON
    if (mysqli_num_rows($res) <= NB_CAT_BOISSON) {
        // libération des ressources
        mysqli_free_result($res);
        // fermeture de la connexion au serveur de base de  données
        mysqli_close($bd);
        return false; // ==> fin de la fonction bdMenuL()
    }


    // tableau associatif contenant les constituants du menu : un élément par section
    $menu = array(  'entrees'           => array(),
                    'plats'             => array(),
                    'accompagnements'   => array(),
                    'desserts'          => array(),
                    'boissons'          => array()
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
    // fermeture de la connexion au serveur de base de  données
    mysqli_close($bd);
    return true;
}

//_______________________________________________________________
/**
 * Affichage d'un des constituants du menu.
 *
 * @param  array       $p      tableau associatif contenant les informations du plat en cours d'affichage
 * @param  string      $catAff catégorie d'affichage du plat
 *
 * @return void
 */
function affPlatL(array $p, string $catAff): void {
    if ($catAff != 'accompagnements'){ //radio bonton
        $name = "rad$catAff";
        $id = "{$name}{$p['plID']}";
        $type = 'radio';
    }
    else{ //checkbox
        $id = $name = "cb{$p['plID']}";
        $type = 'checkbox';
    }

    // protection des sorties contre les attaques XSS
    $p['plNom'] = htmlProtegerSorties($p['plNom']);

    echo    '<input id="', $id, '" name="', $name, '" type="', $type, '" value="', $p['plID'], '" disabled>',
            '<label for="', $id,'">',
                '<img src="../images/repas/', $p['plID'], '.jpg" alt="', $p['plNom'], '" title="', $p['plNom'], '">',
                $p['plNom'], '<br>', '<span>', $p['plCarbone'],'kg eqCO2 / ', $p['plCalories'], 'kcal</span>',
            '</label>';

}

//_______________________________________________________________
/**
 * Génère le contenu de la page.
 *
 * @return void
 */
function affContenuL(): void {

    $date = dateConsulteeL();

    // si dateConsulteeL() renvoie une erreur
    if (is_string($date)){
        echo    '<h4 class="center nomargin">Erreur</h4>',
                '<p>', $date, '</p>',
                (strpos($date, 'URL') !== false) ?
                '<p>Il faut utiliser une URL de la forme :<br>http://..../php/menu.php?jour=7&mois=3&annee=2023</p>':'';
        return; // ==> fin de la fonction affContenuL()
    }
    // si on arrive à ce point de l'exécution, alors la date est valide
    
    // Génération de la navigation entre les dates 
    affNavigationDateL($date);

    // menu du jour
    $menu = [];

    $restoOuvert = bdMenuL($date, $menu);

    if (! $restoOuvert){
        echo '<p>Aucun repas n\'est servi ce jour.</p>';
        return; // ==> fin de la fonction affContenuL()
    }
    
    // titre h3 des sections à afficher
    $h3 = array('entrees'           => 'Entrée',
                'plats'             => 'Plat', 
                'accompagnements'   => 'Accompagnement(s)',
                'desserts'          => 'Fromage/dessert', 
                'boissons'          => 'Boisson'
                );
    
    // affichage du menu
    foreach($menu as $key => $value){
        echo '<section class="bcChoix"><h3>', $h3[$key], '</h3>';
        foreach ($value as $p) {
            affPlatL($p, $key);
        }
        echo '</section>';
    }

}
