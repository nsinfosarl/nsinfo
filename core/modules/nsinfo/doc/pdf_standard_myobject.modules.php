<?php
/* Copyright (C) 2004-2014  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012  Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2008       Raphael Bertrand        <raphael.bertrand@resultic.fr>
 * Copyright (C) 2010-2014  Juanjo Menent           <jmenent@2byte.es>
 * Copyright (C) 2012       Christophe Battarel     <christophe.battarel@altairis.fr>
 * Copyright (C) 2012       Cédric Salvador         <csalvador@gpcsolutions.fr>
 * Copyright (C) 2012-2014  Raphaël Doursenaud      <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2015       Marcos García           <marcosgdf@gmail.com>
 * Copyright (C) 2017       Ferran Marcet           <fmarcet@2byte.es>
 * Copyright (C) 2018       Frédéric France         <frederic.france@netlogic.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 * or see https://www.gnu.org/
 */

/**
 *  \file       nsinfo/core/modules/nsinfo/doc/pdf_standard.modules.php
 *  \ingroup    nsinfo
 *  \brief      Fichier de classe de génération de document à partir du modèle standard
 */

dol_include_once('/nsinfo/core/modules/nsinfo/modules_myobject.php');
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';


/**
 *	Classe de gestion du modèle PDF standard_myobject
 */
class pdf_standard_myobject extends ModelePDFMyObject
{
    /**
     * @var DoliDb Database handler
     */
    public $db;

	/**
     * @var string nom du modèle
     */
    public $name;

	/**
     * @var string description du modèle (texte court)
     */
    public $description;

    /**
     * @var int     Enregistre le nom du fichier généré comme document principal lors de la génération avec ce modèle
     */
    public $update_main_doc_field;

	/**
     * @var string type de document
     */
    public $type;

	/**
     * @var array Version minimale de PHP requise par le module.
     * par ex. : PHP ≥ 5.5 = array(5, 5)
     */
	public $phpmin = array(5, 5);

	/**
     * Version de Dolibarr du document chargé
     * @var string
     */
	public $version = 'dolibarr';

     /**
     * @var int page_largeur
     */
    public $page_largeur;

	/**
     * @var int page_hauteur
     */
    public $page_hauteur;

	/**
     * @var array format
     */
    public $format;

	/**
     * @var int marge_gauche
     */
	public $marge_gauche;

	/**
     * @var int marge_droite
     */
	public $marge_droite;

	/**
     * @var int marge_haute
     */
	public $marge_haute;

	/**
     * @var int marge_basse
     */
	public $marge_basse;

    /**
	* Issuer
	* @var Societe Objet émetteur
	*/
	public $emetteur;

	/**
	 * @var bool Situation invoice type
	 */
	public $situationinvoice;


	/**
	 * @var array des colonnes du tableau du document
	 */
	public $cols;


	/**
	 *	Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
	 */
	public function __construct($db)
	{
		global $conf, $langs, $mysoc;

		// Translations
		$langs->loadLangs(array("main", "bills"));

		$this->db = $db;
		$this->name = "standard";
		$this->description = $langs->trans('DocumentModelStandardPDF');
		$this->update_main_doc_field = 1; // Enregistre le nom du fichier généré comme document principal lors de la génération avec ce modèle

		// Dimension page
		$this->type = 'pdf';
		$formatarray = pdf_getFormat();
		$this->page_largeur = $formatarray['width'];
		$this->page_hauteur = $formatarray['height'];
		$this->format = array($this->page_largeur, $this->page_hauteur);
		$this->marge_gauche = isset($conf->global->MAIN_PDF_MARGIN_LEFT) ? $conf->global->MAIN_PDF_MARGIN_LEFT : 10;
		$this->marge_droite = isset($conf->global->MAIN_PDF_MARGIN_RIGHT) ? $conf->global->MAIN_PDF_MARGIN_RIGHT : 10;
		$this->marge_haute = isset($conf->global->MAIN_PDF_MARGIN_TOP) ? $conf->global->MAIN_PDF_MARGIN_TOP : 10;
		$this->marge_basse = isset($conf->global->MAIN_PDF_MARGIN_BOTTOM) ? $conf->global->MAIN_PDF_MARGIN_BOTTOM : 10;

		$this->option_logo = 1; // Affiche le logo
		$this->option_tva = 1; // Gère l'option TVA FACTURE_TVAOPTION
		$this->option_modereg = 1; // Affiche le mode de règlement
		$this->option_condreg = 1; // Affiche les conditions de règlement
		$this->option_codeproduitservice = 1; // Affiche le code produit-service
		$this->option_multilang = 1; // Disponible en plusieurs langues
		$this->option_escompte = 1; // Affiche s'il y a eu un escompte
		$this->option_credit_note = 1; // Gère les avoirs
		$this->option_freetext = 1; // Permet l'ajout d'un texte personnalisé
		$this->option_draft_watermark = 1; // Permet l'ajout d'un filigrane sur les brouillons

		// Récupère la société émettrice
		$this->emetteur = $mysoc;
		if (empty($this->emetteur->country_code)) $this->emetteur->country_code = substr($langs->defaultlang, -2); // Par défaut, si non défini

		// Définit la position des colonnes
		$this->posxdesc = $this->marge_gauche + 1; // utilisé pour les notes et autres éléments


		$this->tabTitleHeight = 5; // hauteur par défaut

		//  Utilise le nouveau système de position des colonnes, voir  $this->defineColumnField()

		$this->tva = array();
		$this->localtax1 = array();
		$this->localtax2 = array();
		$this->atleastoneratenotnull = 0;
		$this->atleastonediscount = 0;
		$this->situationinvoice = false;
	}


    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
    /**
     *  Fonction de génération du PDF sur disque
     *
     *  @param		Object		$object				Objet à générer
     *  @param		Translate	$outputlangs		Lang output object
     *  @param		string		$srctemplatepath	Chemin complet du fichier source pour le générateur utilisant un modèle
     *  @param		int			$hidedetails		Ne pas afficher le détail des lignes
     *  @param		int			$hidedesc			Ne pas afficher la description
     *  @param		int			$hideref			Ne pas afficher la référence
     *  @return     int         	    			1=OK, 0=KO
	 */
	public function write_file($object, $outputlangs, $srctemplatepath = '', $hidedetails = 0, $hidedesc = 0, $hideref = 0)
	{
	    // phpcs:enable
	    global $user, $langs, $conf, $mysoc, $db, $hookmanager, $nblines;

	    dol_syslog("write_file outputlangs->defaultlang=".(is_object($outputlangs) ? $outputlangs->defaultlang : 'null'));

	    if (!is_object($outputlangs)) $outputlangs = $langs;
	    // Pour la compatibilité ascendante avec FPDF, force le jeu de caractères de sortie en ISO, car FPDF attend un texte encodé en ISO
	    if (!empty($conf->global->MAIN_USE_FPDF)) $outputlangs->charset_output = 'ISO-8859-1';

	    // Charge les fichiers de traduction nécessaires à la page
	    $outputlangs->loadLangs(array("main", "bills", "products", "dict", "companies"));

	    if (!empty($conf->global->PDF_USE_ALSO_LANGUAGE_CODE) && $outputlangs->defaultlang != $conf->global->PDF_USE_ALSO_LANGUAGE_CODE) {
	    	global $outputlangsbis;
	    	$outputlangsbis = new Translate('', $conf);
	    	$outputlangsbis->setDefaultLang($conf->global->PDF_USE_ALSO_LANGUAGE_CODE);
	    	$outputlangsbis->loadLangs(array("main", "bills", "products", "dict", "companies"));
	    }

	    $nblines = (is_array($object->lines) ? count($object->lines) : 0);

	    $hidetop = 0;
	    if (!empty($conf->global->MAIN_PDF_DISABLE_COL_HEAD_TITLE)) {
	        $hidetop = $conf->global->MAIN_PDF_DISABLE_COL_HEAD_TITLE;
	    }

	    // Parcourt chaque ligne pour détecter s'il y a au moins une image à afficher
	    $realpatharray = array();
	    $this->atleastonephoto = false;
	    /*
	    if (!empty($conf->global->MAIN_GENERATE_MYOBJECT_WITH_PICTURE))
	    {
	        $objphoto = new Product($this->db);

	        for ($i = 0; $i < $nblines; $i++)
	        {
	            if (empty($object->lines[$i]->fk_product)) continue;

	            $objphoto->fetch($object->lines[$i]->fk_product);
	            //var_dump($objphoto->ref);exit;
	            if (!empty($conf->global->PRODUCT_USE_OLD_PATH_FOR_PHOTO))
	            {
	                $pdir[0] = get_exdir($objphoto->id, 2, 0, 0, $objphoto, 'product').$objphoto->id."/photos/";
	                $pdir[1] = get_exdir(0, 0, 0, 0, $objphoto, 'product').dol_sanitizeFileName($objphoto->ref).'/';
	            } else {
	                $pdir[0] = get_exdir(0, 0, 0, 0, $objphoto, 'product').dol_sanitizeFileName($objphoto->ref).'/'; // par défaut
	                $pdir[1] = get_exdir($objphoto->id, 2, 0, 0, $objphoto, 'product').$objphoto->id."/photos/"; // alternative
	            }

	            $arephoto = false;
	            foreach ($pdir as $midir)
	            {
	                if (!$arephoto)
	                {
	                    $dir = $conf->product->dir_output.'/'.$midir;

	                    foreach ($objphoto->liste_photos($dir, 1) as $key => $obj)
	                    {
	                        if (empty($conf->global->CAT_HIGH_QUALITY_IMAGES))		// Si CAT_HIGH_QUALITY_IMAGES n'est pas défini, on utilise la vignette si elle existe, puis la photo originale
	                        {
	                            if ($obj['photo_vignette'])
	                            {
	                                $filename = $obj['photo_vignette'];
	                            } else {
	                                $filename = $obj['photo'];
	                            }
	                        } else {
	                            $filename = $obj['photo'];
	                        }

	                        $realpath = $dir.$filename;
	                        $arephoto = true;
	                        $this->atleastonephoto = true;
	                    }
	                }
	            }

	            if ($realpath && $arephoto) $realpatharray[$i] = $realpath;
	        }
	    }
		*/

	    //if (count($realpatharray) == 0) $this->posxpicture=$this->posxtva;

	    if ($conf->nsinfo->dir_output.'/myobject')
	    {
	        $object->fetch_thirdparty();

	        // Définition de $dir et $file
	        if ($object->specimen)
	        {
	        	$dir = $conf->nsinfo->dir_output.'/myobject';
	            $file = $dir."/SPECIMEN.pdf";
	        } else {
	            $objectref = dol_sanitizeFileName($object->ref);
	            $dir = $conf->nsinfo->dir_output.'/myobject/'.$objectref;
	            $file = $dir."/".$objectref.".pdf";
	        }
	        if (!file_exists($dir))
	        {
	            if (dol_mkdir($dir) < 0)
	            {
	                $this->error = $langs->transnoentities("ErrorCanNotCreateDir", $dir);
	                return 0;
	            }
	        }

	        if (file_exists($dir))
	        {
	            // Add pdfgeneration hook
	            if (!is_object($hookmanager))
	            {
	                include_once DOL_DOCUMENT_ROOT.'/core/class/hookmanager.class.php';
	                $hookmanager = new HookManager($this->db);
	            }
	            $hookmanager->initHooks(array('pdfgeneration'));
	            $parameters = array('file'=>$file, 'object'=>$object, 'outputlangs'=>$outputlangs);
	            global $action;
	            $reshook = $hookmanager->executeHooks('beforePDFCreation', $parameters, $object, $action); // Attention : $action et $object peuvent avoir été modifiés par certains hooks

	            // Définit nblines avec le contenu des nouvelles lignes de facture après le hook
	            $nblines = (is_array($object->lines) ? count($object->lines) : 0);

	            // Create pdf instance
	            $pdf = pdf_getInstance($this->format);
	            $default_font_size = pdf_getPDFFontSize($outputlangs); // Doit être placé après pdf_getInstance
	            $pdf->SetAutoPageBreak(1, 0);

	            $heightforinfotot = 50; // Hauteur réservée à l'affichage des infos, du total et du règlement
	            $heightforfreetext = (isset($conf->global->MAIN_PDF_FREETEXT_HEIGHT) ? $conf->global->MAIN_PDF_FREETEXT_HEIGHT : 5); // Hauteur réservée à l'affichage du texte libre sur la dernière page
	            $heightforfooter = $this->marge_basse + (empty($conf->global->MAIN_GENERATE_DOCUMENTS_SHOW_FOOT_DETAILS) ? 12 : 22); // Hauteur réservée à l'affichage du pied de page (valeur incluant la marge basse)

	            if (class_exists('TCPDF'))
	            {
	                $pdf->setPrintHeader(false);
	                $pdf->setPrintFooter(false);
	            }
	            $pdf->SetFont(pdf_getPDFFont($outputlangs));

	            // Définit le chemin du fichier PDF d'arrière-plan
                if (!empty($conf->global->MAIN_ADD_PDF_BACKGROUND))
	            {
	            	$pagecount = $pdf->setSourceFile($conf->mycompany->multidir_output[$object->entity].'/'.$conf->global->MAIN_ADD_PDF_BACKGROUND);
	                $tplidx = $pdf->importPage(1);
	            }

	            $pdf->Open();
	            $pagenb = 0;
	            $pdf->SetDrawColor(128, 128, 128);

	            $pdf->SetTitle($outputlangs->convToOutputCharset($object->ref));
	            $pdf->SetSubject($outputlangs->transnoentities("PdfTitle"));
	            $pdf->SetCreator("Dolibarr ".DOL_VERSION);
	            $pdf->SetAuthor($outputlangs->convToOutputCharset($user->getFullName($outputlangs)));
	            $pdf->SetKeyWords($outputlangs->convToOutputCharset($object->ref)." ".$outputlangs->transnoentities("PdfTitle")." ".$outputlangs->convToOutputCharset($object->thirdparty->name));
	            if (!empty($conf->global->MAIN_DISABLE_PDF_COMPRESSION)) $pdf->SetCompression(false);

	            // Définit le certificat
	            $cert = empty($user->conf->CERTIFICATE_CRT) ? '' : $user->conf->CERTIFICATE_CRT;
	            // Si l'utilisateur n'a pas de certificat, on essaie de prendre celui de la société
	            if (!$cert) {
	            	$cert = empty($conf->global->CERTIFICATE_CRT) ? '' : $conf->global->CERTIFICATE_CRT;
	            }
	            // Si un certificat est trouvé
	            if ($cert) {
	            	$info = array(
	            		'Name' => $this->emetteur->name,
	            		'Location' => getCountry($this->emetteur->country_code, 0),
	            		'Reason' => 'MYOBJECT',
	            		'ContactInfo' => $this->emetteur->email
	            	);
	            	$pdf->setSignature($cert, $cert, $this->emetteur->name, '', 2, $info);
	            }

	            $pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite); // Gauche, haut, droite

	            // New page
	            $pdf->AddPage();
	            if (!empty($tplidx)) $pdf->useTemplate($tplidx);
	            $pagenb++;

	            $top_shift = $this->_pagehead($pdf, $object, 1, $outputlangs, $outputlangsbis);
	            $pdf->SetFont('', '', $default_font_size - 1);
	            $pdf->MultiCell(0, 3, ''); // Définit l'interligne à 3
	            $pdf->SetTextColor(0, 0, 0);

	            $tab_top = 90 + $top_shift;
	            $tab_top_newpage = (empty($conf->global->MAIN_PDF_DONOTREPEAT_HEAD) ? 42 + $top_shift : 10);
	            $tab_height = 130 - $top_shift;
	            $tab_height_newpage = 150;
	            if (empty($conf->global->MAIN_PDF_DONOTREPEAT_HEAD)) $tab_height_newpage -= $top_shift;

                $nexY = $tab_top - 1;

	            // Display notes
	            $notetoshow = empty($object->note_public) ? '' : $object->note_public;
	            // Champs supplémentaires dans la note
                $extranote = $this->getExtrafieldsInHtml($object, $outputlangs);
                if (!empty($extranote))
                {
                    $notetoshow = dol_concatdesc($notetoshow, $extranote);
                }

	            $pagenb = $pdf->getPage();
	            if ($notetoshow)
	            {
					$tab_top -= 2;

	                $tab_width = $this->page_largeur - $this->marge_gauche - $this->marge_droite;
	                $pageposbeforenote = $pagenb;

	                $substitutionarray = pdf_getSubstitutionArray($outputlangs, null, $object);
	                complete_substitutions_array($substitutionarray, $outputlangs, $object);
	                $notetoshow = make_substitutions($notetoshow, $substitutionarray, $outputlangs);
	                $notetoshow = convertBackOfficeMediasLinksToPublicLinks($notetoshow);

	                $pdf->startTransaction();

	                $pdf->SetFont('', '', $default_font_size - 1);
	                $pdf->writeHTMLCell(190, 3, $this->posxdesc - 1, $tab_top, dol_htmlentitiesbr($notetoshow), 0, 1);
	                // Description
	                $pageposafternote = $pdf->getPage();
	                $posyafter = $pdf->GetY();

	                if ($pageposafternote > $pageposbeforenote)
	                {
	                    $pdf->rollbackTransaction(true);

	                    // prépare les pages pour recevoir les notes
	                    while ($pagenb < $pageposafternote) {
	                        $pdf->AddPage();
	                        $pagenb++;
	                        if (!empty($tplidx)) $pdf->useTemplate($tplidx);
	                        if (empty($conf->global->MAIN_PDF_DONOTREPEAT_HEAD)) $this->_pagehead($pdf, $object, 0, $outputlangs);
	                        // $this->_pagefoot($pdf,$object,$outputlangs,1);
	                        $pdf->setTopMargin($tab_top_newpage);
	                        // Seule fonction permettant de modifier la marge basse de la page courante pour la définir.
	                        $pdf->setPageOrientation('', 1, $heightforfooter + $heightforfreetext);
	                    }

	                    // retour au début
	                    $pdf->setPage($pageposbeforenote);
	                    $pdf->setPageOrientation('', 1, $heightforfooter + $heightforfreetext);
	                    $pdf->SetFont('', '', $default_font_size - 1);
	                    $pdf->writeHTMLCell(190, 3, $this->posxdesc - 1, $tab_top, dol_htmlentitiesbr($notetoshow), 0, 1);
	                    $pageposafternote = $pdf->getPage();

	                    $posyafter = $pdf->GetY();

	                    if ($posyafter > ($this->page_hauteur - ($heightforfooter + $heightforfreetext + 20)))	// Il ne reste pas de place pour le total et le texte libre
	                    {
	                        $pdf->AddPage('', '', true);
	                        $pagenb++;
	                        $pageposafternote++;
	                        $pdf->setPage($pageposafternote);
	                        $pdf->setTopMargin($tab_top_newpage);
	                        // Seule fonction permettant de modifier la marge basse de la page courante pour la définir.
	                        $pdf->setPageOrientation('', 1, $heightforfooter + $heightforfreetext);
	                        //$posyafter = $tab_top_newpage;
	                    }


	                    // applique le cadre des notes aux pages précédentes
	                    $i = $pageposbeforenote;
	                    while ($i < $pageposafternote) {
	                        $pdf->setPage($i);


	                        $pdf->SetDrawColor(128, 128, 128);
	                        // Draw note frame
	                        if ($i > $pageposbeforenote) {
	                            $height_note = $this->page_hauteur - ($tab_top_newpage + $heightforfooter);
	                            $pdf->Rect($this->marge_gauche, $tab_top_newpage - 1, $tab_width, $height_note + 1);
	                        } else {
	                            $height_note = $this->page_hauteur - ($tab_top + $heightforfooter);
	                            $pdf->Rect($this->marge_gauche, $tab_top - 1, $tab_width, $height_note + 1);
	                        }

	                        // Add footer
	                        $pdf->setPageOrientation('', 1, 0); // Seule fonction permettant de modifier la marge basse de la page courante pour la définir.
	                        $this->_pagefoot($pdf, $object, $outputlangs, 1);

	                        $i++;
	                    }

	                    // applique le cadre des notes à la dernière page
	                    $pdf->setPage($pageposafternote);
	                    if (!empty($tplidx)) $pdf->useTemplate($tplidx);
	                    if (empty($conf->global->MAIN_PDF_DONOTREPEAT_HEAD)) $this->_pagehead($pdf, $object, 0, $outputlangs);
	                    $height_note = $posyafter - $tab_top_newpage;
	                    $pdf->Rect($this->marge_gauche, $tab_top_newpage - 1, $tab_width, $height_note + 1);
	                } else // No pagebreak
	                {
	                    $pdf->commitTransaction();
	                    $posyafter = $pdf->GetY();
	                    $height_note = $posyafter - $tab_top;
	                    $pdf->Rect($this->marge_gauche, $tab_top - 1, $tab_width, $height_note + 1);


	                    if ($posyafter > ($this->page_hauteur - ($heightforfooter + $heightforfreetext + 20)))
	                    {
	                        // pas assez de place, il faut ajouter une page
	                        $pdf->AddPage('', '', true);
	                        $pagenb++;
	                        $pageposafternote++;
	                        $pdf->setPage($pageposafternote);
	                        if (!empty($tplidx)) $pdf->useTemplate($tplidx);
	                        if (empty($conf->global->MAIN_PDF_DONOTREPEAT_HEAD)) $this->_pagehead($pdf, $object, 0, $outputlangs);

	                        $posyafter = $tab_top_newpage;
	                    }
	                }

	                $tab_height = $tab_height - $height_note;
	                $tab_top = $posyafter + 6;
	            } else {
	                $height_note = 0;
	            }

	            // Use new auto column system
	            $this->prepareArrayColumnField($object, $outputlangs, $hidedetails, $hidedesc, $hideref);

	            // Simulation du tableau pour connaître la hauteur de la ligne de titre
			    $pdf->startTransaction();
			    $this->pdfTabTitles($pdf, $tab_top, $tab_height, $outputlangs, $hidetop);
			    $pdf->rollbackTransaction(true);

			    $nexY = $tab_top + $this->tabTitleHeight;

	            // Parcourt chaque ligne
	            $pageposbeforeprintlines = $pdf->getPage();
	            $pagenb = $pageposbeforeprintlines;
	            for ($i = 0; $i < $nblines; $i++)
	            {
	                $curY = $nexY;
	                $pdf->SetFont('', '', $default_font_size - 1); // Dans la boucle pour gérer le multipage
	                $pdf->SetTextColor(0, 0, 0);

	                // Définit la taille de l'image si besoin
	                $imglinesize = array();
	                if (!empty($realpatharray[$i])) $imglinesize = pdf_getSizeForImage($realpatharray[$i]);

	                $pdf->setTopMargin($tab_top_newpage);
	                $pdf->setPageOrientation('', 1, $heightforfooter + $heightforfreetext + $heightforinfotot); // Seule fonction permettant de modifier la marge basse de la page courante pour la définir.
	                $pageposbefore = $pdf->getPage();

	                $showpricebeforepagebreak = 1;
	                $posYAfterImage = 0;

	                if ($this->getColumnStatus('photo'))
	                {
    	                // On commence par la photo de la ligne produit
    	                if (isset($imglinesize['width']) && isset($imglinesize['height']) && ($curY + $imglinesize['height']) > ($this->page_hauteur - ($heightforfooter + $heightforfreetext + $heightforinfotot)))	// Si la photo est trop haute, on la déplace entièrement sur une nouvelle page
    	                {
    	                    $pdf->AddPage('', '', true);
    	                    if (!empty($tplidx)) $pdf->useTemplate($tplidx);
    	                    $pdf->setPage($pageposbefore + 1);

    	                    $curY = $tab_top_newpage;

							// Permet d'afficher des données sur la première page si la description est assez longue pour se répartir sur plusieurs pages
							if (!empty($conf->global->MAIN_PDF_DATA_ON_FIRST_PAGE))
								$showpricebeforepagebreak = 1;
							else $showpricebeforepagebreak = 0;
    	                }

    	                if (!empty($this->cols['photo']) && isset($imglinesize['width']) && isset($imglinesize['height']))
    	                {
    	                    $pdf->Image($realpatharray[$i], $this->getColumnContentXStart('photo'), $curY, $imglinesize['width'], $imglinesize['height'], '', '', '', 2, 300); // Utilise 300 dpi
    	                    // $pdf->Image n'augmente pas la valeur renvoyée par getY, on la sauvegarde donc manuellement
    	                    $posYAfterImage = $curY + $imglinesize['height'];
    	                }
	                }

	                // Description de la ligne produit
	                if ($this->getColumnStatus('desc'))
	                {
    	                $pdf->startTransaction();

                        $this->printColDescContent($pdf, $curY, 'desc', $object, $i, $outputlangs, $hideref, $hidedesc);
    	                $pageposafter = $pdf->getPage();

    	                if ($pageposafter > $pageposbefore)	// Il y a un saut de page
    	                {
    	                    $pdf->rollbackTransaction(true);
    	                    $pdf->setPageOrientation('', 1, $heightforfooter); // Seule fonction permettant de modifier la marge basse de la page courante pour la définir.

                            $this->printColDescContent($pdf, $curY, 'desc', $object, $i, $outputlangs, $hideref, $hidedesc);

    	                    $pageposafter = $pdf->getPage();
    	                    $posyafter = $pdf->GetY();
    	                    //var_dump($posyafter); var_dump(($this->page_hauteur - ($heightforfooter+$heightforfreetext+$heightforinfotot))); exit;
    	                    if ($posyafter > ($this->page_hauteur - ($heightforfooter + $heightforfreetext + $heightforinfotot)))	// Il ne reste pas de place pour le total et le texte libre
    	                    {
    	                        if ($i == ($nblines - 1))	// Plus de lignes, et pas de place pour afficher le total, on crée donc une nouvelle page
    	                        {
    	                            $pdf->AddPage('', '', true);
    	                            if (!empty($tplidx)) $pdf->useTemplate($tplidx);
    	                            $pdf->setPage($pageposafter + 1);
    	                        }
    	                    } else {
    	                        // Un saut de page a été trouvé
								// Permet d'afficher des données sur la première page si la description est assez longue pour se répartir sur plusieurs pages
								if (!empty($conf->global->MAIN_PDF_DATA_ON_FIRST_PAGE))
									$showpricebeforepagebreak = 1;
								else $showpricebeforepagebreak = 0;
    	                    }
    	                } else // No pagebreak
    	                {
    	                    $pdf->commitTransaction();
    	                }
	                }

                    $nexY = $pdf->GetY();
	                $pageposafter = $pdf->getPage();
	                $pdf->setPage($pageposbefore);
	                $pdf->setTopMargin($this->marge_haute);
	                $pdf->setPageOrientation('', 1, 0); // Seule fonction permettant de modifier la marge basse de la page courante pour la définir.

	                // On suppose qu'une description ou une photo trop longue a été déplacée entièrement sur la page suivante
	                if ($pageposafter > $pageposbefore && empty($showpricebeforepagebreak)) {
	                    $pdf->setPage($pageposafter); $curY = $tab_top_newpage;
	                }

	                $pdf->SetFont('', '', $default_font_size - 1); // On repositionne la police par defaut

	                // Quantity
	                // Suffisant pour 6 caractères
	                if ($this->getColumnStatus('qty'))
	                {
	                    $qty = pdf_getlineqty($object, $i, $outputlangs, $hidedetails);
	                    $this->printStdColumnContent($pdf, $curY, 'qty', $qty);
	                    $nexY = max($pdf->GetY(), $nexY);
	                }

                    // Extrafields
	                if (!empty($object->lines[$i]->array_options)) {
	                    foreach ($object->lines[$i]->array_options as $extrafieldColKey => $extrafieldValue) {
                            if ($this->getColumnStatus($extrafieldColKey))
                            {
                                $extrafieldValue = $this->getExtrafieldContent($object->lines[$i], $extrafieldColKey);
                                $this->printStdColumnContent($pdf, $curY, $extrafieldColKey, $extrafieldValue);
                                $nexY = max($pdf->GetY(), $nexY);
                            }
                        }
                    }


	                $parameters = array(
	                    'object' => $object,
	                    'i' => $i,
	                    'pdf' =>& $pdf,
	                    'curY' =>& $curY,
	                    'nexY' =>& $nexY,
	                    'outputlangs' => $outputlangs,
	                    'hidedetails' => $hidedetails
	                );
	                $reshook = $hookmanager->executeHooks('printPDFline', $parameters, $this); // Attention : $object peut avoir été modifié par le hook


	                $sign = 1;
	                // Collecte des totaux par valeur de tva dans $this->tva["taux"]=total_tva
	                $prev_progress = $object->lines[$i]->get_prev_progress($object->id);
	                if ($prev_progress > 0 && !empty($object->lines[$i]->situation_percent)) // Calcule l'avancement depuis la situation précédente
	                {
	                    if ($conf->multicurrency->enabled && $object->multicurrency_tx != 1) $tvaligne = $sign * $object->lines[$i]->multicurrency_total_tva * ($object->lines[$i]->situation_percent - $prev_progress) / $object->lines[$i]->situation_percent;
	                    else $tvaligne = $sign * $object->lines[$i]->total_tva * ($object->lines[$i]->situation_percent - $prev_progress) / $object->lines[$i]->situation_percent;
	                } else {
	                    if ($conf->multicurrency->enabled && $object->multicurrency_tx != 1) $tvaligne = $sign * $object->lines[$i]->multicurrency_total_tva;
	                    else $tvaligne = $sign * $object->lines[$i]->total_tva;
	                }

	                $localtax1ligne = $object->lines[$i]->total_localtax1;
	                $localtax2ligne = $object->lines[$i]->total_localtax2;
	                $localtax1_rate = $object->lines[$i]->localtax1_tx;
	                $localtax2_rate = $object->lines[$i]->localtax2_tx;
	                $localtax1_type = $object->lines[$i]->localtax1_type;
	                $localtax2_type = $object->lines[$i]->localtax2_type;

	                if ($object->remise_percent) $tvaligne -= ($tvaligne * $object->remise_percent) / 100;
	                if ($object->remise_percent) $localtax1ligne -= ($localtax1ligne * $object->remise_percent) / 100;
	                if ($object->remise_percent) $localtax2ligne -= ($localtax2ligne * $object->remise_percent) / 100;

	                $vatrate = (string) $object->lines[$i]->tva_tx;

	                // Récupère le type depuis la base pour la compatibilité avec les anciens enregistrements
	                if ((!isset($localtax1_type) || $localtax1_type == '' || !isset($localtax2_type) || $localtax2_type == '') // si le type de taxe n'est pas défini
	                    && (!empty($localtax1_rate) || !empty($localtax2_rate))) // et il existe une taxe locale
	                {
	                    $localtaxtmp_array = getLocalTaxesFromRate($vatrate, 0, $object->thirdparty, $mysoc);
	                    $localtax1_type = $localtaxtmp_array[0];
	                    $localtax2_type = $localtaxtmp_array[2];
	                }

	                // retrieve global local tax
                    if ($localtax1_type && $localtax1ligne != 0) {
                        $this->localtax1[$localtax1_type][$localtax1_rate] += $localtax1ligne;
                    }
                    if ($localtax2_type && $localtax2ligne != 0) {
                        $this->localtax2[$localtax2_type][$localtax2_rate] += $localtax2ligne;
                    }

                    if (($object->lines[$i]->info_bits & 0x01) == 0x01) $vatrate .= '*';
                    if (!isset($this->tva[$vatrate])) 				$this->tva[$vatrate] = 0;
                    $this->tva[$vatrate] += $tvaligne;

                    $nexY = max($nexY, $posYAfterImage);

                    // Add line
                    if (!empty($conf->global->MAIN_PDF_DASH_BETWEEN_LINES) && $i < ($nblines - 1)) {
                        $pdf->setPage($pageposafter);
                        $pdf->SetLineStyle(array('dash'=>'1,1', 'color'=>array(80, 80, 80)));
                        //$pdf->SetDrawColor(190,190,200);
                        $pdf->line($this->marge_gauche, $nexY, $this->page_largeur - $this->marge_droite, $nexY);
                        $pdf->SetLineStyle(array('dash'=>0));
                    }

                    // Détecte si des pages ont été ajoutées automatiquement et affiche _tableau pour les pages précédentes
                    while ($pagenb < $pageposafter) {
                        $pdf->setPage($pagenb);
                        if ($pagenb == $pageposbeforeprintlines) {
                            $this->_tableau($pdf, $tab_top, $this->page_hauteur - $tab_top - $heightforfooter, 0, $outputlangs, $hidetop, 1, $object->multicurrency_code, $outputlangsbis);
                        } else {
                        	$this->_tableau($pdf, $tab_top_newpage, $this->page_hauteur - $tab_top_newpage - $heightforfooter, 0, $outputlangs, 1, 1, $object->multicurrency_code, $outputlangsbis);
                        }
                        $this->_pagefoot($pdf, $object, $outputlangs, 1);
                        $pagenb++;
                        $pdf->setPage($pagenb);
                        $pdf->setPageOrientation('', 1, 0); // Seule fonction permettant de modifier la marge basse de la page courante pour la définir.
                        if (empty($conf->global->MAIN_PDF_DONOTREPEAT_HEAD)) $this->_pagehead($pdf, $object, 0, $outputlangs);
                    }

                    if (isset($object->lines[$i + 1]->pagebreak) && $object->lines[$i + 1]->pagebreak) {
                        if ($pagenb == $pageposafter) {
                        	$this->_tableau($pdf, $tab_top, $this->page_hauteur - $tab_top - $heightforfooter, 0, $outputlangs, $hidetop, 1, $object->multicurrency_code, $outputlangsbis);
                        } else {
                        	$this->_tableau($pdf, $tab_top_newpage, $this->page_hauteur - $tab_top_newpage - $heightforfooter, 0, $outputlangs, 1, 1, $object->multicurrency_code, $outputlangsbis);
                        }
                        $this->_pagefoot($pdf, $object, $outputlangs, 1);
                        // New page
                        $pdf->AddPage();
                        if (!empty($tplidx)) $pdf->useTemplate($tplidx);
                        $pagenb++;
                        if (empty($conf->global->MAIN_PDF_DONOTREPEAT_HEAD)) $this->_pagehead($pdf, $object, 0, $outputlangs);
                    }
	            }

	            // Show square
	            if ($pagenb == $pageposbeforeprintlines)
	            {
	            	$this->_tableau($pdf, $tab_top, $this->page_hauteur - $tab_top - $heightforinfotot - $heightforfreetext - $heightforfooter, 0, $outputlangs, $hidetop, 0, $object->multicurrency_code, $outputlangsbis);
	                $bottomlasttab = $this->page_hauteur - $heightforinfotot - $heightforfreetext - $heightforfooter + 1;
	            } else {
	            	$this->_tableau($pdf, $tab_top_newpage, $this->page_hauteur - $tab_top_newpage - $heightforinfotot - $heightforfreetext - $heightforfooter, 0, $outputlangs, 1, 0, $object->multicurrency_code, $outputlangsbis);
	                $bottomlasttab = $this->page_hauteur - $heightforinfotot - $heightforfreetext - $heightforfooter + 1;
	            }

	            // Display infos area
	            //$posy = $this->drawInfoTable($pdf, $object, $bottomlasttab, $outputlangs);

	            // Display total zone
	            //$posy = $this->drawTotalTable($pdf, $object, $deja_regle, $bottomlasttab, $outputlangs);

	            // Display payment area
	            /*
	            if (($deja_regle || $amount_credit_notes_included || $amount_deposits_included) && empty($conf->global->INVOICE_NO_PAYMENT_DETAILS))
	            {
	                $posy = $this->drawPaymentsTable($pdf, $object, $posy, $outputlangs);
	            }
	            */

	            // Pagefoot
	            $this->_pagefoot($pdf, $object, $outputlangs);
	            if (method_exists($pdf, 'AliasNbPages')) $pdf->AliasNbPages();

	            $pdf->Close();

	            $pdf->Output($file, 'F');

	            // Add pdfgeneration hook
	            $hookmanager->initHooks(array('pdfgeneration'));
	            $parameters = array('file'=>$file, 'object'=>$object, 'outputlangs'=>$outputlangs);
	            global $action;
	            $reshook = $hookmanager->executeHooks('afterPDFCreation', $parameters, $this, $action); // Attention : $action et $object peuvent avoir été modifiés par certains hooks
	            if ($reshook < 0)
	            {
	                $this->error = $hookmanager->error;
	                $this->errors = $hookmanager->errors;
	            }

	            if (!empty($conf->global->MAIN_UMASK))
	                @chmod($file, octdec($conf->global->MAIN_UMASK));

	                $this->result = array('fullpath'=>$file);

	                return 1; // Pas d'erreur
	        } else {
	            $this->error = $langs->transnoentities("ErrorCanNotCreateDir", $dir);
	            return 0;
	        }
	    } else {
	        $this->error = $langs->transnoentities("ErrorConstantNotDefined", "FAC_OUTPUTDIR");
	        return 0;
	    }
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Renvoie la liste des modules de génération actifs
	 *
	 *  @param	DoliDB	$db     			Database handler
	 *  @param  integer	$maxfilenamelength  Longueur maximale de la valeur à afficher
	 *  @return	array						Liste des modèles
	 */
	public static function liste_modeles($db, $maxfilenamelength = 0)
	{
		// phpcs:enable
		return parent::liste_modeles($db, $maxfilenamelength); // TODO : remplacer le squelette généré automatiquement
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	/**
	 *   Affiche le tableau des lignes
	 *
	 *   @param		tcpdf			$pdf     		Object PDF
	 *   @param		string		$tab_top		Position haute du tableau
	 *   @param		string		$tab_height		Hauteur du tableau (rectangle)
	 *   @param		int			$nexY			Y (non utilisé)
	 *   @param		Translate	$outputlangs	Langs object
	 *   @param		int			$hidetop		1=Masque la barre haute du tableau et le titre, 0=Ne masque rien, -1=Masque seulement le titre
	 *   @param		int			$hidebottom		Masque la barre basse du tableau
	 *   @param		string		$currency		Code de la devise
	 *   @param		Translate	$outputlangsbis	Langs object bis
	 *   @return	void
	 */
	protected function _tableau(&$pdf, $tab_top, $tab_height, $nexY, $outputlangs, $hidetop = 0, $hidebottom = 0, $currency = '', $outputlangsbis = null)
	{
		global $conf;

		// Force la désactivation de hidetop et hidebottom
		$hidebottom = 0;
		if ($hidetop) $hidetop = -1;

		$currency = !empty($currency) ? $currency : $conf->currency;
		$default_font_size = pdf_getPDFFontSize($outputlangs);

		// Montant dans (à tab_top - 1)
		$pdf->SetTextColor(0, 0, 0);
		$pdf->SetFont('', '', $default_font_size - 2);

		if (empty($hidetop))
		{
			$titre = $outputlangs->transnoentities("AmountInCurrency", $outputlangs->transnoentitiesnoconv("Currency".$currency));
			if (!empty($conf->global->PDF_USE_ALSO_LANGUAGE_CODE) && is_object($outputlangsbis)) {
				$titre .= ' - '.$outputlangsbis->transnoentities("AmountInCurrency", $outputlangsbis->transnoentitiesnoconv("Currency".$currency));
			}

			$pdf->SetXY($this->page_largeur - $this->marge_droite - ($pdf->GetStringWidth($titre) + 3), $tab_top - 4);
			$pdf->MultiCell(($pdf->GetStringWidth($titre) + 3), 2, $titre);

			//$conf->global->MAIN_PDF_TITLE_BACKGROUND_COLOR='230,230,230';
			if (!empty($conf->global->MAIN_PDF_TITLE_BACKGROUND_COLOR)) {
				$pdf->Rect($this->marge_gauche, $tab_top, $this->page_largeur - $this->marge_droite - $this->marge_gauche, $this->tabTitleHeight, 'F', null, explode(',', $conf->global->MAIN_PDF_TITLE_BACKGROUND_COLOR));
			}
		}

		$pdf->SetDrawColor(128, 128, 128);
		$pdf->SetFont('', '', $default_font_size - 1);

		// Output Rect
		$this->printRect($pdf, $this->marge_gauche, $tab_top, $this->page_largeur - $this->marge_gauche - $this->marge_droite, $tab_height, $hidetop, $hidebottom); // Rect prend une longueur en 3e et 4e paramètres


		$this->pdfTabTitles($pdf, $tab_top, $tab_height, $outputlangs, $hidetop);

		if (empty($hidetop)) {
		    $pdf->line($this->marge_gauche, $tab_top + $this->tabTitleHeight, $this->page_largeur - $this->marge_droite, $tab_top + $this->tabTitleHeight); // line prend une position y en 2e et 4e paramètres
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	/**
	 *  Affiche l'en-tête haut de page.
	 *
	 *  @param	Tcpdf			$pdf     		Object PDF
	 *  @param  Object		$object     	Objet à afficher
	 *  @param  int	    	$showaddress    0=no, 1=yes
	 *  @param  Translate	$outputlangs	Langue de l'objet pour la sortie
	 *  @param  Translate	$outputlangsbis	Langue de l'objet pour la sortie (bis)
	 *  @return	void
	 */
	protected function _pagehead(&$pdf, $object, $showaddress, $outputlangs, $outputlangsbis = null)
	{
		global $conf, $langs;

		// Charge les fichiers de traduction nécessaires à la page
		$outputlangs->loadLangs(array("main", "bills", "propal", "companies"));

		$default_font_size = pdf_getPDFFontSize($outputlangs);

		pdf_pagehead($pdf, $outputlangs, $this->page_hauteur);

		// Show Draft Watermark
		if ($object->statut == Facture::STATUS_DRAFT && (!empty($conf->global->FACTURE_DRAFT_WATERMARK)))
        {
		      pdf_watermark($pdf, $outputlangs, $this->page_hauteur, $this->page_largeur, 'mm', $conf->global->FACTURE_DRAFT_WATERMARK);
        }

		$pdf->SetTextColor(0, 0, 60);
		$pdf->SetFont('', 'B', $default_font_size + 3);

		$w = 110;

		$posy = $this->marge_haute;
        $posx = $this->page_largeur - $this->marge_droite - $w;

		$pdf->SetXY($this->marge_gauche, $posy);

		// Logo
		if (empty($conf->global->PDF_DISABLE_MYCOMPANY_LOGO))
		{
			if ($this->emetteur->logo)
			{
				$logodir = $conf->mycompany->dir_output;
				if (!empty($conf->mycompany->multidir_output[$object->entity])) $logodir = $conf->mycompany->multidir_output[$object->entity];
				if (empty($conf->global->MAIN_PDF_USE_LARGE_LOGO))
				{
					$logo = $logodir.'/logos/thumbs/'.$this->emetteur->logo_small;
				} else {
					$logo = $logodir.'/logos/'.$this->emetteur->logo;
				}
				if (is_readable($logo))
				{
				    $height = pdf_getHeightForLogo($logo);
					$pdf->Image($logo, $this->marge_gauche, $posy, 0, $height); // largeur=0 (auto)
				} else {
					$pdf->SetTextColor(200, 0, 0);
					$pdf->SetFont('', 'B', $default_font_size - 2);
					$pdf->MultiCell($w, 3, $outputlangs->transnoentities("ErrorLogoFileNotFound", $logo), 0, 'L');
					$pdf->MultiCell($w, 3, $outputlangs->transnoentities("ErrorGoToGlobalSetup"), 0, 'L');
				}
			} else {
				$text = $this->emetteur->name;
				$pdf->MultiCell($w, 4, $outputlangs->convToOutputCharset($text), 0, 'L');
			}
		}

		$pdf->SetFont('', 'B', $default_font_size + 3);
		$pdf->SetXY($posx, $posy);
		$pdf->SetTextColor(0, 0, 60);
		$title = $outputlangs->transnoentities("PdfTitle");
		if (!empty($conf->global->PDF_USE_ALSO_LANGUAGE_CODE) && is_object($outputlangsbis)) {
			$title .= ' - ';
			$title .= $outputlangsbis->transnoentities("PdfTitle");
		}
		$pdf->MultiCell($w, 3, $title, '', 'R');

		$pdf->SetFont('', 'B', $default_font_size);

		$posy += 5;
		$pdf->SetXY($posx, $posy);
		$pdf->SetTextColor(0, 0, 60);
		$textref = $outputlangs->transnoentities("Ref")." : ".$outputlangs->convToOutputCharset($object->ref);
		if ($object->statut == Facture::STATUS_DRAFT)
		{
			$pdf->SetTextColor(128, 0, 0);
			$textref .= ' - '.$outputlangs->transnoentities("NotValidated");
		}
		$pdf->MultiCell($w, 4, $textref, '', 'R');

		$posy += 1;
		$pdf->SetFont('', '', $default_font_size - 2);

		if ($object->ref_client)
		{
			$posy += 4;
			$pdf->SetXY($posx, $posy);
			$pdf->SetTextColor(0, 0, 60);
			$pdf->MultiCell($w, 3, $outputlangs->transnoentities("RefCustomer")." : ".$outputlangs->convToOutputCharset($object->ref_client), '', 'R');
		}

		if (!empty($conf->global->PDF_SHOW_PROJECT_TITLE))
		{
			$object->fetch_projet();
			if (!empty($object->project->ref))
			{
				$posy += 3;
				$pdf->SetXY($posx, $posy);
				$pdf->SetTextColor(0, 0, 60);
				$pdf->MultiCell($w, 3, $outputlangs->transnoentities("Project")." : ".(empty($object->project->title) ? '' : $object->projet->title), '', 'R');
			}
		}

		if (!empty($conf->global->PDF_SHOW_PROJECT))
		{
			$object->fetch_projet();
			if (!empty($object->project->ref))
			{
				$posy += 3;
				$pdf->SetXY($posx, $posy);
				$pdf->SetTextColor(0, 0, 60);
				$pdf->MultiCell($w, 3, $outputlangs->transnoentities("RefProject")." : ".(empty($object->project->ref) ? '' : $object->projet->ref), '', 'R');
			}
		}

		$posy += 4;
		$pdf->SetXY($posx, $posy);
		$pdf->SetTextColor(0, 0, 60);

		$title = $outputlangs->transnoentities("Date");
		if (!empty($conf->global->PDF_USE_ALSO_LANGUAGE_CODE) && is_object($outputlangsbis)) {
			$title .= ' - '.$outputlangsbis->transnoentities("Date");
		}
		$pdf->MultiCell($w, 3, $title." : ".dol_print_date($object->date, "day", false, $outputlangs), '', 'R');

		if ($object->thirdparty->code_client)
		{
			$posy += 3;
			$pdf->SetXY($posx, $posy);
			$pdf->SetTextColor(0, 0, 60);
			$pdf->MultiCell($w, 3, $outputlangs->transnoentities("CustomerCode")." : ".$outputlangs->transnoentities($object->thirdparty->code_client), '', 'R');
		}

		// Récupère le contact
		if (!empty($conf->global->DOC_SHOW_FIRST_SALES_REP))
		{
		    $arrayidcontact = $object->getIdContact('internal', 'SALESREPFOLL');
		    if (count($arrayidcontact) > 0)
		    {
		        $usertmp = new User($this->db);
		        $usertmp->fetch($arrayidcontact[0]);
                $posy += 4;
                $pdf->SetXY($posx, $posy);
		        $pdf->SetTextColor(0, 0, 60);
		        $pdf->MultiCell($w, 3, $langs->transnoentities("SalesRepresentative")." : ".$usertmp->getFullName($langs), '', 'R');
		    }
		}

		$posy += 1;

		$top_shift = 0;
		// Affiche la liste des objets liés
		$current_y = $pdf->getY();
		$posy = pdf_writeLinkedObjects($pdf, $object, $outputlangs, $posx, $posy, $w, 3, 'R', $default_font_size);
		if ($current_y < $pdf->getY())
		{
			$top_shift = $pdf->getY() - $current_y;
		}

		if ($showaddress)
		{
			// Sender properties
			$carac_emetteur = pdf_build_address($outputlangs, $this->emetteur, $object->thirdparty, '', 0, 'source', $object);

			// Show sender
			$posy = !empty($conf->global->MAIN_PDF_USE_ISO_LOCATION) ? 40 : 42;
			$posy += $top_shift;
			$posx = $this->marge_gauche;
			if (!empty($conf->global->MAIN_INVERT_SENDER_RECIPIENT)) $posx = $this->page_largeur - $this->marge_droite - 80;

			$hautcadre = !empty($conf->global->MAIN_PDF_USE_ISO_LOCATION) ? 38 : 40;
			$widthrecbox = !empty($conf->global->MAIN_PDF_USE_ISO_LOCATION) ? 92 : 82;


			// Show sender frame
			$pdf->SetTextColor(0, 0, 0);
			$pdf->SetFont('', '', $default_font_size - 2);
			$pdf->SetXY($posx, $posy - 5);
			$pdf->MultiCell(66, 5, $outputlangs->transnoentities("BillFrom").":", 0, 'L');
			$pdf->SetXY($posx, $posy);
			$pdf->SetFillColor(230, 230, 230);
			$pdf->MultiCell($widthrecbox, $hautcadre, "", 0, 'R', 1);
			$pdf->SetTextColor(0, 0, 60);

			// Affiche le nom de l'expéditeur
			$pdf->SetXY($posx + 2, $posy + 3);
			$pdf->SetFont('', 'B', $default_font_size);
			$pdf->MultiCell($widthrecbox - 2, 4, $outputlangs->convToOutputCharset($this->emetteur->name), 0, 'L');
			$posy = $pdf->getY();

			// Show sender information
			$pdf->SetXY($posx + 2, $posy);
			$pdf->SetFont('', '', $default_font_size - 1);
			$pdf->MultiCell($widthrecbox - 2, 4, $carac_emetteur, 0, 'L');

			// Si un contact FACTURATION est défini sur la facture, on l'utilise
			$usecontact = false;
			$arrayidcontact = $object->getIdContact('external', 'BILLING');
			if (count($arrayidcontact) > 0)
			{
				$usecontact = true;
				$result = $object->fetch_contact($arrayidcontact[0]);
			}

			// Nom du destinataire
			if ($usecontact && !empty($conf->global->MAIN_USE_COMPANY_NAME_OF_CONTACT)) {
				$thirdparty = $object->contact;
			} else {
				$thirdparty = $object->thirdparty;
			}

			if (is_object($thirdparty)) {
				$carac_client_name = pdfBuildThirdpartyName($thirdparty, $outputlangs);
			}

			$carac_client = pdf_build_address($outputlangs, $this->emetteur, $object->thirdparty, ($usecontact ? $object->contact : ''), $usecontact, 'target', $object);

			// Show recipient
			$widthrecbox = !empty($conf->global->MAIN_PDF_USE_ISO_LOCATION) ? 92 : 100;
			if ($this->page_largeur < 210) $widthrecbox = 84; // Pour le format US executive
			$posy = !empty($conf->global->MAIN_PDF_USE_ISO_LOCATION) ? 40 : 42;
			$posy += $top_shift;
			$posx = $this->page_largeur - $this->marge_droite - $widthrecbox;
			if (!empty($conf->global->MAIN_INVERT_SENDER_RECIPIENT)) $posx = $this->marge_gauche;

			// Show recipient frame
			$pdf->SetTextColor(0, 0, 0);
			$pdf->SetFont('', '', $default_font_size - 2);
			$pdf->SetXY($posx + 2, $posy - 5);
			$pdf->MultiCell($widthrecbox, 5, $outputlangs->transnoentities("BillTo").":", 0, 'L');
			$pdf->Rect($posx, $posy, $widthrecbox, $hautcadre);

			// Affiche le nom du destinataire
			$pdf->SetXY($posx + 2, $posy + 3);
			$pdf->SetFont('', 'B', $default_font_size);
			$pdf->MultiCell($widthrecbox, 2, $carac_client_name, 0, 'L');

			$posy = $pdf->getY();

			// Show recipient information
			$pdf->SetFont('', '', $default_font_size - 1);
			$pdf->SetXY($posx + 2, $posy);
			$pdf->MultiCell($widthrecbox, 4, $carac_client, 0, 'L');
		}

		$pdf->SetTextColor(0, 0, 0);
		return $top_shift;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	/**
	 *   	Affiche le pied de page. Nécessite l'objet this->emetteur
     *
	 *   	@param	PDF			$pdf     			PDF
	 * 		@param	Object		$object				Objet à afficher
	 *      @param	Translate	$outputlangs		Langue de l'objet pour la sortie
	 *      @param	int			$hidefreetext		1=Hide free text
	 *      @return	int								Renvoie la hauteur de la marge basse, texte du pied de page inclus
	 */
	protected function _pagefoot(&$pdf, $object, $outputlangs, $hidefreetext = 0)
	{
		global $conf;
		$showdetails = $conf->global->MAIN_GENERATE_DOCUMENTS_SHOW_FOOT_DETAILS;
		return pdf_pagefoot($pdf, $outputlangs, 'INVOICE_FREE_TEXT', $this->emetteur, $this->marge_basse, $this->marge_gauche, $this->page_hauteur, $object, $showdetails, $hidefreetext);
	}

	/**
	 *  Définit le tableau des champs de colonnes
	 *
	 *  @param	object			$object    		common object
	 *  @param	Translate		$outputlangs    langs
     *  @param	int			   $hidedetails		Ne pas afficher le détail des lignes
     *  @param	int			   $hidedesc		Ne pas afficher la description
     *  @param	int			   $hideref			Ne pas afficher la référence
     *  @return	null
     */
    public function defineColumnField($object, $outputlangs, $hidedetails = 0, $hidedesc = 0, $hideref = 0)
    {
	    global $conf, $hookmanager;

	    // Style de champ par défaut pour le contenu
	    $this->defaultContentsFieldsStyle = array(
	        'align' => 'R', // R,C,L
	        'padding' => array(1, 0.5, 1, 0.5), // Comme en css : 0 => haut, 1 => droite, 2 => bas, 3 => gauche
	    );

	    // Style de champ par défaut pour le contenu
	    $this->defaultTitlesFieldsStyle = array(
	        'align' => 'C', // R,C,L
	        'padding' => array(0.5, 0, 0.5, 0), // Comme en css : 0 => haut, 1 => droite, 2 => bas, 3 => gauche
	    );

	    /*
	     * Par exemple
	    $this->cols['theColKey'] = array(
	        'rank' => $rank, // int : sert à trier les colonnes
	        'width' => 20, // la largeur de la colonne en mm
	        'title' => array(
	            'textkey' => 'yourLangKey', // si pas de libellé, yourLangKey sera traduit pour remplacer le libellé
	            'label' => ' ', // le libellé final : utilisé pour le texte final généré
	            'align' => 'L', // text alignement :  R,C,L
	            'padding' => array(0.5,0.5,0.5,0.5), // Comme en css : 0 => haut, 1 => droite, 2 => bas, 3 => gauche
	        ),
	        'content' => array(
	            'align' => 'L', // text alignement :  R,C,L
	            'padding' => array(0.5,0.5,0.5,0.5), // Comme en css : 0 => haut, 1 => droite, 2 => bas, 3 => gauche
	        ),
	    );
	    */

	    $rank = 0; // ne pas utiliser de rang négatif
	    $this->cols['desc'] = array(
	        'rank' => $rank,
	        'width' => false, // seulement pour la description
	        'status' => true,
	        'title' => array(
	            'textkey' => 'Designation', // utiliser une clé de langue est utile dans certains cas avec le module
	            'align' => 'L',
	            // 'textkey' => 'yourLangKey', // si pas de libellé, yourLangKey sera traduit pour remplacer le libellé
	            // 'label' => ' ', // le libellé final
	            'padding' => array(0.5, 0.5, 0.5, 0.5), // Comme en css : 0 => haut, 1 => droite, 2 => bas, 3 => gauche
	        ),
	        'content' => array(
	            'align' => 'L',
                'padding' => array(1, 0.5, 1, 1.5), // Comme en css : 0 => haut, 1 => droite, 2 => bas, 3 => gauche
	        ),
	    );

	    // PHOTO
        $rank = $rank + 10;
        $this->cols['photo'] = array(
            'rank' => $rank,
            'width' => (empty($conf->global->MAIN_DOCUMENTS_WITH_PICTURE_WIDTH) ? 20 : $conf->global->MAIN_DOCUMENTS_WITH_PICTURE_WIDTH), // en mm
            'status' => false,
            'title' => array(
                'textkey' => 'Photo',
                'label' => ' '
            ),
            'content' => array(
                'padding' => array(0, 0, 0, 0), // Comme en css : 0 => haut, 1 => droite, 2 => bas, 3 => gauche
            ),
            'border-left' => false, // remove left line separator
        );

	    if (!empty($conf->global->MAIN_GENERATE_INVOICES_WITH_PICTURE) && !empty($this->atleastonephoto))
	    {
	        $this->cols['photo']['status'] = true;
	    }


	    $rank = $rank + 10;
	    $this->cols['vat'] = array(
	        'rank' => $rank,
	        'status' => false,
	        'width' => 16, // en mm
	        'title' => array(
	            'textkey' => 'VAT'
	        ),
	        'border-left' => true, // add left line separator
	    );

	    if (empty($conf->global->MAIN_GENERATE_DOCUMENTS_WITHOUT_VAT) && empty($conf->global->MAIN_GENERATE_DOCUMENTS_WITHOUT_VAT_COLUMN))
	    {
	        $this->cols['vat']['status'] = true;
	    }

	    $rank = $rank + 10;
	    $this->cols['subprice'] = array(
	        'rank' => $rank,
	        'width' => 19, // en mm
	        'status' => true,
	        'title' => array(
	            'textkey' => 'PriceUHT'
	        ),
	        'border-left' => true, // add left line separator
	    );

	    $rank = $rank + 10;
	    $this->cols['qty'] = array(
	        'rank' => $rank,
	        'width' => 16, // en mm
	        'status' => true,
	        'title' => array(
	            'textkey' => 'Qty'
	        ),
	        'border-left' => true, // add left line separator
	    );

	    $rank = $rank + 10;
	    $this->cols['progress'] = array(
	        'rank' => $rank,
	        'width' => 19, // en mm
	        'status' => false,
	        'title' => array(
	            'textkey' => 'Progress'
	        ),
	        'border-left' => true, // add left line separator
	    );

	    if ($this->situationinvoice)
	    {
	        $this->cols['progress']['status'] = true;
	    }

	    $rank = $rank + 10;
	    $this->cols['unit'] = array(
	        'rank' => $rank,
	        'width' => 11, // en mm
	        'status' => false,
	        'title' => array(
	            'textkey' => 'Unit'
	        ),
	        'border-left' => true, // add left line separator
	    );
	    if (getDolGlobalString('PRODUCT_USE_UNITS')) {
	        $this->cols['unit']['status'] = true;
	    }

	    $rank = $rank + 10;
	    $this->cols['discount'] = array(
	        'rank' => $rank,
	        'width' => 13, // en mm
	        'status' => false,
	        'title' => array(
	            'textkey' => 'ReductionShort'
	        ),
	        'border-left' => true, // add left line separator
	    );
	    if ($this->atleastonediscount) {
	        $this->cols['discount']['status'] = true;
	    }

	    $rank = $rank + 1000; // ajoute un grand décalage pour être sûr d'être la dernière colonne, car le rang par défaut d'un champ supplémentaire est 100
	    $this->cols['totalexcltax'] = array(
	        'rank' => $rank,
	        'width' => 26, // en mm
	        'status' => true,
	        'title' => array(
	            'textkey' => 'TotalHT'
	        ),
	        'border-left' => true, // add left line separator
	    );

	    // Add extrafields cols
        if (!empty($object->lines)) {
            $line = reset($object->lines);
            $this->defineColumnExtrafield($line, $outputlangs, $hidedetails);
        }

	    $parameters = array(
	        'object' => $object,
	        'outputlangs' => $outputlangs,
	        'hidedetails' => $hidedetails,
	        'hidedesc' => $hidedesc,
	        'hideref' => $hideref
	    );

	    $reshook = $hookmanager->executeHooks('defineColumnField', $parameters, $this); // Attention : $object peut avoir été modifié par le hook
	    if ($reshook < 0)
	    {
	        setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
	    } elseif (empty($reshook))
	    {
	        $this->cols = array_replace($this->cols, $hookmanager->resArray); // array_replace est utilisé pour conserver les clés
	    } else {
	        $this->cols = $hookmanager->resArray;
	    }
	}
}
