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
if (!estAuthentifie()){
    header("Location: {$_SESSION['pagePrecedente']}");
    exit();
}


// si formulaires soumis, traitement des demandes de modification

// infos personnelles
if (isset($_POST['btnModifInfo'])) {
    $erreursInfo = traitementModifInfo();
}else{
    $erreursInfo = null;
}

// infos de connexion
if (isset($_POST['btnModifConnexion'])) {
    $erreursConnexion = traitementModifConnexion();
}
else{
    $erreursConnexion = null;
}

/*------------------------- Etape 2 --------------------------------------------
- génération du code HTML de la page
------------------------------------------------------------------------------*/

// génération de la page
affEntete('Mon espace');
affNav();

affMonEspace($erreursInfo, $erreursConnexion);


affPiedDePage();

// facultatif car fait automatiquement par PHP
ob_end_flush();




function affModifInfo(?array $err){
    if (isset($_POST['btnModifInfo'])){
        $values = htmlProtegerSorties($_POST);
    }
    else{
        $values['nom'] = $values['prenom'] = $values['email'] = '';
    }
    


    echo 
        '<label for="modifInfo-button" class="monEspace-modif-label">Modifier mes informations personnels</label>',
        '<section id="monEspace-modifInfoForm">';
    if (is_array($err)) {
        echo    '<div class="error">Les erreurs suivantes ont été relevées lors de votre inscription :',
                    '<ul>';
        foreach ($err as $e) {
            echo        '<li>', $e, '</li>';
        }
        echo        '</ul>',
                '</div>';
    }
    echo
        '<input type="radio" name="modif-button" id="modifInfo-button" class="monEspace-modif-button"',
            (isset($_POST['btnModifInfo']) ? 'checked' : ''),
        '>',
        '<form method="post" action="mon_espace.php">',
            '<table>';
affLigneInput('Nouveau nom :', array('type' => 'text', 'name' => 'nom', 'value' => $values['nom'], 'required' => null));
affLigneInput('Nouveau prénom :', array('type' => 'text', 'name' => 'prenom', 'value' => $values['prenom'], 'required' => null));
affLigneInput('Nouvelle adresse email :', array('type' => 'email', 'name' => 'email', 'value' => $values['email'], 'required' => null));

echo
                '<tr>',
                    '<td colspan="2">',
                        '<input type="submit" name="btnModifInfo" value="Valider">',
                        '<input type="reset" value="Réinitialiser">',
                    '</td>',
                '</tr>',
            '</table>',
        '</form>',
        '</section>';
}

function affModifConnexion(?array $err){
    if (isset($_POST['btnModifConnexion'])){
        $values = htmlProtegerSorties($_POST);
    }
    else{
        $values['login'] = '';
    }

    echo 
        '<label for="modifConnexion-button" class="monEspace-modif-label">Modifier mes informations de connexion</label>',
        '<section id="monEspace-modifConnexionForm">';
        if (is_array($err)) {
            echo    '<div class="error">Les erreurs suivantes ont été relevées lors de votre inscription :',
                        '<ul>';
            foreach ($err as $e) {
                echo        '<li>', $e, '</li>';
            }
            echo        '</ul>',
                    '</div>';
        }
        echo
        '<input type="radio" name="modif-button" id="modifConnexion-button" class="monEspace-modif-button"',
            (isset($_POST['btnModifConnexion']) ? 'checked' : ''),
        '>',
        '<form method="post" action="mon_espace.php">',
            '<table>';
    affLigneInput(  'Nouveau login :', array('type' => 'text', 'name' => 'login', 'value' => $values['login'],
            'placeholder' => LMIN_LOGIN . ' à '. LMAX_LOGIN . ' lettres minuscules ou chiffres', 'required' => null));
    affLigneInput(  'Nouveau mot de passe :', array('type' => 'password', 'name' => 'passe1', 'value' => '',
            'placeholder' => LMIN_PASSWORD . ' caractères minimum', 'required' => null));
    affLigneInput('Répétez le mot de passe :', array('type' => 'password', 'name' => 'passe2', 'value' => '', 'required' => null));

echo
                '<tr>',
                    '<td colspan="2">',
                        '<input type="submit" name="btnModifConnexion" value="Valider">',
                        '<input type="reset" value="Réinitialiser">',
                    '</td>',
                '</tr>',
            '</table>',
        '</form>',
        '</section>';
}


// traitement des demandes de modification

// infos personnelles
function traitementModifInfo() : array|null{
    $login = $_SESSION['usLogin'];
    
    /* Toutes les erreurs détectées qui nécessitent une modification du code HTML sont considérées comme des tentatives de piratage 
    et donc entraînent l'appel de la fonction sessionExit() */

    if( !parametresControle('post', ['nom', 'prenom', 'email', 'btnModifInfo'])) {
        sessionExit();   
    }

    $erreurs = [];

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

    // si erreurs --> retour
    if (count($erreurs) > 0) {
        return $erreurs;   //===> FIN DE LA FONCTION
    }


    // ouverture de la connexion à la base 
    $bd = bdConnect();

    // protection des entrées
    $login2 = mysqli_real_escape_string($bd, $login);
    $email2 = mysqli_real_escape_string($bd, $email);
    $sql = "SELECT usMail FROM usager WHERE usLogin != '{$login2}' AND usMail = '{$email2}'";
    $res = bdSendRequest($bd, $sql);

    while($tab = mysqli_fetch_assoc($res)) {
        if ($tab['usMail'] == $email){
            $erreurs[] = 'L\'adresse email existe déjà pour un autre utilisateur.';
        }
    }
    mysqli_free_result($res);

    // si erreurs --> retour
    if (count($erreurs) > 0) {
        // fermeture de la connexion à la base de données
        mysqli_close($bd);
        return $erreurs;   //===> FIN DE LA FONCTION
    }

    $nom = mysqli_real_escape_string($bd, $nom);
    $prenom = mysqli_real_escape_string($bd, $prenom);

    // les valeurs sont écrites en respectant l'ordre de création des champs dans la table usager
    $sql = "UPDATE usager
            SET usNom = '{$nom}', usPrenom = '{$prenom}', usMail = '{$email2}'
            WHERE usLogin = '{$login2}'";
        
    bdSendRequest($bd, $sql);

    // fermeture de la connexion à la base de données
    mysqli_close($bd);

    return null;
}

// info connexion 
function traitementModifConnexion(): array|null {
    
    /* Toutes les erreurs détectées qui nécessitent une modification du code HTML sont considérées comme des tentatives de piratage 
    et donc entraînent l'appel de la fonction sessionExit() */

    if( !parametresControle('post', ['login', 'passe1', 'passe2', 'btnModifConnexion'])) {
        sessionExit();   
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

    // si erreurs --> retour
    if (count($erreurs) > 0) {
        return $erreurs;   //===> FIN DE LA FONCTION
    }

    // on verrifie que le login change
    if($login !== $_SESSION["usLogin"]){

        // ouverture de la connexion à la base 
        $bd = bdConnect();

        // protection des entrées
        $login2 = mysqli_real_escape_string($bd, $login);
        $sql = "SELECT usLogin FROM usager WHERE usLogin = '{$login2}'";
        $res = bdSendRequest($bd, $sql);

        while($tab = mysqli_fetch_assoc($res)) {
            if ($tab['usLogin'] == $login){
                $erreurs[] = 'Le login existe déjà pour un autre utilisateur.';
            }
        }
        mysqli_free_result($res);
    }

    // si erreurs --> retour
    if (count($erreurs) > 0) {
        // fermeture de la connexion à la base de données
        mysqli_close($bd);
        return $erreurs;   //===> FIN DE LA FONCTION
    }

    // calcul du hash du mot de passe pour enregistrement dans la base.
    $passe = password_hash($_POST['passe1'], PASSWORD_DEFAULT);

    $passe = mysqli_real_escape_string($bd, $passe);


    // les valeurs sont écrites en respectant l'ordre de création des champs dans la table usager
    $sql = "UPDATE usager
            SET usLogin = '$login', usPasse = '$passe'
            WHERE usLogin = '{$_SESSION["usLogin"]}'";    
    bdSendRequest($bd, $sql);


    // fermeture de la connexion à la base de données
    mysqli_close($bd);

    $_SESSION['usLogin'] = $login;

    return null;
}

function affStatistiques() {
    // ouverture de la connexion à la base 
    $bd = bdConnect();

    // protection des entrées
    $login = mysqli_real_escape_string($bd, $_SESSION['usLogin']);

    $sql = "SELECT 
            (SELECT COUNT(DISTINCT reDate) FROM repas 
                INNER JOIN usager ON reUsager = usID 
                WHERE usLogin = '{$login}') as nbRepas,
            (SELECT SUM(plCalories * reNbPortions) FROM plat 
                INNER JOIN repas ON rePlat = plID 
                INNER JOIN usager ON usID = reUsager
                WHERE usLogin = '{$login}') as sumCalories,
            (SELECT SUM(plCarbone * reNbPortions) FROM plat 
                INNER JOIN repas ON rePlat = plID 
                INNER JOIN usager ON usID = reUsager
                WHERE usLogin = '{$login}') as sumCarbone,
            (SELECT COUNT(*) FROM commentaire 
                INNER JOIN usager ON coUsager = usID 
                WHERE usLogin = '{$login}') as nbCommentaires,
            (SELECT SUM(coNote) FROM commentaire 
                INNER JOIN usager ON coUsager = usID 
                WHERE usLogin = '{$login}') as sumNotes";

    $res = bdSendRequest($bd, $sql);
    $tab = mysqli_fetch_assoc($res);
    $nbRepas = $tab['nbRepas'];
    $sumCalories = $tab['sumCalories'];
    $sumCarbone = $tab['sumCarbone'];
    $nbCommentaires = $tab['nbCommentaires'];
    $sumNotes = $tab['sumNotes'];
    mysqli_free_result($res);

    // fermeture de la connexion à la base de données
    mysqli_close($bd);

    if($nbCommentaires == 0) {
        $moyenneNotes = 0;
    }else {
        $moyenneNotes = floatval(number_format($sumNotes / $nbCommentaires, 2));
    }

    if ($nbRepas == 0) {
        echo "<section><p class=\"article-paragraph\">Vous n'avez pas encore pris de repas &#x1F631; ! Vous pouvez en commander dès maintenant ! Promis on ne mord pas &#x1F609;.</p></section>";
        return;
    }
    $pourcentageRepasCommentes = floatval(number_format($nbCommentaires / $nbRepas * 100, 2));
    $moyenneCalories = floatval(number_format($sumCalories / $nbRepas, 2));
    $moyenneCarbone = floatval(number_format($sumCarbone / $nbRepas, 2));
    
    $phraseNbRepas = "Depuis que vous êtes inscrit, vous avez pris <strong>{$nbRepas}</strong> repas. ";
    $phraseNbCommentaires = ($nbCommentaires == 0)? "Sur ces repas, vous n'en avez malheureusement commenté aucun. "
        : "Sur ces repas, vous avez participé à notre communauté en en commantant <strong>{$nbCommentaires}</strong>. ";
    $phrasePourcentRepasCommentes = "Cela correspond à <strong>{$pourcentageRepasCommentes}%</strong> de l'ensemble de vos repas. ";
    $phraseMerci = ($pourcentageRepasCommentes > 30)? "Merci ! " : "";
    $phraseNoteMoyenne = ($nbCommentaires == 0)? "" : "Vous avez attribué une note moyenne de <strong>{$moyenneNotes}/5</strong> à vos commentaires. ";
    $phraseDernierParagraphe = "Comme vous le savez, nous sommes très concerné par notre impacte sur la nature et votre santé. 
    C'est pourquoi, nous pouvons vous dire que vous avez consommé en moyenne <strong>{$moyenneCalories}kcal</strong> et 
    regeté <strong>{$moyenneCarbone}g</strong> de CO2 dans notre atmosphère par repas. ";


    echo "<section><p class=\"article-paragraph\">",
    $phraseNbRepas,
    $phraseNbCommentaires,
    $phrasePourcentRepasCommentes,
    $phraseMerci,
    $phraseNoteMoyenne,
    "</p><p class=\"article-paragraph\">",
    $phraseDernierParagraphe,
    "</p></section>";
}

function affDeconnexion(){
    echo "<h2> Vous nous quittez déjà ?</h2>",
    "<section><a href=\"deconnexion.php\" id=\"mon-espace-boutton-deconnexion\">Oui &#x1F61D; !</a></section>";
}
function affCommentaire() {
    echo "<h2> Vous avez un commentaire à nous faire ?</h2>",
    "<section><a href=\"commentaire.php\" id=\"ajouter-commentaire\">Mes commentaire</a></section>";
}

function affMonEspace($erreursInfo, $erreursConnexion){
    echo "<h2> Bienvenue sur votre espace personnel !</h2>",
    "<section id='monEspace-modif'>";
    affModifInfo($erreursInfo);
    affModifConnexion($erreursConnexion);
    echo "</section><h2> Curieux de vos statistiques ?</h2>";
    affStatistiques();
    affCommentaire();
    affDeconnexion();
}
