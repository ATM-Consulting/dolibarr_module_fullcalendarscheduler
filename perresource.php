<?php

require('./config.php');

require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/agenda.lib.php';

dol_include_once('fullcalendarscheduler/lib/fullcalendarscheduler.lib.php');

$result = restrictedArea($user, 'agenda', 0, '', 'allactions');

$morejs = array(
	'/fullcalendarscheduler/js/moment.min.js'
	,'/fullcalendarscheduler/js/fullcalendar.min.js'
	,'/fullcalendarscheduler/js/scheduler.min.js' // TODO swap for scheduler.min.js
	,'/fullcalendarscheduler/js/fullcalendarscheduler.js'
	,'/fullcalendarscheduler/js/langs/lang-all.js'
);
$morecss = array(
	'/fullcalendarscheduler/css/fullcalendarscheduler.css'
	,'/fullcalendarscheduler/css/fullcalendar.min.css'
	,'/fullcalendarscheduler/css/scheduler.min.css'
);


llxHeader('', $langs->trans("Agenda"), '', '', 0, 0, $morejs, $morecss);
$head = calendars_prepare_head(array());
dol_fiche_head($head, 'perresource', $langs->trans('Agenda'), 0, 'action');

echo '<div id="fullcalendar_scheduler"></div>';

$TRessource = getResourcesAllowed();

echo '
<script type="text/javascript">
	fullcalendarscheduler_interface = "'.dol_buildpath('/fullcalendarscheduler/script/interface.php', 1).'";
	fullcalendarscheduler_initialLangCode = "'.(!empty($conf->global->FULLCALENDARSCHEDULER_LOCALE_LANG) ? $conf->global->FULLCALENDARSCHEDULER_LOCALE_LANG : 'fr').'";

	fullcalendar_scheduler_resources_allowed = '.json_encode($TRessource).';
	
	fullcalendar_scheduler_businessHours_week_start = "'.(!empty($conf->global->FULLCALENDARSCHEDULER_BUSINESSHOURS_WEEK_START) ? $conf->global->FULLCALENDARSCHEDULER_BUSINESSHOURS_WEEK_START : '08:00').'";
	fullcalendar_scheduler_businessHours_week_end = "'.(!empty($conf->global->FULLCALENDARSCHEDULER_BUSINESSHOURS_WEEK_END) ? $conf->global->FULLCALENDARSCHEDULER_BUSINESSHOURS_WEEK_END : '18:00').'";

	fullcalendar_scheduler_businessHours_weekend_start = "'.(!empty($conf->global->FULLCALENDARSCHEDULER_BUSINESSHOURS_WEEKEND_START) ? $conf->global->FULLCALENDARSCHEDULER_BUSINESSHOURS_WEEKEND_START : '10:00').'";
	fullcalendar_scheduler_businessHours_weekend_end = "'.(!empty($conf->global->FULLCALENDARSCHEDULER_BUSINESSHOURS_WEEKEND_END) ? $conf->global->FULLCALENDARSCHEDULER_BUSINESSHOURS_WEEKEND_END : '16:00').'";
</script>';

dol_fiche_end();
llxFooter();

$db->close();