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

/*------------------------- Etape 1 --------------------------------------------
- vérifications diverses et traitement des soumissions
------------------------------------------------------------------------------*/

// si l'utilisateur est déjà authentifié
if (estAuthentifie()){
    header ('Location: menu.php');
    exit();
}

// si formulaire soumis, traitement de la demande d'inscription
if (isset($_POST['btnInscription'])) {
    $erreurs = traitementInscriptionL(); // ne revient pas quand les données soumises sont valides
}
else{
    $erreurs = null;
}

/*------------------------- Etape 2 --------------------------------------------
- génération du code HTML de la page
------------------------------------------------------------------------------*/

// génération de la page
affEntete('Inscription');
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
function affFormulaireL(?array $err): void {
    // réaffichage des données soumises en cas d'erreur, sauf les mots de passe
    if (isset($_POST['btnInscription'])){
        $values = htmlProtegerSorties($_POST);
    }
    else{
        $values['login'] = $values['nom'] = $values['prenom'] = $values['email'] = $values['naissance'] = '';
    }

    echo
        '<section>',
            '<h3>Formulaire d\'inscription</h3>',
            '<p>Pour vous inscrire, merci de fournir les informations suivantes. </p>';

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
            '<form method="post" action="inscription.php">',
                '<table>';

    affLigneInput(  'Votre login :', array('type' => 'text', 'name' => 'login', 'value' => $values['login'],
                    'placeholder' => LMIN_LOGIN . ' à '. LMAX_LOGIN . ' lettres minuscules ou chiffres', 'required' => null));
    affLigneInput(  'Votre mot de passe :', array('type' => 'password', 'name' => 'passe1', 'value' => '',
                    'placeholder' => LMIN_PASSWORD . ' caractères minimum', 'required' => null));
    affLigneInput('Répétez le mot de passe :', array('type' => 'password', 'name' => 'passe2', 'value' => '', 'required' => null));
    affLigneInput('Votre nom :', array('type' => 'text', 'name' => 'nom', 'value' => $values['nom'], 'required' => null));
    affLigneInput('Votre prénom :', array('type' => 'text', 'name' => 'prenom', 'value' => $values['prenom'], 'required' => null));
    affLigneInput('Votre adresse email :', array('type' => 'email', 'name' => 'email', 'value' => $values['email'], 'required' => null));
    affLigneInput('Votre date de naissance :', array('type' => 'date', 'name' => 'naissance', 'value' => $values['naissance'], 'required' => null));

    echo
                    '<tr>',
                        '<td colspan="2">',
                            '<input type="submit" name="btnInscription" value="S\'inscrire">',
                            '<input type="reset" value="Réinitialiser">',
                        '</td>',
                    '</tr>',
                '</table>',
            '</form>',
        '</section>';
}


/**
 * Traitement d'une demande d'inscription
 *
 * Vérification de la validité des données
 * Si on trouve des erreurs => return un tableau les contenant
 * Sinon
 *     Enregistrement du nouvel inscrit dans la base
 *     Ouverture de la session et redirection vers la page protegee.php
 * FinSi
 * Toutes les erreurs détectées qui nécessitent une modification du code HTML sont considérées comme des tentatives de piratage
 * et donc entraînent l'appel de la fonction em_sessionExit() sauf :
 * - les éventuelles suppressions des attributs required car l'attribut required est une nouveauté apparue dans la version HTML5 et
 *   nous souhaitons que l'application fonctionne également correctement sur les vieux navigateurs qui ne supportent pas encore HTML5
 * - une éventuelle modification de l'input de type date en input de type text car c'est ce que font les navigateurs qui ne supportent
 *   pas les input de type date
 *
 *  @return array    un tableau contenant les erreurs s'il y en a
 */
function traitementInscriptionL(): array {
    
    /* Toutes les erreurs détectées qui nécessitent une modification du code HTML sont considérées comme des tentatives de piratage 
    et donc entraînent l'appel de la fonction sessionExit() */

    if( !parametresControle('post', ['login', 'nom', 'prenom', 'naissance',
                                     'passe1', 'passe2', 'email', 'btnInscription'])) {
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

    // si erreurs --> retour
    if (count($erreurs) > 0) {
        return $erreurs;   //===> FIN DE LA FONCTION
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
    mysqli_free_result($res);

    // si erreurs --> retour
    if (count($erreurs) > 0) {
        // fermeture de la connexion à la base de données
        mysqli_close($bd);
        return $erreurs;   //===> FIN DE LA FONCTION
    }

    // calcul du hash du mot de passe pour enregistrement dans la base.
    $passe = password_hash($_POST['passe1'], PASSWORD_DEFAULT);

    $passe = mysqli_real_escape_string($bd, $passe);

    $dateNaissance = $annee*10000 + $mois*100 + $jour;

    $nom = mysqli_real_escape_string($bd, $nom);
    $prenom = mysqli_real_escape_string($bd, $prenom);

    // les valeurs sont écrites en respectant l'ordre de création des champs dans la table usager
    $sql = "INSERT INTO usager
            VALUES (NULL, '{$nom}', '{$prenom}', {$dateNaissance}, '{$login2}','{$passe}', '{$email2}')";
        
    bdSendRequest($bd, $sql);

    // mémorisation de l'ID dans une variable de session
    // cette variable de session permet de savoir si l'utilisateur est authentifié
    // mysqli_insert_id() retourne la valeur générée pour une colonne AUTO_INCREMENT par la dernière requête
    $_SESSION['usID'] = mysqli_insert_id($bd);

    // fermeture de la connexion à la base de données
    mysqli_close($bd);

    // mémorisation du login dans une variable de session (car affiché dans la barre de navigation sur toutes les pages)
    // enregistrement dans la variable de session du login avant passage par la fonction mysqli_real_escape_string()
    // car, d'une façon générale, celle-ci risque de rajouter des antislashs
    // Rappel : ici, elle ne rajoutera jamais d'antislash car le login ne peut contenir que des caractères alphanumériques
    $_SESSION['usLogin'] = $login;

    // redirection vers la page protegee.php : à modifier dans le projet !
    header('Location: protegee.php');
    exit(); //===> Fin du script
}
