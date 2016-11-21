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

dol_include_once('/fullcalendarscheduler/lib/fullcalendarscheduler.lib.php');

$langs->load('errors');
$langs->load('fullcalendarscheduler@fullcalendarscheduler');

$get = GETPOST('get', 'alpha');
$put = GETPOST('put', 'alpha');

$response = new interfaceResponse;

switch ($get) {
	case 'getEventsFromDate':
		_getEventsFromDate(GETPOST('dateFrom'));
		__out( $response );
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
	case 'createEvent':
		_createEvent(GETPOST('TParam', 'array'), GETPOST('dateFrom'));
		__out( $response );
		break;
	case 'deleteEvent':
		_deleteEvent(GETPOST('fk_actioncomm', 'int'));
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
			$response->TError[] = $dolresource->error;
			return -1;
		}
	}
	else
	{
		$response->TError[] = $dolresource->error;
		return -2;
	}
}

/**
 * Fonction de mise à jour de créneau horaire
 * @return int <0 si erreur, >0 si ok
 */
function _updateTimeSlot($event, $dateFrom)
{
	global $db,$langs,$user,$response,$conf;
	
	$actioncomm = new ActionComm($db);
	if ($actioncomm->fetch($event->id) > 0)
	{
		$actioncomm->fetch_userassigned();
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
			
			$timef = strtotime($event->end);
			$actioncomm->datef = dol_mktime(date('H', $timef), date('i', $timef), 0, date('m', $timef), date('d', $timef), date('Y', $timef));
		}
		
		if ($actioncomm->update($user) > 0)
		{
			$response->TSuccess[] = 'Update Time slot successful';
			return 1;
		}
		else
		{
			$response->TError[] = $actioncomm->error;
			return -1;
		}
	}
	else 
	{
		$response->TError[] = $actioncomm->error;
		return -2;
	}
}

/**
 * Function qui retourne le nombre d'events d'un jour donné et ajout à la variable de retour les events 
 * 
 * @param $dateFrom	date	format Y-m-d
 */
function _getEventsFromDate($dateFrom)
{
	global $response;
	
	$TResource = getResourcesAllowed();
	$TEvent = getEventForResources($TResource, $dateFrom);
	
	$response->data->TEvent = $TEvent;
	
	return count($TEvent);
}

/**
 * Fonction qui créé un nouvel événement agenda et le retourne
 * 
 * @param $TParam	array
 */
function _createEvent($TParam, $dateFrom)
{
	global $db, $response, $langs, $user;
	
	$TParam['label'] = trim( $TParam['label']);
	$TParam['note'] = trim( $TParam['note']);
	
	if (empty($TParam['type_code'])) $response->TError[] = $langs->transnoentitiesnoconv('fullcalendarscheduler_create_event_type_code_empty');
	if (empty($TParam['label'])) $response->TError[] = $langs->transnoentitiesnoconv('fullcalendarscheduler_create_event_label_empty');
	if (empty($TParam['fk_soc']) || $TParam['fk_soc'] <= 0) $response->TError[] = $langs->transnoentitiesnoconv('fullcalendarscheduler_create_event_fk_soc_empty');
	if (empty($TParam['fk_user'])) $response->TError[] = $langs->transnoentitiesnoconv('fullcalendarscheduler_create_event_fk_user_empty');
	if (empty($TParam['fk_resource'])) $response->TError[] = $langs->transnoentitiesnoconv('fullcalendarscheduler_create_event_fk_resource_empty');
	$date_start = strtotime($TParam['date_start']); // return false si la conversion échoue
	if (!$date_start) $response->TError[] = $langs->transnoentitiesnoconv('fullcalendarscheduler_create_event_date_start_invalid', $TParam['date_start']);
	$date_end = strtotime($TParam['date_end']); // return false si la conversion échoue
	if (!$date_end) $response->TError[] = $langs->transnoentitiesnoconv('fullcalendarscheduler_create_event_date_end_invalid', $TParam['date_end']);
	
	if (!empty($response->TError))
	{
		return -1;
	}
	
	$actioncomm = new Actioncomm($db);
	
	// Initialisation object actioncomm
	$actioncomm->type_code = $TParam['type_code'];
	$actioncomm->label = $TParam['label'];
	$actioncomm->note = $TParam['note'];
	
	$actioncomm->fulldayevent = (int) $TParam['fulldayevent']; // TODO voir si je l'utilise côté client
	$actioncomm->datep = $date_start;
	$actioncomm->datef = $date_end;
	
	$actioncomm->socid = $TParam['fk_soc'];
	$actioncomm->fetch_thirdparty();
	$actioncomm->societe = $actioncomm->thirdparty;
	
	$actioncomm->contact = new Contact($db);
	if (!empty($TParam['contactid'])) $actioncomm->contact->fetch($TParam['contactid']);
	
	$actioncomm->userownerid = $TParam['fk_user'];
	//$actioncomm->userassigned[] = array('id'=>$TParam['fk_user'], 'transparency'=>0); // Facultatif, la methode create fait la même chose
	
	
	// Autres params que je n'utilise pas
	$actioncomm->priority = 0;
	$actioncomm->location = '';
	$actioncomm->fk_project = 0;
	$actioncomm->percentage = -1; // Non application, à faire évoluer potentiellement
	$actioncomm->duree = 0;

	
	$db->begin(); // Gestion de la transaction à ce niveau car je doit, après création de l'event, associer la ressource
	if ($actioncomm->create($user) > 0)
	{
		if (!$actioncomm->error)
		{
			$res = $actioncomm->add_element_resource($TParam['fk_resource'], 'dolresource');
			if ($res)
			{
				$db->commit();
				$TResource = getResourcesAllowed();
				$TEvent = getEventForResources($TResource, $TParam['dateFrom']);
				
				$response->TSuccess[] = 'Create event and resource linked successful';
				$response->data->TEvent = $TEvent;
			}
			else 
			{
				$db->rollback();
				$response->TError[] = $actioncomm->error;
				return -4;
			}
		}
		else
		{
			$db->rollback();
			$response->TError[] = $actioncomm->error; // Pour respecter le traitement d'erreur standard il faut peut être ajouter un $langs->transnoentitiesnoconv() 
			return -3;
		}
	}
	else
	{
		$db->rollback();
		if (!empty($actioncomm->error)) $response->TError[] = $actioncomm->error;
		if (!empty($actioncomm->errors)) array_merge($response->TError, $actioncomm->errors);
		return -2;
	}
	
}

function _deleteEvent($fk_actioncomm)
{
	global $db,$response;
	
	$actioncomm = new Actioncomm($db);
	if ($actioncomm->fetch($fk_actioncomm) > 0)
	{
		if ($actioncomm->delete() > 0)
		{
			$response->TSuccess[] = 'Delete event id = '.$fk_actioncomm.' successful';
		}
		else 
		{
			$response->TError[] = $actioncomm->error;
		}
	}
	else
	{
		$response->TError[] = $actioncomm->error;
	}
}

class interfaceResponse {
	public $TSuccess = array();
	public $TError = array();
	public $data; // object qui contiendra des données pour le traitement côté JS
	
	public function __construct()
	{
		$this->data = new stdClass;
	}
}
