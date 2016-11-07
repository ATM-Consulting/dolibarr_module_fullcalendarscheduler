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
	,'/fullcalendarscheduler/js/scheduler.js' // TODO swap for scheduler.min.js
	,'/fullcalendarscheduler/js/fullcalendarscheduler.js'
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
$TEvent = getEventForResources($TRessource);

echo '
<script type="text/javascript">
	fullcalendar_scheduler_resources_allowed = '.json_encode($TRessource).';
	fullcalendar_scheduler_events_by_resource = '.json_encode($TEvent).'; 
</script>';

dol_fiche_end();
llxFooter();

$db->close();