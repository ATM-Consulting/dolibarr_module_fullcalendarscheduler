<?php
if (!defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL', 1); // Disables token renewal
if (!defined('INC_FROM_CRON_SCRIPT')) define('INC_FROM_CRON_SCRIPT', true);
chdir(dirname(__FILE__));
require('../config.php');

require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/agenda.lib.php';

require_once DOL_DOCUMENT_ROOT.'/resource/class/dolresource.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

dol_include_once('/fullcalendarscheduler/lib/fullcalendarscheduler.lib.php');

$langs->load('companies');
$langs->load('products');
$langs->load('errors');
$langs->load('fullcalendarscheduler@fullcalendarscheduler');

$get = GETPOST('get', 'alpha');
$put = GETPOST('put', 'alpha');

global $response;
$response = new interfaceResponse;

switch ($get) {
	case 'getEventsFromDateAndResource':
	case 'getEventsFromDate':
		_getEventsFromDate(GETPOST('dateFrom'));
		__out( $response );
		break;
	case 'getEventsFromDates':
		_getEventsFromDates(GETPOST('dateStart'), GETPOST('dateEnd'), GETPOST('code'));
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
	case 'createOrUpdateEvent':
		_createOrUpdateEvent($_GET, GETPOST('dateFrom'));
		__out( $response );
		break;
	case 'deleteEvent':
		_deleteEvent(GETPOST('fk_actioncomm', 'int'));
		__out( $response );
		break;
}


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
	global $db,$user,$response;

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
 * Function qui retourne le nombre d'events d'un jour donné et ajoute à la variable de retour les events
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
 * Fonction qui retourne une liste d'events agenda pour une date ou un plage de date et éventuellement avec un type
 * @param date $date_s	format Y-m-d H:i:s
 * @param date $date_e	format Y-m-d H:i:s
 */
function _getEventsFromDates($date_s, $date_e='', $c_actioncomm_code='')
{
	global $db, $response, $conf;

	require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';



	$actioncomm = new ActionComm($db);
	$extrafields = new ExtraFields($db);
	$extralabels=$extrafields->fetch_name_optionals_label($actioncomm->table_element);

	$sql = 'SELECT a.id AS fk_actioncomm, ca.code AS type_code';
	$sql.= ', a.label, a.note, a.fk_soc, s.nom AS company_name, a.datep, a.datep2, a.fulldayevent';
	$sql.= ', sp.rowid AS fk_socpeople, sp.civility, sp.lastname, sp.firstname, sp.email AS contact_email, sp.address AS contact_address, sp.zip AS contact_zip, sp.town AS contact_town, sp.phone_mobile AS contact_phone_mobile';
	foreach ($extralabels as $key => $label)
	{
		$sql .= ', ae.'.$key.' AS extra_'.$key;
	}
	$sql.= ' FROM '.MAIN_DB_PREFIX.'actioncomm a';
	$sql.= ' INNER JOIN '.MAIN_DB_PREFIX.'c_actioncomm ca ON (ca.id = a.fk_action)';

	$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'societe s ON (s.rowid = a.fk_soc)';
	$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'socpeople sp ON (sp.rowid = a.fk_contact)';
	$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'actioncomm_extrafields ae ON (ae.fk_object = a.id)';

	$sql.= ' WHERE a.entity = '.$conf->entity;
	if (empty($date_e)) $sql.= ' AND DATE_FORMAT(a.datep, "%Y-%m-%d") = \''.date('Y-m-d', $date_s).'\'';
	else {
		$sql.= ' AND a.datep >= '.$db->idate($date_s);
		$sql.= ' AND a.datep2 <= '.$db->idate($date_e);
	}
	if (!empty($c_actioncomm_code)) $sql.= ' AND ca.code = \''.$c_actioncomm_code.'\'';

	dol_syslog("interface.php::_getEventsFromDates", LOG_DEBUG);
	$resql = $db->query($sql);
	if ($resql)
	{
		$societe = new Societe($db);
		$contact = new Contact($db);
		while ($obj = $db->fetch_object($resql))
		{
			$actioncomm->fetch($obj->fk_actioncomm);
			$actioncomm->fetch_optionals();

			$societe->id = $obj->fk_soc;
			$societe->nom = $societe->name = $obj->company_name;

			$contact->id = $obj->fk_socpeople;
			$contact->firstname = $obj->firstname;
			$contact->lastname = $obj->lastname;
			$contact->email = $obj->contact_email;
			$contact->phone_mobile = $obj->contact_phone_mobile;
			$contact->address = $obj->contact_address;
			$contact->zip = $obj->contact_zip;
			$contact->town = $obj->contact_town;

			$response->data->TEvent[] = array(
				'id' => $obj->fk_actioncomm
				,'type_code' => $obj->type_code
				,'title' => $obj->label
				,'desc' => !empty($obj->note) ? $obj->note : ''
				,'fk_soc' => $obj->fk_soc
				,'company_name' => $obj->company_name
				,'link_company' => !empty($societe->id) ? $societe->getNomUrl(1) : ''
				,'fk_socpeople' => $obj->fk_socpeople
				,'contact_civility' => $obj->civility
				,'contact_lastname' => $obj->lastname
				,'contact_firstname' => $obj->firstname
				,'link_contact' => !empty($contact->id) ? $contact->getNomUrl(1) : ''
				,'start' => !empty($obj->fulldayevent) ? dol_print_date($obj->datep, '%Y-%m-%d') : dol_print_date($obj->datep, '%Y-%m-%dT%H:%M:%S', 'gmt') // TODO
				,'end' => !empty($obj->fulldayevent) ? dol_print_date($obj->datep2, '%Y-%m-%d') : dol_print_date($obj->datep2, '%Y-%m-%dT%H:%M:%S', 'gmt')
				,'allDay' => (boolean) $obj->fulldayevent // TODO à voir si on garde pour que l'event aparaisse en haut
				,'showOptionals' => !empty($extralabels) ? customShowOptionals($actioncomm, $extrafields) : ''
				,'editOptionals' => !empty($extralabels) ? '<table id="extrafield_to_replace" class="extrafields" width="100%">'.$actioncomm->showOptionals($extrafields, 'edit').'</table>' : ''
			);

		}

		return count($response->data->TEvent);
	}
	else
	{
		$response->TError[] = $db->lasterror;
		return 0;
	}
}

/**
 * Fonction qui créé ou maj un événement agenda et le retourne
 *
 * @param $TParam	array
 */
function _createOrUpdateEvent($TParam, $dateFrom)
{
	global $db, $response, $langs, $user;

	$TParam['label'] = trim( $TParam['label']);
	$TParam['note'] = trim( $TParam['note']);

	if (empty($TParam['type_code'])) $response->TError[] = $langs->transnoentitiesnoconv('fullcalendarscheduler_create_event_type_code_empty');
	if (empty($TParam['label'])) $response->TError[] = $langs->transnoentitiesnoconv('fullcalendarscheduler_create_event_label_empty');
	if (empty($TParam['fk_soc']) || $TParam['fk_soc'] <= 0) $response->TError[] = $langs->transnoentitiesnoconv('fullcalendarscheduler_create_event_fk_soc_empty');
	if (empty($TParam['fk_user'])) $response->TError[] = $langs->transnoentitiesnoconv('fullcalendarscheduler_create_event_fk_user_empty');
	if (empty($TParam['fk_resource'])) $response->TError[] = $langs->transnoentitiesnoconv('fullcalendarscheduler_create_event_fk_resource_empty');
	$date_start = dol_mktime($TParam['date_starthour'], $TParam['date_startmin'], 0, $TParam['date_startmonth'], $TParam['date_startday'], $TParam['date_startyear']);
	if (empty($date_start)) $response->TError[] = $langs->transnoentitiesnoconv('fullcalendarscheduler_create_event_date_start_invalid', $TParam['date_start']);
	$date_end = dol_mktime($TParam['date_endhour'], $TParam['date_endmin'], 0, $TParam['date_endmonth'], $TParam['date_endday'], $TParam['date_endyear']);
	if (empty($date_end)) $response->TError[] = $langs->transnoentitiesnoconv('fullcalendarscheduler_create_event_date_end_invalid', $TParam['date_end']);

	if (!empty($response->TError))
	{
		return -1;
	}

	$actioncomm = new Actioncomm($db);
	if (!empty($TParam['fk_actioncomm']))
	{
		if ($actioncomm->fetch($TParam['fk_actioncomm']) < 0)
		{
			$response->TError[] = $langs->transnoentitiesnoconv('fullcalendarscheduler_create_event_error_fetch', $TParam['fk_actioncomm']);
			return -5;
		}
	}

	// Initialisation object actioncomm
	$actioncomm->type_code = $TParam['type_code'];
	$actioncomm->fk_action = dol_getIdFromCode($db, $TParam['type_code'], 'c_actioncomm');
	$actioncomm->label = $TParam['label'];
	$actioncomm->note = $TParam['note'];

	$actioncomm->fulldayevent = (int) $TParam['fullday']; // TODO voir si je l'utilise côté client
	$actioncomm->datep = $date_start;
	$actioncomm->datef = $date_end;

	$actioncomm->socid = $TParam['fk_soc'];
	$actioncomm->fetch_thirdparty();
	$actioncomm->societe = $actioncomm->thirdparty;

	if (!empty($TParam['contactid'])) $actioncomm->contactid = $TParam['contactid'];

	$actioncomm->userownerid = $TParam['fk_user'];
	$actioncomm->userassigned = array($actioncomm->userownerid=>array('id'=>$actioncomm->userownerid));

	// Autres params que je n'utilise pas
	$actioncomm->priority = 0;
	$actioncomm->location = '';
	$actioncomm->fk_project = 0;
	$actioncomm->percentage = -1; // Non application, à faire évoluer potentiellement
	$actioncomm->duree = 0;

	$extrafields = new ExtraFields($db);
	$extralabels = $extrafields->fetch_name_optionals_label($actioncomm->table_element);
	$extrafields->setOptionalsFromPost($extralabels, $actioncomm);

	$is_update = 0;

	$db->begin(); // Gestion de la transaction à ce niveau car je doit, après création de l'event, associer la ressource
	if (empty($actioncomm->id)) $res = $actioncomm->create($user);
	else $is_update = $res = $actioncomm->update($user);

	if ($res > 0)
	{
		if (!$actioncomm->error)
		{
			if (!empty($TParam['fk_service']))
			{
				$service = new product($db);
				$actioncomm->fetchObjectLinked('', $service->element);
				if (!empty($actioncomm->linkedObjectsIds))
				{
					foreach ($actioncomm->linkedObjectsIds as $fk_element_element => $fk_product) $actioncomm->deleteObjectLinked('', '', '', '', $fk_element_element);
				}
				if ($actioncomm->add_object_linked($service->element, $TParam['fk_service']) <= 0)
				{
					$db->rollback();
					$response->TError[] = $actioncomm->error;
					return -6;
				}
			}

			// TODO à faire évoluer si nécessaire
			// Si je suis dans le cas d'un update, alors je supprime l'association à la ressource précédente puis je fait mon ajout (méthode un peu brutale car j'empèche l'association à plusieurs ressources, le module n'est pas prévus pour ça si non il faut valoriser en amont l'attribut "resourceIds")
			if ($is_update)
			{
				$dolresource = new Dolresource($db);
				$TResourceLinked = $dolresource->getElementResources($actioncomm->element, $actioncomm->id, 'dolresource');
				if (!empty($TResourceLinked))
				{
					foreach ($TResourceLinked as $Tab)
					{
						$actioncomm->delete_resource($Tab['rowid'], $actioncomm->element);
					}
				}
			}

			$res = $actioncomm->add_element_resource($TParam['fk_resource'], 'dolresource');
			if ($res)
			{
				$db->commit();
				$TResource = getResourcesAllowed();
				$TEvent = getEventForResources($TResource, $dateFrom);

				if ($is_update) $response->TSuccess[] = 'Update event and resource linked successful';
				else $response->TSuccess[] = 'Create event and resource linked successful';
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
		$this->data->TEvent = array();
	}
}
