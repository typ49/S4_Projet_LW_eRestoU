<?php
// chargement des bibliothèques de fonctions
require_once('bibli_generale.php');
require_once('bibli_erestou.php');


// bufferisation des sorties
ob_start();

// démarrage ou reprise de la session
session_start();
$bd = bdConnect();
$erreur = array();
function uploadImage($bd, $erreur) {
    if (empty($_FILES['fileToUpload']['name'])) {
        //redirection vers la page d'ajout de commentaire
        $erreur = traitement_commentaire($bd, $_SESSION['usID'], $erreur);
        header("location:./commentaire.php");
        exit;
    }

    $coDateRepas = $_POST['dateRepas'];
    $coUsager = $_SESSION['usID']; 

    // Remplacez les caractères non désirés dans les chaînes pour qu'ils soient sûrs à utiliser dans un nom de fichier
    $coDateRepas = preg_replace('/[^A-Za-z0-9_\-]/', '_', $coDateRepas);
    $coUsager = preg_replace('/[^A-Za-z0-9_\-]/', '_', $coUsager);

    // Construisez le nouveau nom de fichier
    $newFileName = $coDateRepas . '_' . $coUsager . '.jpg';

    $target_dir = "../upload/"; // le dossier où l'image sera stockée
    $target_file = $target_dir . $newFileName; // le chemin du fichier à être chargé
    $uploadOk = 1;
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

    // Vérifiez si le fichier image est une image réelle ou une fausse image
    if(isset($_POST["submit"])) {
        $check = getimagesize($_FILES["fileToUpload"]["tmp_name"]);
        if($check !== false) {
            echo "Le fichier est une image - " . $check["mime"] . ".";
            $uploadOk = 1;
        } else {
            $erreur[] =  "Le fichier n'est pas une image.";
            $uploadOk = 0;
        }
    }

    // Vérifiez si le fichier existe déjà
    if (file_exists($target_file)) {
        $erreur[] = "Désolé, le fichier existe déjà.";
        $uploadOk = 0;
    }

    // Vérifiez la taille du fichier
    if ($_FILES["fileToUpload"]["size"] > 100000) { // Taille maximum de 100ko
        $erreur[] = "Désolé, votre fichier est trop grand.";
        $uploadOk = 0;
    }

    // Autoriser certains formats de fichier
    if($imageFileType != "jpg" && $imageFileType != "jpeg") {
        $erreur[] = "Désolé, seuls les fichiers JPG et JPEG sont autorisés.";
        $uploadOk = 0;
    }

    // Vérifiez si $uploadOk est défini sur 0 par une erreur
    if ($uploadOk == 0) {
        $erreur[] = "Désolé, votre fichier n'a pas été chargé.";

    // si tout est correct, essayez de charger le fichier
    } else {
        // L'image temporaire est redimensionnée et enregistrée sous le nom formatté directement
        if (resizeImage($_FILES["fileToUpload"]["tmp_name"], $target_file, 90)) {
            header("location:./commentaire.php");
        } else {
            $erreur[] = "Désolé, il y a eu une erreur lors du chargement de votre fichier.";
        }
    }

    $erreur = traitement_commentaire($bd, $_SESSION['usID'], $erreur);
}


// fermeture de la connexion à la base de données
mysqli_close($bd);

//_______________________________________________________________
function traitement_commentaire($bd, $id, $erreur) {

    verifierTexte($_POST['commentaire'], 'commentaire', $erreur, 1000);
    //vérifier piratage du texte
    if (preg_match('/[<>]/', $_POST['commentaire'])) {
        $erreur[] = 'Vous avez utilisé des caractères interdits';
        return;
    }

    
    // vérifier piratage de la note
    if (preg_match('/[<>]/', $_POST['note'])) {
        $erreur[] = 'Vous avez utilisé des caractères interdits';
        return;
    }
    // vérifier qu'il n'y a pas déjà un commentaire pour cette date
    $sql = 'SELECT * FROM commentaire WHERE coUsager = ' . $id . ' AND coDateRepas = "' . $_POST['dateRepas'] . '"';
    $res = bdSendRequest($bd, $sql);
    if (mysqli_num_rows($res) != 0) {
        return;
    }
    // ajouter le commentaire
    $commentaire = mysqli_real_escape_string($_POST['commentaire'], $bd);
    $sql = 'INSERT INTO commentaire (coUsager, coDateRepas, coTexte, coDatePublication, coNote) VALUES (' . $id . ', "' . $_POST['dateRepas'] . '", "' . $commentaire . '" , "' . DATE_AUJOURDHUI . '", "' . $_POST['note'] . '" )';
    $res = bdSendRequest($bd, $sql);
    if ($res === false) {
        $erreur[] =  'Erreur lors de l\'ajout du commentaire';
    } else {
        $erreur[] = 'Le commentaire a bien été ajouté';
    }
    return $erreur;
}
