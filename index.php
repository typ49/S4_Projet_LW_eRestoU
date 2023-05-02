<?php

// chargement des bibliothèques de fonctions
require_once('./php/bibli_erestou.php');
require_once('./php/bibli_generale.php');

// bufferisation des sorties
ob_start();

// démarrage ou reprise de la session
session_start();

// génération de l'entête 
affEntete('Accueil', prefixe:'.');
// génération du menu (connecté ou pas)
affNav('.');

// génération du contenu de la page
affContenuL();

// pied de page
affPiedDePage();

// envoi du buffer
ob_end_flush();


/**
 *  Fonction générant le contenu statique de la page d'accueil. 
 *
 * @return void
 */
function affContenuL(): void {
    // chaîne nowdoc
    echo <<< '_HTML_'
    <p>Finies les interminables files d'attente qui s'étiraient jusqu'à l'extérieur des bâtiments. </p>
    <p>Inspirées du système click-and-collect des grandes enseignes de la restauration rapide, les commandes du Resto-U sont désormais digitalisées pour un <strong>repas 2.0</strong> !</p>
    <section>
        <h3>eResto-U, qu'est-ce que c'est ?</h3>
        <p>Pour permettre à tous de bénéficier, malgré les conditions sanitaires, d'un repas pris dans les meilleures conditions, le <a href="https://www.crous-bfc.fr/" target="_blank">CROUS de l'Université de Franche-Comté</a> s'est associé avec le <a href="https://www.femto-st.fr/fr/Departements-de-recherche/DISC/Presentation" target="_blank">Département d'Informatique des Systèmes Complexes (DISC)</a> de l'<abbr title="Franche-Comté Électronique Mécanique Thermique et Optique - Sciences et Technologies">Institut FEMTO-ST</abbr> pour proposer une solution innovante et disruptive de prise de repas pour les étudiants. </p>
        <p>Les brillants chercheurs de l'Université de Franche-Comté ont ainsi mis au point un protocole <strong>compatible avec les mesures sanitaires COVID-19</strong> pour le service des repas au CROUS. Ce protocole s'appuie sur une application numérique simple et efficace, développée avec les dernières technologies à la mode. </p>
        <div class="technos"><img src="images/technos/html5.png" alt="Logo HTML5" id="logoHTML"><img src="images/technos/css3.png" alt="Logo CSS3" id="logoCSS"><img src="images/technos/javascript.png" alt="Logo Javascript" id="logoJS"><img src="images/technos/php.png" alt="Logo PHP" id="logoPHP"><img src="images/technos/mysql.png" alt="Logo MySQL" id="logoMySQL"><img src="images/technos/ajax.png" alt="Logo AJAX" id="logoAJAX"><img src="images/technos/pwa.png" alt="Logo Progressive Web App" id="logoPWA"></div>
    </section>
    <div id="background"></div>
    <div class="liens"><a href="#fonctionnement"><span>Le fonctionnement de eResto-U</span></a><a href="#avantages"><span>Les avantages de eResto-U</span></a><a href="#partenariat"><span>Un partenariat 100% Franc-Comtois</span></a></div>
    <section id="fonctionnement">
        <h3>Comment fonctionne eResto-U ?</h3>
        <ol>
            <li>A l'arrivée au restaurant universitaire, vous pouvez soit commander sur une borne à l'entrée, soit depuis votre smartphone avec l'application eResto-U, soit depuis le site web du service. Dans tous les cas, votre login et mot de passe ENT de l'université seront utilisés pour vous authentifier.</li>
            <li>Durant la commande, vous choisissez, en fonction de votre statut, la formule à laquelle vous avez droit, et composez votre plateau tranquillement, sans stress. Une fois votre choix effectué, la préparation du plateau débute. </li>
            <li>Vous pouvez ensuite rejoindre directement la salle pour vous installer le temps que votre plateau soit prêt.</li>
            <li>Quand le plateau est prêt, le numéro de votre commande est appelé et il apparaît sur l'écran récapitulant les commandes en attente de récupération. Si vous avez commandé avec l'application mobile, une notification apparaîtra sur votre appareil. </li>
            <li>Votre carte multi-services vous permet de récupérer votre commande en toute autonomie dans les box sécurisés et intelligents qui maintiennent votre plat au chaud. </li>
            <li>Une fois votre repas terminé, vous déposez votre plateau sur la desserte en quittant le restaurant.</li>
        </ol>
    </section>
    <section id="avantages">
        <h3>Avantages de eResto-U</h3>
        <p>Ce système présente plusieurs avantages pour ses usagers... </p>
        <ul>
            <li>Pas de file d'attente, ni pour choisir ses plats, ni à la caisse : les distances de sécurité sont respectées.</li>
            <li>Pas de bousculade dues aux nombreuses hésitations devant le large choix de mets raffinés offerts par le Resto-U.</li>
            <li>Un temps de service raccourci, qui garantit un repas chaud qui n'attend pas avant d'être consommé. </li>
            <li>Une visualisation des plats disponibles en temps réel. </li>
            <li>Des informations détaillées sur les menus avec, en plus des traditionnelles informations nutritives, le bilan carbone de votre plateau. </li>
            <li>Un historique précis de vos mémorables repas pris au Resto U. </li>
        </ul>
        <p>...tout comme pour le CROUS !</p>
        <ul>
            <li>Réorganisation des espaces dans les locaux du resto, ce qui permet ainsi d'augmenter la capacité d'accueil pour permettre au plus grand nombre d'être dans des conditions optimales pour les repas.</li>
            <li>Création d'emplois étudiants supplémentaires pour la préparation des commandes <img src="images/point-interrogation.png" alt="icone point interrogation" id="pi" width="16" height="16"><span>(dans la limite d'un budget en baisse constante)</span>.</li>
        </ul>
    </section>
    <section id="partenariat">
        <h3>Une technologie à la pointe grâce au partenariat avec le DISC de FEMTO-ST</h3>
        <p>L'application intègre d'ores et déjà un composant évolué d'intelligence artificielle, développé au sein du Département Informatique des Systèmes Complexes de FEMTO-ST, qui sera capable de pré-remplir votre plateau du jour sur la base de vos choix. </p>
        <h4>Un puissant outil d'aide à la décision</h4><img src="images/eco-friendly.png" alt="Logo eco-friendly">
        <p>Pour vous aider à suivre votre régime, ou <strong>contrôler votre bilan carbone</strong>, chaque commande récapitule son apport énergétique journalier, ainsi que son empreinte carbone. Si vous suivez un régime particulier, ou si vous vous fixez un objectif de réduction de vos émissions de CO2, l'application mobile est en mesure de composer automatiquement votre plateau pour vous accompagner dans l'atteinte de vos objectifs. </p>
        <h4>Sécurité et données personnelles</h4><img src="images/rgpd.png" alt="Logo RGPD">
        <p>Le CROUS collecte des données sur les repas pris, à des fins de statistiques, indépendamment des individus. En d'autres termes, <strong>il n'est pas question d'enregistrer les habitudes alimentaires personnelles</strong> des usagers du CROUS et encore moins de les vendre à des géants de la restauration qui sont de vrais clowns. </p>
    </section>
    <section>
        <h3>Pour résumer...</h3>

        <p>eResto-U n'a que des avantages !</p>

        <table id="bilan">
            <thead>
                <tr>
                    <th></th>
                    <th>L'ancien Resto U</th>
                    <th>Notre eResto-U</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Manger un bon repas</td>
                    <td data-check="yes">&#x2714;</td>
                    <td data-check="yes">&#x2714;</td>
                </tr>
                <tr>
                    <td>Ne pas poireauter dans une file d'attente</td>
                    <td data-check="no">&#x2718;</td>
                    <td data-check="yes">&#x2714;</td>
                </tr>
                <tr>
                    <td>Réserver son repas pour être sûr d'avoir ce que l'on veut</td>
                    <td data-check="no">&#x2718;</td>
                    <td data-check="yes">&#x2714;</td>
                </tr>
                <tr>
                    <td>Connaître et maîtriser son empreinte carbone</td>
                    <td data-check="no">&#x2718;</td>
                    <td data-check="yes">&#x2714;</td>
                </tr>
            </tbody>
        </table>

        <p>N'attendez plus pour <a href="./php/menu.php">passer commande</a> !</p>
    </section>
_HTML_;
}
