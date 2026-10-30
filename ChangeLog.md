# CHANGELOG NSINFO FOR [DOLIBARR ERP CRM](https://www.dolibarr.org)

## 23.8.5 - 30/10/2026
- Onglets d'administration de tous les modules NS INFO (barre contenant l'onglet ChangeLog) : même présentation que les sous-onglets (haut arrondi, fond doux au survol et sur l'onglet actif en gras, trait de couleur sous l'onglet actif), par nsinfo.css.php ; sous-onglets disponibles pour tous les modules (classes .nsinfo-subtabs / .nsinfo-subtab, .active)
- Nouveau : horloge de choix d'une heure à la manière d'Android (js/modules/timePicker.js, fonction PHP nsinfoTimePickerLoad(), fonction JavaScript nsinfoTimePicker()) : l'heure sur le cadran (anneau extérieur de 1 à 12, anneau intérieur de 13 à 00), puis les minutes, à la souris ou au doigt (appuyer, glisser, relâcher), boutons Maintenant / Effacer / Annuler / OK, feuille en bas d'écran sur mobile, sans bibliothèque ; styles dans nsinfo.css.php (.nsinfo-clock)
- Calendrier : option `single: true` de nsinfoRangePicker() pour choisir un seul jour (un mois, un clic, début = fin), pour les champs d'une seule date

## 23.8.4 - 29/10/2026
- Réglages : le type text de nsinfoSetup() préaffiche la valeur par défaut (`default`, si c'est un texte) tant que rien n'est enregistré
- Réglages : le type sellist de nsinfoSetup() accepte `addurl` (et `addtitle`) : bouton + au bout de la liste de sélection pour créer un nouvel élément
- Réglages : champ de type color de nsinfoSetup() en sélecteur de couleur natif moderne (à la place du sélecteur Dolibarr à code hexadécimal), enregistré dès le choix de la couleur
- Réglages : nsinfoSetup() enregistre automatiquement tous les champs de réglage au changement (texte, nombre, couleur, zones de texte, listes, dates), sans recharger la page (au changement du champ comme au clic sur Mettre à jour ou à la touche Entrée), avec une coche verte au bout du champ modifié une fois enregistré (affichée 1 seconde) (croix rouge en cas d'échec), pour tous les modules

## 23.8.3 - 29/10/2026
- Sécurité : nsinfoAction() exige le jeton de sécurité pour les actions GET qui modifient une constante (activer / désactiver, set_*, del_*, reset, settxt…) ; les liens des interrupteurs de réglages (fiche, champs visible / obligatoire) portent maintenant le jeton ; un lien sans jeton (modules qui n'en portent pas encore) reste accepté seulement s'il vient d'une page du même site (en-tête Referer) ; l'action listsel reste acceptée sans jeton (retour de la création d'une fiche depuis le bouton « + »)

## 23.8.2 - 27/10/2026
- Nouveau : calendrier de plage entre deux dates (js/modules/rangePicker.js, fonction PHP nsinfoRangePickerLoad(), fonction JavaScript nsinfoRangePicker()) : sélection à la souris ou au doigt par glisser d'un jour à l'autre (la plage donne les bornes début et fin) ou un clic sur le premier jour puis un clic sur le dernier, deux mois côte à côte, jours non travaillés grisés, nombre de jours, feuille en bas d'écran sur mobile, sans bibliothèque ; styles dans nsinfo.css.php (.nsinfo-cal, .nsinfo-period-chip)

## 23.8.1 - 26/10/2026
- Réglages, type warehouse : l'entrepôt créé avec le bouton « + » revient sélectionné et enregistré dans les réglages (retour de la fiche entrepôt avec l'action listsel)

## 23.8.0 - 24/10/2026
- Nouveau : dossier backport/ pour les modules NS INFO construits avec le ModuleBuilder de Dolibarr 23 : fonctions getDolDefaultContextPage(), dolBuildUrl() et getCallerInfoString() reprises de Dolibarr 23.0.4, chargées seulement si Dolibarr est antérieur à la 23 ; à inclure par le module avec dol_include_once('/nsinfo/backport/backport.inc.php') (compatibilité de ces modules à partir de Dolibarr 20)
- Menu réductible : nsinfoMenuMinimize($mainmenu, 0, …) calcule elle-même la position de l'entrée « Réduire le menu » (nouvelle fonction nsinfoMenuMinimizePosition()) : après les entrées du module, stable d'une activation à l'autre et sans collision avec l'entrée d'un autre module (Saturne…) ; corrige « Menu entry (all,2012,) already exists » à l'activation ; une position donnée par le module reste utilisée

## 23.7.2 - 22/10/2026
- Réglages, type link : option 'row' => 1 pour afficher le lien dans une ligne du tableau (libellé avec info-bulle 'Tooltip', texte 'linklabel', picto 'picto', 'col', 'sametab' pour ouvrir dans le même onglet) ; sans 'row', affichage inchangé
- Réglages, type seltags (catégorie) : valeur enregistrée (actions listsel, ou multilistsel avec 'multiple' => 1) et présélectionnée, liste simple par défaut, bouton « + » pour créer une catégorie du type ('typtag') puis revenir aux réglages (droit de créer des catégories)

## 23.7.1 - 22/10/2026
- Réglages : titres de colonnes « Paramètre » / « Valeur » en gras (css/nsinfo.css.php)
- Réglages : nouvelle classe « nssep » pour le type sep ('class' => 'nssep') : séparateur bien visible, trait violet au-dessus, fond léger, texte violet en gras
- Correction : réglages, balise </td> en trop sur les types bool, bool3 et rec (HTML du tableau)

## 23.7.0 - 11/10/2026
- Nouveau : zone de dépôt de fichiers nsinfoDropZone() (lib/nsinfo.lib.php, js/modules/dropZone.js) : glisser-déposer ou clic, envoi immédiat par formulaire ; libellés dans les 5 langues
- Nouveau réglage « Menu réduit : afficher les entrées des menus déroulants » : en menu réduit, les icônes des entrées des groupes restent affichées (désactivé : seules les entrées principales)
- Menu réduit à ses icônes : entrées de même largeur, centrées dans le menu, icône au milieu (css/nsinfo.css.php)
- Nouveau : groupes de menus déroulants, fonctions nsinfoMenuGroupParent() et nsinfoMenuGroupChild() (lib/nsinfo.lib.php) à ajouter au « prefix » des entrées de menu de gauche : les entrées d'un groupe sont rangées, avec leurs sous-menus, sous l'entrée parente, qui reçoit une flèche pour les déplier / replier avec ses propres sous-menus (une entrée parente seule rend ses sous-menus déroulants ; option pour afficher le groupe sous le bloc de l'entrée parente plutôt qu'à l'intérieur) ; choix mémorisé dans le navigateur, ouvert d'office sur une page du groupe ; masqué quand le menu est réduit à ses icônes, seules les entrées principales restent (js/modules/menu.js, css/nsinfo.css.php)

## 23.6.0 - 11/10/2026
- Nouveau : fonction nsinfoMenuMinimize() (lib/nsinfo.lib.php) et script js/modules/menu.js : entrée de menu « Réduire le menu » pour les modules, qui réduit le menu de gauche à ses icônes ou le rétablit, choix mémorisé dans le navigateur (même technique que le module Saturne) ; libellés dans les 5 langues ; réactiver le module pour charger le script ; en mode réduit, les sous-menus (sans icône) et les séparateurs sont masqués et les blocs resserrés, sans espacement vertical

## 23.5.1 - 11/10/2026
- Correction : réglages, balise de fin de ligne du tableau imprimée deux fois (types sep, tabdeb, tabdeb2) ou sans ligne ouverte (tabfin, hidden, titletab, titletabdesc, link)
- Correction : réglages, types selectsocid et seluser, options typecompany et showempty ignorées (variable $desk au lieu de $desc)
- Langue française : en-tête de licence du fichier object.lang ; « statut » au lieu de « status »

## 23.5.0 - 10/10/2026
- Nouveau : traductions allemande, italienne et espagnole ; traduction anglaise complétée (fichier object.lang, textes du module et de la page À propos à la place des textes d'exemple) ; titre de l'accueil du module (NsinfoArea) traduit
- Correction : réglages, type boolall (« Tout activer / tout désactiver »), balise de fin de ligne du tableau imprimée deux fois

## 23.4.0 - 04/10/2026
- Nouveau logo du module (boule NS INFO + pièces de puzzle), aussi devant les onglets Réglages, ChangeLog et À propos

## 23.3.1 - 01/10/2026
- Réglages : option « Tout activer / tout désactiver » pour un groupe d'interrupteurs (type boolall)

## 23.3.0 - 28/09/2026
- Selecteur de tags

## 23.2.3 - 25/08/2026
- fetch_extrafield()

## 23.2.2 - 25/08/2026
- Correction PHP8

## 23.2.1 - 03/08/2026
- Forcer le CSS pour les status

## 23.2.0 - 03/08/2026
- Changement URL Ticket

## 23.1.2 - 30/07/2026
- Correction ajout  de fichier zone document (correction css)

## 23.1.1 - 21/07/2026
- Correction Warning PHP 8

## 23.1.0 - 12/07/2026
- Ajout de gestion d'import d'image

## 23.0.3 - 09/07/2026
-  Fix #20: setup.lib.php warning si picto non défini

## 23.0.2 - 09/06/2026
- Ajout type textarea2

## 23.0.1 - 14/05/2026
- Correction en lien avec le module BomPdf

## 23.0.0 - 07/05/2026
- Php8 Correction SelectDate

## 22.0.2 - 17/04/2026
- Ajout 'picto' sur 'titletab'

## 22.0.0 - 28/03/2026
- Alignement des versions Numérotation NSINFO
- Option : sep fusion de colonne (argument col = nb de colonne, title = Titre, class = 'center')

## 4.2.0 - 04/03/2026
- Correction PHP 8

## 4.1.3 - 06/02/2026
- Réglage des tableaux Colonnes 1, 2 et 3
  - ('type' => 'tabdeb', 'td1'=>70, 'td2'=>15, 'td3'=>15)

## 4.1.2 - 03/02/2026
- Ajout d'un option de saut de ligne dans les configuration type=>sep

## 4.1.1 - 02/01/2026
- Ajout d'un affichage d'unité dans les dimensions de paramétrage ('unit'=>'px')

## 4.1.0 - 21/12/2025
- Ajout d'une hidden value pour les tableaux des paramètres

## 4.0.9 - 18/12/2025
- Ajout d'une option de sélection multiple dans une liste

## 4.0.8 - 16/12/2025
- Option date dans les réglages

## 4.0.7 - 09/12/2025
- Ajout d'une traduction

## 4.0.6 - 25/11/2025
- Ajout d'un champ date dans les réglages

## 4.0.5 - 05/08/2025
- Ajout du numéro de version du Framework dans la déclaration d'incident des autres modules
- Ajout du numéro de version du Framework dans la page "A propos" des autres modules

## 4.0.4 - 25/06/2025
- Correction Bool3 (Token)

## 4.0.3 - 24/06/2025
- Ajout d'un type bool (bool3) permettant de switcher une information

## 4.0.2 - 15/06/2025
- Intégration à GitHub

## 4.0.1 - 23/05/2025
- Verif version Dolibarr pour la fonction isModEnabled

## 4.0.0 - 15/05/2025
- Création d'un réglages
- Options:
  - Afficher la version du module visible (si possible)
  - Désactiver la recherche de la version des modules

- Ajout d'une fonctionnalité permettant au module GMAO d'accèder à la partie admin avec un droit particulier

## 3.0.1 - 23/04/2025
- Ajout d'un  champ textarera pour les réglages

## 3.0.0 - 06/04/2025
- Mise à jour des info SARL
- Changement de l'icône du ChangeLog
- Modification de la partie Support
- Modification de l'A Propos (page commune des modules)

## 2.1.1
- Ajout retro fonctionnalité

## 2.1.0
- MAJ PHP 8

## 2.0.15
- Correction A propos pour Factory

## 2.0.14
- Personnalisation ChangeLog & A propos en fonction du module

## 2.0.13
- Correction Php8.2

## 2.0.12
- Menu permettant d'activer ou non des champs

## 2.0.11
- Ajout bouton modifier avec action specifique ('type' => 'rec', 'btupdate'=>'btupdate')

## 2.0.10
- Ajout de fonction au niveau de l'admin

## 2.0.9
- Intégration des spécifiques modules

## 2.0.8
- Fix Css

## 2.0.7
- Modification onglet A propos

## 2.0.6
- Réglage supplémentaire sélection de produit

## 2.0.5
- Réglage entrepot

## 2.0.4
- Ajout de fonction de gestion de mail

## 2.0.3
-Ajout des fonctions de dumpn vardtest et var2d si variables divers NSINFODEBBUG = 1
dol_include_once('/nsinfo/lib/phpversif.lib.php');

## 2.0.2
- ajout d'une fonction de participation à l'à propos "NSINFOAboutDesc5"

## 2.0.1
- Ajout d'une liste "selliststring" traité en chaine

## 2.0.0
- Mise à jour pour Php8

## 1.1.10
- Ajout d'un filtre complémentaire sur liste des produits

## 1.1.9
- Ajout Champ password
- Modification du lien vers les documentations

## 1.1.8
- ajout de la liste des projets

## 1.1.7
- Suppression des appel $conf->global

## 1.1.6
- Ajout d'une sélection de produit

## 1.1.5
- Correction de la sélection par liste

## 1.1.4
- Correction de warning PHP 8

## 1.1.3
- Intégration CSS

## 1.1.2
- Ajout d'une sélection de société

## 1.1.1
- Ajout d'une sélection d'utilisateur

## 1.1.0
- PHP8

## 1.0.14
- Ajout de la selection d'un entrepôt

## 1.0.13
- Ajout fonction d'appel aux constantes

## 1.0.12
- Ajout de lien dans le Setup
- Fonction de gestion des couleurs (setColorTxtDpp, setColorDrawDpp)

## 1.0.11
- Permet de mettre de l'HTML dans les traductions des options

## 1.0.9
- Correction CSRF Option ON/OFF

## 1.0.8
- Ajout de l'accès aux incidents depuis la page "A Propos"

## 1.0.7
- Ajout du suivi de version liée au Dolistore
- Ajout ChangeLog

## 1.0.6
- Ajout de champs de type DolEditor

## 1.0.3
- Ajout d'une fonction de gestion des listes

## 1.0.2
- bibliothéque de mise en forme des réglages de module

## 1.0.0
- Initial version
