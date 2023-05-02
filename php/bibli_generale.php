<?php
/*********************************************************
 *        Bibliothèque de fonctions génériques          
 * 
 * Les régles de nommage sont les suivantes.
 * Les noms des fonctions respectent la notation camel case.
 *
 * Ils commencent en général par un terme définisant le "domaine" de la fonction :
 *  aff   la fonction affiche du code html / texte destiné au navigateur
 *  html  la fonction renvoie du code html / texte
 *  bd    la fonction gère la base de données
 *
 * Les fonctions qui ne sont utilisés que dans un seul script
 * sont définies dans le script et les noms de ces fonctions se
 * sont suffixées avec la lettre 'L'.
 *
 *********************************************************/
 
 /**
 * Arrêt du script si erreur de base de données
 *
 * Affichage d'un message d'erreur, puis arrêt du script
 * Fonction appelée quand une erreur 'base de données' se produit :
 *      - lors de la phase de connexion au serveur MySQL ou MariaDB
 *      - ou lorsque l'envoi d'une requête échoue
 *
 * @param array    $err    Informations utiles pour le débogage
 *
 * @return void
 */
function bdErreurExit(array $err):void {
    ob_end_clean(); // Suppression de tout ce qui a pu être déja généré

    echo    '<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8">',
            '<title>Erreur',
            IS_DEV ? ' base de données': '', '</title>',
            '</head><body>';
    if (IS_DEV){
        // Affichage de toutes les infos contenues dans $err
        echo    '<h4>', $err['titre'], '</h4>',
                '<pre>',
                    '<strong>Erreur mysqli</strong> : ',  $err['code'], "\n",
                    $err['message'], "\n";
        if (isset($err['autres'])){
            echo "\n";
            foreach($err['autres'] as $cle => $valeur){
                echo    '<strong>', $cle, '</strong> :', "\n", $valeur, "\n";
            }
        }
        echo    "\n",'<strong>Pile des appels de fonctions :</strong>', "\n", $err['appels'],
                '</pre>';
    }
    else {
        echo 'Une erreur s\'est produite';
    }

    echo    '</body></html>';

    if (! IS_DEV){
        // Mémorisation des erreurs dans un fichier de log
        $fichier = @fopen('error.log', 'a');
        if($fichier){
            fwrite($fichier, '['.date('d/m/Y').' '.date('H:i:s')."]\n");
            fwrite($fichier, $err['titre']."\n");
            fwrite($fichier, "Erreur mysqli : {$err['code']}\n");
            fwrite($fichier, "{$err['message']}\n");
            if (isset($err['autres'])){
                foreach($err['autres'] as $cle => $valeur){
                    fwrite($fichier,"{$cle} :\n{$valeur}\n");
                }
            }
            fwrite($fichier,"Pile des appels de fonctions :\n");
            fwrite($fichier, "{$err['appels']}\n\n");
            fclose($fichier);
        }
    }
    exit(1);        // ==> ARRET DU SCRIPT
}


//____________________________________________________________________________
/**
 *  Ouverture de la connexion à la base de données en gérant les erreurs.
 *
 *  En cas d'erreur de connexion, une page "propre" avec un message d'erreur
 *  adéquat est affiché ET le script est arrêté.
 *
 *  @return mysqli  objet connecteur à la base de données
 */
function bdConnect(): mysqli {
    // pour forcer la levée de l'exception mysqli_sql_exception
    // si la connexion échoue
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    try{
        $conn = mysqli_connect(BD_SERVER, BD_USER, BD_PASS, BD_NAME);
    }
    catch(mysqli_sql_exception $e){
        $err['titre'] = 'Erreur de connexion';
        $err['code'] = $e->getCode();
        // $e->getMessage() est encodée en ISO-8859-1, il faut la convertir en UTF-8
        $err['message'] = mb_convert_encoding($e->getMessage(), 'UTF-8', 'ISO-8859-1');
        $err['appels'] = $e->getTraceAsString(); //Pile d'appels
        $err['autres'] = array('Paramètres' =>   'BD_SERVER : '. BD_SERVER
                                                    ."\n".'BD_USER : '. BD_USER
                                                    ."\n".'BD_PASS : '. BD_PASS
                                                    ."\n".'BD_NAME : '. BD_NAME);
        bdErreurExit($err); // ==> ARRET DU SCRIPT
    }
    try{
        //mysqli_set_charset() définit le jeu de caractères par défaut à utiliser lors de l'envoi
        //de données depuis et vers le serveur de base de données.
        mysqli_set_charset($conn, 'utf8');
        return $conn;     // ===> Sortie connexion OK
    }
    catch(mysqli_sql_exception $e){
        $err['titre'] = 'Erreur lors de la définition du charset';
        $err['code'] = $e->getCode();
        $err['message'] = mb_convert_encoding($e->getMessage(), 'UTF-8', 'ISO-8859-1');
        $err['appels'] = $e->getTraceAsString();
        bdErreurExit($err); // ==> ARRET DU SCRIPT
    }
}


//____________________________________________________________________________
/**
 * Envoie une requête SQL au serveur de BdD en gérant les erreurs.
 *
 * En cas d'erreur, une page propre avec un message d'erreur est affichée et le
 * script est arrêté. Si l'envoi de la requête réussit, cette fonction renvoie :
 *      - un objet de type mysqli_result dans le cas d'une requête SELECT
 *      - true dans le cas d'une requête INSERT, DELETE ou UPDATE
 *
 * @param   mysqli              $bd     Objet connecteur sur la base de données
 * @param   string              $sql    Requête SQL
 *
 * @return  mysqli_result|bool          Résultat de la requête
 */
function bdSendRequest(mysqli $bd, string $sql): mysqli_result|bool {
    try{
        return mysqli_query($bd, $sql);
    }
    catch(mysqli_sql_exception $e){
        $err['titre'] = 'Erreur de requête';
        $err['code'] = $e->getCode();
        $err['message'] = $e->getMessage();
        $err['appels'] = $e->getTraceAsString();
        $err['autres'] = array('Requête' => $sql);
        bdErreurExit($err);    // ==> ARRET DU SCRIPT
    }
}

//___________________________________________________________________
/**
 * Teste si une valeur est une valeur entière
 *
 * @param   mixed    $x  valeur à tester
 *
 * @return  bool     true si entier, false sinon
 */
function estEntier(mixed $x):bool {
    return is_numeric($x) && ($x == (int) $x);
}


//___________________________________________________________________
/**
 * Créé une liste déroulante à partir des options passées en paramètres.
 *
 * @param string     $nom       Le nom de la liste déroulante
 * @param array      $options   Un tableau associatif donnant la liste des options sous la forme valeur => libelle
 * @param string     $default   La valeur qui doit être sélectionnée par défaut.
 *
 * @return void
 */
function affListe(string $nom, array $options, string $defaut): void {
    echo '<select name="', $nom, '">';
    foreach ($options as $valeur => $libelle) {
        echo '<option value="', $valeur, '"', (($defaut == $valeur) ? ' selected' : '') ,'>', $libelle, '</option>';
    }
    echo '</select>';
}
//___________________________________________________________________
/**
 * Créé une liste déroulante d'une suite de nombre à partir des options passées en paramètres.
 *
 * @param string     $nom       Le nom de la liste déroulante
 * @param int        $min       La valeur minimale de la liste
 * @param int        $max       La valeur maximale de la liste
 * @param int        $pas       La pas d'itération (si positif, énumération croissante, sinon décroissante)
 * @param int        $default   La valeur qui doit être sélectionnée par défaut.
 *
 * @return void
 */
function affListeNombre(string $nom, int $min, int $max, int $pas, int $defaut): void {
    echo '<select name="', $nom, '">';
    if ($pas > 0) {
        for ($i=$min; $i <= $max; $i += $pas) {
            echo '<option value="', $i, '"', (($defaut == $i) ? ' selected' : '') ,'>', $i, '</option>';
        }
    }
    else {
        for ($i=$max; $i >= $min; $i += $pas) {
            echo '<option value="', $i, '"', (($defaut == $i) ? ' selected' : '') ,'>', $i, '</option>';
        }
    }
    echo '</select>';
}

//___________________________________________________________________
/**
 * Renvoie un tableau contenant le nom des mois (utile pour certains affichages)
 *
 * @return array     Tableau à indices numériques contenant les noms des mois
 */
function getTableauMois() : array {
    return array('Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre');
}

//___________________________________________________________________
/**
 * Affiche une liste déroulante représentant les 12 mois de l'année
 *
 * @param string    $nom      Le nom de la liste déroulante (valeur de l'attribut name)
 * @param int       $defaut   Le mois qui doit être sélectionné par défaut (1 pour janvier)
 *
 * @return void
 */
function affListeMois(string $nom, int $defaut): void {
    $mois = getTableauMois();
    $m = [];
    foreach ($mois as $k => $v) {
        $m[$k+1] = mb_strtolower($v, encoding:'UTF-8');
    }
    affListe($nom, $m, $defaut);
}

//___________________________________________________________________
/**
 * Contrôle des clés présentes dans les tableaux $_GET ou $_POST - piratage ?
 *
 * Soit $x l'ensemble des clés contenues dans $_GET ou $_POST
 * L'ensemble des clés obligatoires doit être inclus dans $x.
 * De même $x doit être inclus dans l'ensemble des clés autorisées,
 * formé par l'union de l'ensemble des clés facultatives et de
 * l'ensemble des clés obligatoires. Si ces 2 conditions sont
 * vraies, la fonction renvoie true, sinon, elle renvoie false.
 * Dit autrement, la fonction renvoie false si une clé obligatoire
 * est absente ou si une clé non autorisée est présente; elle
 * renvoie true si "tout va bien"
 *
 * @param string    $tabGlobal 'post' ou 'get'
 * @param array     $clesObligatoires tableau contenant les clés
 *                  qui doivent obligatoirement être présentes
 * @param array     $clesFacultatives tableau contenant
 *                  les clés facultatives
 *
 * @return bool     true si les paramètres sont corrects, false sinon
 */
function parametresControle(string $tabGlobal, array $clesObligatoires, array $clesFacultatives = []): bool{
    $x = strtolower($tabGlobal) == 'post' ? $_POST : $_GET;

    $x = array_keys($x);
    // $clesObligatoires doit être inclus dans $x
    if (count(array_diff($clesObligatoires, $x)) > 0){
        return false;
    }
    // $x doit être inclus dans
    // $clesObligatoires Union $clesFacultatives
    if (count(array_diff($x, array_merge($clesObligatoires, $clesFacultatives))) > 0){
        return false;
    }
    return true;
}

//___________________________________________________________________
/**
 * Renvoie un tableau à indices numériques contenant le jour, le mois et l'année d'une date au format AAAAMMJJ
 *
 * @param int       $date   La date au format AAAAMMJJ
 *
 * @return array            Tableau contenant le jour, le mois et l'année
 */
function getJourMoisAnneeFromDate(int $date) : array{
    $t = [];
    $t[] = $date % 100;
    $t[] = intval(substr($date, 4, 2));
    $t[] = intdiv($date, 10000);
    return $t;
}

//_______________________________________________________________
/**
 *  Protection des sorties (code HTML généré à destination du client).
 *
 *  Fonction à appeler pour toutes les chaines provenant de :
 *      - de saisies de l'utilisateur (formulaires)
 *      - de la bdD
 *  Permet de se protéger contre les attaques XSS (Cross site scripting)
 *  Convertit tous les caractères éligibles en entités HTML, notamment :
 *      - les caractères ayant une signification spéciales en HTML (<, >, ", ', ...)
 *      - les caractères accentués
 *
 *  Si on lui transmet un tableau, la fonction renvoie un tableau où toutes les chaines
 *  qu'il contient sont protégées, les autres données du tableau ne sont pas modifiées.
 *
 *  @param  array|string  $content   la chaine à protéger ou un tableau contenant des chaines à protéger
 *  @return array|string             la chaîne protégée ou le tableau
 */
function htmlProtegerSorties(array|string $content): array|string {
    if (is_array($content)) {
        foreach ($content as &$value) {
            if (is_array($value) || is_string($value)){
                $value = htmlProtegerSorties($value);
            }
        }
        unset ($value); // à ne pas oublier (de façon générale)
        return $content;
    }
    if (is_string($content)){
        return htmlentities($content, ENT_QUOTES, encoding:'UTF-8');
    }
    return $content;
}

//___________________________________________________________________
/**
 * Affiche une ligne d'un tableau permettant la saisie d'un champ input de type 'text', 'password', 'date' ou 'email'
 *
 * La ligne est constituée de 2 cellules :
 * - la 1ère cellule contient un label permettant un "contrôle étiqueté" de l'input
 * - la 2ème cellule contient l'input
 *
 * @param string    $libelle        Le label associé à l'input
 * @param array     $attributs      Un tableau associatif donnant les attributs de l'input sous la forme nom => valeur
 * @param string    $prefixId      Le préfixe utilisé pour l'id de l'input, ce qui donne un id égal à {$prefixId}{$attributs['name']}
 */
function affLigneInput(string $libelle, array $attributs = array(), string $prefixId = 'text'): void{
    echo    '<tr>',
                '<td><label for="', $prefixId, $attributs['name'], '">', $libelle, '</label></td>',
                '<td><input id="', $prefixId, $attributs['name'], '"';

    foreach ($attributs as $cle => $value){
        echo ' ', $cle, ($value !== null ? "='{$value}'" : '');
    }
    echo '></td></tr>';
}

