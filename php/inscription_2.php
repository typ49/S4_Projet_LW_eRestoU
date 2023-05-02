<?php

// chargement des bibliothèques de fonctions
require_once('bibli_erestou.php');
require_once('bibli_generale.php');

// bufferisation des sorties
ob_start();

// génération de la page
affEntete('Vérification des données reçues');
affNav();

/* Toutes les erreurs détectées qui nécessitent une modification du code HTML sont considérées comme des tentatives de piratage
et donc entraînent l'appel de la fonction sessionExit() */

if( !parametresControle('post', ['login', 'nom', 'prenom', 'naissance',
                                    'passe1', 'passe2', 'email', 'btnInscription'])) {
    header('Location: ../index.php');
    exit;
}

$erreurs = [];

// vérification du login
$login = $_POST['login'] = trim($_POST['login']);

if (!preg_match('/^[a-z][a-z0-9]{' . (LMIN_LOGIN - 1) . ',' .(LMAX_LOGIN - 1). '}$/u',$login)) {
    $erreurs[] = 'Le login doit contenir entre '. LMIN_LOGIN .' et '. LMAX_LOGIN .
                ' lettres minuscules sans accents, ou chiffres, et commencer par une lettre.';
}

// vérification des mots de passe
if ($_POST['passe1'] !== $_POST['passe2']) {
    $erreurs[] = 'Les mots de passe doivent être identiques.';
}
$nb = mb_strlen($_POST['passe1'], encoding:'UTF-8');
if ($nb < LMIN_PASSWORD || $nb > LMAX_PASSWORD){
    $erreurs[] = 'Le mot de passe doit être constitué de '. LMIN_PASSWORD . ' à ' . LMAX_PASSWORD . ' caractères.';
}

// vérification des noms et prénoms
$expRegNomPrenom = '/^[[:alpha:]]([\' -]?[[:alpha:]]+)*$/u';
$nom = $_POST['nom'] = trim($_POST['nom']);
$prenom = $_POST['prenom'] = trim($_POST['prenom']);
verifierTexte($nom, 'Le nom', $erreurs, LMAX_NOM, $expRegNomPrenom);
verifierTexte($prenom, 'Le prénom', $erreurs, LMAX_PRENOM, $expRegNomPrenom);

// vérification du format de l'adresse email
$email = $_POST['email'] = trim($_POST['email']);
verifierTexte($email, 'L\'adresse email', $erreurs, LMAX_EMAIL);

// la validation faite par le navigateur en utilisant le type email pour l'élément HTML input
// est moins forte que celle faite ci-dessous avec la fonction filter_var()
// Exemple : 'l@i' passe la validation faite par le navigateur et ne passe pas
// celle faite ci-dessous
if(! filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $erreurs[] = 'L\'adresse email n\'est pas valide.';
}

// vérification de la date de naissance
if (empty($_POST['naissance'])){
    $erreurs[] = 'La date de naissance doit être renseignée.';
}
else{
    if(! preg_match('/^\d{4}(-\d{2}){2}$/u', $_POST['naissance'])){ //vieux navigateur qui ne supporte pas le type date ?
        $erreurs[] = 'la date de naissance doit être au format "AAAA-MM-JJ".';
    }
    else{
        list($annee, $mois, $jour) = explode('-', $_POST['naissance']);
        if (!checkdate($mois, $jour, $annee)) {
            $erreurs[] = 'La date de naissance n\'est pas valide.';
        }
        else if (mktime(0,0,0,$mois,$jour,$annee + AGE_MINIMUM) > time()) {
            $erreurs[] = 'Vous devez avoir au moins '. AGE_MINIMUM. ' ans pour vous inscrire.';
        }
    }
}

// si erreurs --> affichage et fin du script
if (count($erreurs) > 0) {
    echo '<div>Les erreurs suivantes ont été relevées lors de votre inscription :<ul style="list-style-type: disc">';
    foreach($erreurs as $e){
        echo '<li>', $e, '</li>';
    }
    echo '</ul></div>';
    affPiedDePage();
    exit;   //=> fin du script
}

// on vérifie si le login et l'adresse email ne sont pas encore utilisés que si tous les autres champs
// sont valides car ces 2 dernières vérifications nécessitent une connexion au serveur de base de données
// consommatrice de ressources système

// ouverture de la connexion à la base
$bd = bdConnect();

// protection des entrées
$login2 = mysqli_real_escape_string($bd, $login); // fait par principe, mais inutile ici car on a déjà vérifié que le login
                                            // ne contenait que des caractères alphanumériques
$email2 = mysqli_real_escape_string($bd, $email);
$sql = "SELECT usLogin, usMail FROM usager WHERE usLogin = '{$login2}' OR usMail = '{$email2}'";
$res = bdSendRequest($bd, $sql);

while($tab = mysqli_fetch_assoc($res)) {
    if ($tab['usLogin'] == $login){
        $erreurs[] = 'Le login existe déjà.';
    }
    if ($tab['usMail'] == $email){
        $erreurs[] = 'L\'adresse email existe déjà.';
    }
}
// Libération de la mémoire associée au résultat de la requête
mysqli_free_result($res);
// fermeture de la connexion à la base de données
mysqli_close($bd);


// si erreurs --> affichage et fin du script
if (count($erreurs) > 0) {
    echo '<div>Les erreurs suivantes ont été relevées lors de votre inscription :<ul style="list-style-type: disc">';
    foreach($erreurs as $e){
        echo '<li>', $e, '</li>';
    }
    echo '</ul></div>';
    affPiedDePage();
    exit;   //=> fin du script
}

// pas d'erreur détectée
echo '<p>Aucune erreur de saisie.</p>';
affPiedDePage();


    

