<?php

require('./config.php');

require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/agenda.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formactions.class.php';
require_once DOL_DOCUMENT_ROOT.'/resource/class/html.formresource.class.php';

dol_include_once('fullcalendarscheduler/lib/fullcalendarscheduler.lib.php');

$result = restrictedArea($user, 'agenda', 0, '', 'allactions');
$hookmanager->initHooks(array('agenda', 'fullcalendarscheduler'));

$action='view';
$object=null;

$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

$morejs = array(
	'/fullcalendarscheduler/js/moment.min.js'
	,'/fullcalendarscheduler/js/fullcalendar.js'
	,'/fullcalendarscheduler/js/scheduler.min.js' // TODO swap for scheduler.min.js
	,'/fullcalendarscheduler/js/fullcalendarscheduler.js'
	,'/fullcalendarscheduler/js/langs/lang-all.js'
);
$morecss = array(
	'/fullcalendarscheduler/css/fullcalendarscheduler.css'
	,'/fullcalendarscheduler/css/fullcalendar.min.css'
	,'/fullcalendarscheduler/css/scheduler.min.css'
);

$actioncomm = new ActionComm($db);

$langs->load('main');

llxHeader('', $langs->trans("Agenda"), '', '', 0, 0, $morejs, $morecss);
$head = calendars_prepare_head(array());
dol_fiche_head($head, 'perresource', $langs->trans('Agenda'), 0, 'action');

echo '<div id="fullcalendar_scheduler"></div>';

$TRessource = getResourcesAllowed();


/**
 * Instance des variables utiles pour le formulaire de création d'un événement
 */
$formactions=new FormActions($db);
$form=new Form($db);
$formresources = new FormResource($db);

ob_start();
$formactions->select_type_actions(-1, 'type_code', 'systemauto');
$select_type_action .= ob_get_clean();

$input_title_action = '<input type="text" name="label" placeholder="'.$langs->transnoentitiesnoconv('Title').'" style="width:300px" />';

// on intègre la notion de fulldayevent ??? $langs->trans("EventOnFullDay")   <input type="checkbox" id="fullday" name="fullday" '.(GETPOST('fullday')?' checked':'').' />
ob_start();
echo '<label>'.$langs->trans("DateActionStart").'</label> ';
$form->select_date(null,'date_start',1,1,1,"action",1,1,0,0,'fulldaystart');
$select_date_start = ob_get_clean();

ob_start();
echo '<label>'.$langs->trans("DateActionEnd").'</label> ';
$form->select_date(null,'date_end',1,1,1,"action",1,1,0,0,'fulldayend');
$select_date_end = ob_get_clean();

/*
require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
$doleditor=new DolEditor('note',(GETPOST('note')?GETPOST('note'):$object->note),'',180,'dolibarr_notes','In',true,true,$conf->fckeditor->enabled,ROWS_6,90);
$doleditor->Create();
*/
$input_note = '<textarea name="note" value="" placeholder="'.$langs->trans('Description').'" rows="3" class="minwidth300"></textarea>';
$options = array(array('method'=>'getContacts', 'url'=>dol_buildpath('/core/ajax/contacts.php',1), 'htmlname'=>'contactid', 'params'=>array('add-customer-contact'=>'disabled')));
$select_company = '<label for="fk_soc">'.$langs->transnoentitiesnoconv('ThirdParty').'</label>'.$form->select_company('', 'fk_soc', '', 1, 0, 0, $options, 0, 'minwidth300');

ob_start();
echo '<label for="contactid">'.$langs->transnoentitiesnoconv('Contact').'</label>';
$form->select_contacts(-1, -1, 'contactid', 1, '', '', 0, 'minwidth200'); // contactid car nom non pris en compte par l'ajax en vers.<3.9
$select_contact = ob_get_clean();

$select_user = '<label for="fk_user">'.$langs->transnoentitiesnoconv('User').'</label>'.$form->select_dolusers($user->id, 'fk_user');
$select_resource = '<label for="fk_resource">'.$langs->transnoentitiesnoconv('Resource').'</label> '.$formresources->select_resource_list('','fk_resource','',0,1,0,array(),'',2);

//$select_service = '<label for="fk_product">'.$langs->transnoentitiesnoconv('Service').'</label>'.$form->select_produits_list('', 'fk_product', 1);
ob_start();
echo '<label for="fk_service">'.$langs->transnoentitiesnoconv('Service').'</label>';
$form->select_produits('', 'fk_service', 1);
$select_service = ob_get_clean();


$TExtraToPrint = '<table id="extrafield_to_replace" class="extrafields" width="100%">';

$extrafields = new ExtraFields($db);
$extralabels=$extrafields->fetch_name_optionals_label($actioncomm->table_element);
if (!empty($extrafields->attribute_label))
{
	$TExtraToPrint.= $actioncomm->showOptionals($extrafields, 'edit');
}
$TExtraToPrint.= '</table>';
/**/

echo '
<script type="text/javascript">
	fullcalendarscheduler_interface = "'.dol_buildpath('/fullcalendarscheduler/script/interface.php', 1).'";
	fullcalendarscheduler_initialLangCode = "'.(!empty($conf->global->FULLCALENDARSCHEDULER_LOCALE_LANG) ? $conf->global->FULLCALENDARSCHEDULER_LOCALE_LANG : 'fr').'";
	fullcalendarscheduler_snapDuration = "'.(!empty($conf->global->FULLCALENDARSCHEDULER_SNAP_DURATION) ? $conf->global->FULLCALENDARSCHEDULER_SNAP_DURATION : '00:30:00').'";
	fullcalendarscheduler_aspectRatio = "'.(!empty($conf->global->FULLCALENDARSCHEDULER_ASPECT_RATIO) ? $conf->global->FULLCALENDARSCHEDULER_ASPECT_RATIO : '1.6').'";
	fullcalendarscheduler_minTime = "'.(!empty($conf->global->FULLCALENDARSCHEDULER_MIN_TIME) ? $conf->global->FULLCALENDARSCHEDULER_MIN_TIME : '00:00').'";
	fullcalendarscheduler_maxTime = "'.(!empty($conf->global->FULLCALENDARSCHEDULER_MAX_TIME) ? $conf->global->FULLCALENDARSCHEDULER_MAX_TIME : '23:00').'";
	
	
	fullcalendar_scheduler_resources_allowed = '.json_encode($TRessource).';
	
	fullcalendar_scheduler_businessHours_week_start = "'.(!empty($conf->global->FULLCALENDARSCHEDULER_BUSINESSHOURS_WEEK_START) ? $conf->global->FULLCALENDARSCHEDULER_BUSINESSHOURS_WEEK_START : '08:00').'";
	fullcalendar_scheduler_businessHours_week_end = "'.(!empty($conf->global->FULLCALENDARSCHEDULER_BUSINESSHOURS_WEEK_END) ? $conf->global->FULLCALENDARSCHEDULER_BUSINESSHOURS_WEEK_END : '18:00').'";

	fullcalendar_scheduler_businessHours_weekend_start = "'.(!empty($conf->global->FULLCALENDARSCHEDULER_BUSINESSHOURS_WEEKEND_START) ? $conf->global->FULLCALENDARSCHEDULER_BUSINESSHOURS_WEEKEND_START : '10:00').'";
	fullcalendar_scheduler_businessHours_weekend_end = "'.(!empty($conf->global->FULLCALENDARSCHEDULER_BUSINESSHOURS_WEEKEND_END) ? $conf->global->FULLCALENDARSCHEDULER_BUSINESSHOURS_WEEKEND_END : '16:00').'";
	
	fullcalendarscheduler_title_dialog_create_event = "'.$langs->transnoentitiesnoconv('fullcalendarscheduler_title_dialog_create_event').'";
	fullcalendarscheduler_title_dialog_update_event = "'.$langs->transnoentitiesnoconv('fullcalendarscheduler_title_dialog_update_event').'";
	fullcalendarscheduler_title_dialog_delete_event = "'.$langs->transnoentitiesnoconv('fullcalendarscheduler_title_dialog_delete_event').'";
	fullcalendarscheduler_title_dialog_show_detail_event = "'.$langs->transnoentitiesnoconv('fullcalendarscheduler_title_dialog_show_detail_event').'";
	fullcalendarscheduler_button_dialog_add = "'.$langs->transnoentitiesnoconv('fullcalendarscheduler_button_dialog_add').'";
	fullcalendarscheduler_button_dialog_update = "'.$langs->transnoentitiesnoconv('fullcalendarscheduler_button_dialog_update').'";
	fullcalendarscheduler_button_dialog_cancel = "'.$langs->transnoentitiesnoconv('fullcalendarscheduler_button_dialog_cancel').'";
	fullcalendarscheduler_button_dialog_confirm = "'.$langs->transnoentitiesnoconv('fullcalendarscheduler_button_dialog_confirm').'";
	fullcalendarscheduler_content_dialog_delete = "'.$langs->transnoentitiesnoconv('fullcalendarscheduler_content_dialog_delete').'";
	
	fullcalendarscheduler_date_format = "'.$langs->trans("FormatDateShortJavaInput").'";
	
	fullcalendarscheduler_div = $(\'<form id="form_add_event" action="#"></form>\');
	fullcalendarscheduler_div	.append("<p>"+'.json_encode($select_type_action).'+"</p>")
								.append("<p>"+'.json_encode($input_title_action).'+"</p>")
								.append("<p>"+'.json_encode($select_date_start).'+"</p>")
								.append("<p>"+'.json_encode($select_date_end).'+"</p>")
								.append("<p>"+'.json_encode($input_note).'+"</p>")
								.append("<p>"+'.json_encode($select_company).'+"</p>")
								.append("<p>"+'.json_encode($select_contact).'+"</p>")
								.append("<p>"+'.json_encode($select_user).'+"</p>")
								.append("<p>"+'.json_encode($select_resource).'+"</p>")
								.append("<p>"+'.json_encode($select_service).'+"</p>")
								.append('.json_encode($TExtraToPrint).');					
								
	fullcalendarscheduler_picto_delete = "'.addslashes(img_delete()).'";
	fullcalendarscheduler_picto_detail = "'.addslashes(img_picto($langs->transnoentitiesnoconv('Show'), 'detail.png')).'";
	fullcalendarscheduler_TColorCivility = '.json_encode(getTColorCivility()).';
	
	fullcalendarscheduler_url_event_card = "'.dol_buildpath('/comm/action/card.php', 1).'";
</script>';

echo '
<style type="text/css">
	#fullcalendar_scheduler .ajaxtool {
		position:absolute;
		top:3px;
		right:2px;
	}
	
	#fullcalendar_scheduler .ajaxtool_link.need_to_be_adjust img {
		position:relative;
		top:-1px;
	}
	
	.ui-dialog { overflow: visible; }
	
	'.(!empty($conf->global->FULLCALENDARSCHEDULER_ROW_HEIGHT) ? '.fc-agendaDay-view tr { height: '.$conf->global->FULLCALENDARSCHEDULER_ROW_HEIGHT.'; }' : '').'
</style>
';

$parameters=array();
$reshook=$hookmanager->executeHooks('addMoreContent', $parameters, $object, $action);


dol_fiche_end();
llxFooter();

$db->close();