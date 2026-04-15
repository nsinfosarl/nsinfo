<?php

function nsSendMail($object, $diroutput, $outputlangs, $modelmail, $socpeople='', $typemail = 'order_send', $armail = 0, $inverseevent = 0, $toemails='')
{
	global $user, $db, $langs;

	include_once DOL_DOCUMENT_ROOT . '/core/class/CMailFile.class.php';
	include_once DOL_DOCUMENT_ROOT . '/core/class/html.formmail.class.php';
	$outputlangs = $langs;

	$error = 0;

	$ref = dol_sanitizeFileName($object->ref);

	if (empty(($modelmail)) || ($modelmail) < 1) {
		setEventMessages("Aucun modèle de mail sélectionné dans le module ixci ".($modelmail), null, "errors");
		$error++;
	}

	if (!empty($socpeople)) {
		require_once DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php';
		$usersocpeople = new Contact($db);
		$usersocpeople->fetch($socpeople);
	}

	if (!empty($diroutput)) {
		$fileparams = dol_most_recent_file($diroutput . '/' . $ref, preg_quote($ref, '/') . '[^\-]+');

		$arr_file = array();
		$arr_mime = array();
		$arr_name = array();

		$arr_file[] = $fileparams['fullname'];
		$arr_mime[] = dol_mimetype($fileparams['name']);
		$arr_name[] = $fileparams['name'];


		if ($fileparams['fullname'] == NULL) {
			setEventMessages("Pas de fichier à envoyer", null, "errors");
			$error++;
		}
	}

	if (!$error) {
		$trackid = 'ord' . $object->id;

		$formmail = new FormMail($db);

		$object->fetch_thirdparty();

//		$substitutionarray = getCommonSubstitutionArray($outputlangs, 0, '', $object);
		complete_substitutions_array($substitutionarray, $outputlangs, $object);
		$arraymessage = $formmail->getEMailTemplate($db, $typemail, $user, $outputlangs, ($modelmail), 1);

		$errormesg = '';

		$sendTopicTmp = ($outputlangs->transnoentitiesnoconv($arraymessage->topic));

		$sendTopic = make_substitutions($sendTopicTmp, $substitutionarray, $outputlangs, 0);

		//replace (__REF_CLIENT__) par vide si ecore présent après la substitution
		$sendTopic = str_replace('( __REF_CLIENT__ )', '', $sendTopic);

		$content = $outputlangs->transnoentitiesnoconv($arraymessage->content);
//
		$sendContent = make_substitutions($content, $substitutionarray, $outputlangs, 0);
		var_dump('ici');

		$to = !empty($socpeople) ? $usersocpeople->email : $toemails;
		$from = $user->email;

		$arcclient = $armail;

		$cMailFile = new CMailFile($sendTopic, $to, $from, $sendContent, $arr_file, $arr_mime, $arr_name, '', '', $arcclient, 1, '', '', $trackid, '', '', '');


		// Sending Mail
		if ($cMailFile->sendfile()) {
//			$resultmajcmde = $infocmdeclt->Verifbt($object->id, 1);

			//evenement
			$datep = dol_mktime(date('H'), date('i'), '00', date('m'), date('d'), date('Y'));
			$actioncomm = new ActionComm($db);
			$actioncode = 'EMAIL_OUT';

			$actioncomm->socid = $object->socid;
			$actioncomm->type_code = 'AC_OTH_AUTO'; // Type of event ('AC_OTH', 'AC_OTH_AUTO', 'AC_XXX'...)
			$actioncomm->code = 'AC_OTH_AUTO';
			$exped = $user->firstname . ' ' . $user->lastname;
			$desti = $usersocpeople->firstname . ' ' . $usersocpeople->lastname;
			if (!empty($inverseevent)) $actioncomm->label = 'Mail envoyé par ' . $exped . ' à ' . $desti;
			else $actioncomm->label = 'Mail envoyé à ' . $desti . ' par ' . $exped;
			$actioncomm->trackid = $trackid;
			$actioncomm->fk_element = $object->id;
			$actioncomm->elementtype = $object->element;
			if (is_array($arr_file) && count($arr_file) > 0) {
				$actioncomm->attachedfiles = $arr_file;
			}
			$actioncomm->email_msgid = $cMailFile->msgid;
			$actioncomm->email_from = $from;
			$actioncomm->email_subject = $sendTopic;
			$actioncomm->email_to = $to;
			$actioncomm->datep = $datep;
			$actioncomm->datef = $datep;
			$actioncomm->percentage = -1;
			$actioncomm->userassigned[$userid] = array('id' => $user->id, 'mandatory' => 0, 'transparency' => 0);
			$actioncomm->fulldayevent = 0;
			$actioncomm->userownerid = $user->id;
			$actioncomm->note_private = $sendContent;
			$actioncomm->contact_id = $socpeople;
			$actioncomm->socpeopleassigned = (!empty($socpeople) ? array($socpeople => '') : array());

			$db->begin();
			$result = $actioncomm->create($user);

			if ($result > 0) {
				if (!$actioncomm->error) $db->commit();
			}
			setEventMessages($langs->trans('MESSARCENV'), null);

		}
	}



}



