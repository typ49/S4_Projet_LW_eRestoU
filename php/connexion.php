<?php
/* ------------------------------------------------------------------------------
    Architecture de la page
    - étape 1 : vérifications diverses et traitement des soumissions
    - étape 2 : génération du code HTML de la page
------------------------------------------------------------------------------*/

// chargement des bibliothèques de fonctions
require_once('bibli_erestou.php');
require_once('bibli_generale.php');

// bufferisation des sorties
ob_start();

// démarrage ou reprise de la session
session_start();

// on enregistre la page precedente :
if (!isset($_SESSION['pagePrecedente'])){
    $_SESSION['pagePrecedente'] = $_SERVER['HTTP_REFERER'] ?? 'menu.php';
}


/*------------------------- Etape 1 --------------------------------------------
- vérifications diverses et traitement des soumissions
------------------------------------------------------------------------------*/

// si l'utilisateur est déjà authentifié
if (estAuthentifie()){
    header("Location: menu.php");
    exit();
}

// si formulaire soumis, traitement de la demande d'inscription
if (isset($_POST['btnConnexion'])) {
    $erreurs = traitementConnexion(); // ne revient pas quand les données soumises sont valides
}
else{
    $erreurs = false;
}

/*------------------------- Etape 2 --------------------------------------------
- génération du code HTML de la page
------------------------------------------------------------------------------*/

// génération de la page
affEntete('Connexion');
affNav();

affFormulaireL($erreurs);

affPiedDePage();

// facultatif car fait automatiquement par PHP
ob_end_flush();

// ----------  Fonctions locales du script ----------- //

/**
 * Contenu de la page : affichage du formulaire d'inscription
 *
 * En absence de soumission (i.e. lors du premier affichage), $err est égal à null
 * Quand l'inscription échoue, $err est un tableau de chaînes
 *
 * @param ?array    $err    Tableau contenant les erreurs en cas de soumission du formulaire, null lors du premier affichage
 *
 * @return void
 */
function affFormulaireL(bool $err): void {
    // réaffichage des données soumises en cas d'erreur, sauf les mots de passe
    if (isset($_POST['btnConnexion'])){
        $values = htmlProtegerSorties($_POST);
    }
    else{
        $values['login'] = '';
    }

    echo
        '<section>',
            '<h3>Formulaire de connexion</h3>',
            '<p>Pour vous authentifier, remplissez le formulaire ci-dessous. </p>';

    if ($err) {
        echo    '<div class="error">',
                    'Échec d\'autentification. Utilisateur inconnu ou mot de passe incorect.',
                '</div>';
    }


    echo
            '<form method="post" action="connexion.php">',
                '<table>';

    affLigneInput(  'login :', array('type' => 'text', 'name' => 'login', 'value' => $values['login'], 'required' => null));
    affLigneInput('Mot le mot de passe :', array('type' => 'password', 'name' => 'passe', 'value' => '', 'required' => null));

    echo
                    '<tr>',
                        '<td colspan="2">',
                            '<input type="submit" name="btnConnexion" value="Se connecter">',
                            '<input type="reset" value="Annuler">',
                        '</td>',
                    '</tr>',
                '</table>',
            '</form>',
            '<p>Pas encore inscrit ? N\'attendez pas, <a href="inscription.php">inscrivez-vous</a> !</p>';
        '</section>';
}



function traitementConnexion(): bool {
    
    /* Toutes les erreurs détectées qui nécessitent une modification du code HTML sont considérées comme des tentatives de piratage 
    et donc entraînent l'appel de la fonction sessionExit() */

    if( !parametresControle('post', ['login', 'passe', 'btnConnexion'])) {
        sessionExit(); 
    }

    $erreurs = false;

    // vérification du login
    $login = $_POST['login'] = trim($_POST['login']);

    if (!preg_match('/^[a-z][a-z0-9]{' . (LMIN_LOGIN - 1) . ',' .(LMAX_LOGIN - 1). '}$/u',$login)) {
        return true;
    }

    $nb = mb_strlen($_POST['passe'], encoding:'UTF-8');
    if ($nb < LMIN_PASSWORD || $nb > LMAX_PASSWORD){
        return true;
    }



    // ouverture de la connexion à la base 
    $bd = bdConnect();

    // protection des entrées
    $login2 = mysqli_real_escape_string($bd, $login);

    $passe = mysqli_real_escape_string($bd, $_POST['passe']);

    $sql = "SELECT usID, usPasse FROM usager WHERE usLogin = '{$login2}'";
    $res = bdSendRequest($bd, $sql);


    $tab = mysqli_fetch_assoc($res);
    
    if (password_verify($passe, $tab['usPasse'])){
        $_SESSION['usID'] = $tab['usID'];
        $_SESSION['usLogin'] = $login;
    }else{
        return true;
    }
    mysqli_free_result($res);
    mysqli_close($bd);

    $_SESSION['usID'] = $tab['usID'];
    $_SESSION['usLogin'] = $login;

    header("Location: {$_SESSION['pagePrecedente']}");
    exit();
}
