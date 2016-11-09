<?php
if (!defined('INC_FROM_CRON_SCRIPT')) define('INC_FROM_CRON_SCRIPT', true);
require('../config.php');

require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/agenda.lib.php';

require_once DOL_DOCUMENT_ROOT.'/resource/class/dolresource.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

$get = GETPOST('get', 'alpha');
$put = GETPOST('put', 'alpha');

$response = new interfaceResponse;

switch ($get) {
	case 'value':
		
		break;
}

switch ($put) {
	case 'updateTimeSlotAndResource':
		_updateTimeSlotAndResource((object) GETPOST('event'), GETPOST('dateFrom'));
		__out( $response );
		break;
	case 'updateTimeSlot':
		_updateTimeSlot((object) GETPOST('event'), GETPOST('dateFrom'));
		__out( $response );
		break;
}

exit;

/**
 * Fonction de mise à jour de créneau horaire et l'association de la ressource
 * @return object
 */
function _updateTimeSlotAndResource($event, $dateFrom)
{
	_updateTimeSlot($event, $dateFrom);
	_updateResourceLinked($event->fk_element_resource, $event->resourceId);
}

/**
 * Fonction de mise à jour de l'association de la ressource
 * @return int <0 si erreur, >0 si ok 
 */
function _updateResourceLinked($fk_element_resource, $ressourceId)
{
	global $db,$user,$response;;
	
	$dolresource = new Dolresource($db);
	
	$res = $dolresource->fetch_element_resource($fk_element_resource);
	if($res)
	{
		$dolresource->resource_id = $ressourceId;
		//$dolresource->busy = $busy;
		//$dolresource->mandatory = $mandatory;
		
		$result = $dolresource->update_element_resource($user);
		if ($result >= 0)
		{
			$response->TSuccess[] = 'Update resource link successful';
			return 1;
		}
		else
		{
			$response->TErrors[] = $dolresource->error;
			return -1;
		}
	}
	else
	{
		$response->TErrors[] = $dolresource->error;
		return -2;
	}
}

/**
 * Fonction de mise à jour de créneau horaire
 * @return int <0 si erreur, >0 si ok
 */
function _updateTimeSlot($event, $dateFrom)
{
	global $db,$langs,$user,$response;
	
	$actioncomm = new ActionComm($db);
	if ($actioncomm->fetch($event->id) > 0)
	{
		if (!empty($event->allDay))
		{
			$timeFrom = strtotime($dateFrom);
			
			$actioncomm->fulldayevent = 1;
			$actioncomm->datep = dol_mktime(0, 0, 0, date('m', $timeFrom), date('d', $timeFrom), date('Y', $timeFrom));
			$actioncomm->datef = dol_mktime(23, 59, 0, date('m', $timeFrom), date('d', $timeFrom), date('Y', $timeFrom));
		}
		else
		{
			$actioncomm->fulldayevent = 0;
			
			$timep = strtotime($event->start);
			$actioncomm->datep = dol_mktime(date('H', $timep), date('i', $timep), 0, date('m', $timep), date('d', $timep), date('Y', $timep));
			
			if (!empty($event->end)) $timef = strtotime($event->end);
			else $timef = $timep + 3600; // Heure début + 1h car l'attribut "defaultTimedEventDuration" est paramétré sur 1h
			
			$actioncomm->datef = dol_mktime(date('H', $timef), date('i', $timef), 0, date('m', $timef), date('d', $timef), date('Y', $timef));
		}
		
		if ($actioncomm->update($user) > 0)
		{
			$response->TSuccess[] = 'Update Time slot successful';
			return 1;
		}
		else
		{
			$response->TErrors[] = $actioncomm->error;
			return -1;
		}
	}
	else 
	{
		$response->TErrors[] = $actioncomm->error;
		return -2;
	}
}

class interfaceResponse {
	public $TSuccess = array();
	public $TErrors = array();
	public $data = stdClass; // object qui contiendra des données pour le traitement côté JS
}
