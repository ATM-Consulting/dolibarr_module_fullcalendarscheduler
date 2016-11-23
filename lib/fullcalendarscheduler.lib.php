<?php
/* <one line to give the program's name and a brief idea of what it does.>
 * Copyright (C) 2015 ATM Consulting <support@atm-consulting.fr>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *	\file		lib/fullcalendarscheduler.lib.php
 *	\ingroup	fullcalendarscheduler
 *	\brief		This file is an example module library
 *				Put some comments here
 */

function fullcalendarschedulerAdminPrepareHead()
{
    global $langs, $conf;

    $langs->load("fullcalendarscheduler@fullcalendarscheduler");

    $h = 0;
    $head = array();

    $head[$h][0] = dol_buildpath("/fullcalendarscheduler/admin/fullcalendarscheduler_setup.php", 1);
    $head[$h][1] = $langs->trans("Parameters");
    $head[$h][2] = 'settings';
    $h++;
    $head[$h][0] = dol_buildpath("/fullcalendarscheduler/admin/fullcalendarscheduler_about.php", 1);
    $head[$h][1] = $langs->trans("About");
    $head[$h][2] = 'about';
    $h++;

    // Show more tabs from modules
    // Entries must be declared in modules descriptor with line
    //$this->tabs = array(
    //	'entity:+tabname:Title:@fullcalendarscheduler:/fullcalendarscheduler/mypage.php?id=__ID__'
    //); // to add new tab
    //$this->tabs = array(
    //	'entity:-tabname:Title:@fullcalendarscheduler:/fullcalendarscheduler/mypage.php?id=__ID__'
    //); // to remove a tab
    complete_head_from_modules($conf, $langs, $object, $head, $h, 'fullcalendarscheduler');

    return $head;
}

/**
 * Copie de Dolresource::load_cache_code_type_resource
 */
function getAllCodeResource()
{
	global $db;
	
	$TRes = array();
	
	$sql = 'SELECT code, label';
	$sql.= ' FROM '.MAIN_DB_PREFIX.'c_type_resource';
	$sql.= ' WHERE active > 0';
	$sql.= ' ORDER BY label';
	
	dol_syslog("fulcalendarscheduler.lib.php::getAllCodeResource", LOG_DEBUG);
	$resql = $db->query($sql);
	if ($resql)
	{
		$num = $db->num_rows($resql);
		$i = 0;
		while ($i < $num)
		{
			$obj = $db->fetch_object($resql);
			// Si traduction existe, on l'utilise, sinon on prend le libelle par defaut
			//$label=($langs->trans("ResourceTypeShort".$obj->code)!=("ResourceTypeShort".$obj->code)?$langs->trans("ResourceTypeShort".$obj->code):($obj->label!='-'?$obj->label:''));
			$TRes[$obj->code] = $obj->label;
			$i++;
		}
	}
	else
	{
		dol_print_error($db);
	}
	
	return $TRes;
}

function getResourcesAllowed()
{
	global $db,$conf;
	
	$TRes = array();
	$TBgColor = array('#AD5C47', '#47B4A7', '#C8445E', '#438386', '#CB44B9', '#4D9241', '#444D99', '#4447A1', '#654399', '#BC4AE9', '#98A144');
	
	if (!empty($conf->global->FULLCALENDAR_SCHEDULER_RESOURCES_TYPE_ALLOWED))
	{
		$sql = 'SELECT r.rowid as fk_resource, r.ref, ctr.code';
		$sql.= ' FROM '.MAIN_DB_PREFIX.'resource r';
		$sql.= ' INNER JOIN '.MAIN_DB_PREFIX.'c_type_resource ctr ON (r.fk_code_type_resource = ctr.code)';
		$sql.= ' WHERE ctr.active > 0';
		$sql.= ' AND r.entity = '.$conf->entity; // TODO à faire évoluer potentiellement vers un getEntity
		$sql.= ' AND ctr.code IN ("'.implode('","', explode(',', $conf->global->FULLCALENDAR_SCHEDULER_RESOURCES_TYPE_ALLOWED)).'")';
		
		dol_syslog("fulcalendarscheduler.lib.php::getResourcesAllowed", LOG_DEBUG);
		$resql = $db->query($sql);
		if ($resql)
		{
			dol_include_once('/fullcalendarscheduler/class/randomColor.class.php');
			
			$num = $db->num_rows($resql);
			$i = $j = 0;
			while ($i < $num)
			{
				$obj = $db->fetch_object($resql);
				//$bgColor = RandomColor::one(array('luminosity'=>'dark'));
				if (empty($TBgColor[$j])) $j = 0;
				$bgColor = $TBgColor[$j];
				
				// Si traduction existe, on l'utilise, sinon on prend le libelle par defaut
				//$label=($langs->trans("ResourceTypeShort".$obj->code)!=("ResourceTypeShort".$obj->code)?$langs->trans("ResourceTypeShort".$obj->code):($obj->label!='-'?$obj->label:''));
				// Surtout ne pas mettre de clé en indice, si non, un json encode en sortie est foireux
				$TRes[] = array('id' => $obj->fk_resource, 'title' => $obj->ref, 'code' => $obj->code, 'eventTextColor' => '#fff', 'eventColor' => (!empty($conf->global->FULLCALENDARSCHEDULER_USE_COLOR_FOR_EACH_RESOURCE) ? $bgColor : '') );
				
				$i++;
				$j++;
			}
		}
		else
		{
			dol_print_error($db);
		}
	}
	
	return $TRes;
}

/**
 * Retourne les événements pour les ressources données et pour une date 
 * 
 * @param $date 	date 	format Y-m-d
 */
function getEventForResources($TResource, $date='')
{
	global $db,$conf;
	
	$TEvent = $TResId = array();
	foreach ($TResource as &$l)
	{
		$TResId[] = $l['id'];
	}
	
	if (!empty($TResId))
	{
		require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
		require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
		
		$actioncomm = new ActionComm($db);
		$service = new Product($db);
		
		if (empty($date)) $date = date('Y-m-d');
		
		$sql = 'SELECT a.id as fk_actioncomm, ca.code as type_code, p.rowid as fk_service, p.ref as product_ref, p.fk_product_type as product_type, p.label as product_label';
		$sql.= ', er.resource_id, a.label, a.note, a.fk_soc, s.nom as company_name, a.datep, a.datep2, a.fulldayevent, er.rowid as fk_element_resource ';
		//$sql.= ', ';
		$sql.= ', sp.rowid as fk_socpeople, sp.civility, sp.lastname, sp.firstname, sp.email as contact_email, sp.address as contact_address, sp.zip as contact_zip, sp.town as contact_town, sp.phone_mobile as contact_phone_mobile';
		$sql.= ' FROM '.MAIN_DB_PREFIX.'actioncomm a';
		$sql.= ' INNER JOIN '.MAIN_DB_PREFIX.'element_resources er ON (er.element_id = a.id AND er.element_type = "action")';
		$sql.= ' INNER JOIN '.MAIN_DB_PREFIX.'resource r ON (er.resource_id = r.rowid)';
		$sql.= ' INNER JOIN '.MAIN_DB_PREFIX.'societe s ON (s.rowid = a.fk_soc)';
		$sql.= ' INNER JOIN '.MAIN_DB_PREFIX.'socpeople sp ON (sp.rowid = a.fk_contact)';
		$sql.= ' INNER JOIN '.MAIN_DB_PREFIX.'c_actioncomm ca ON (ca.id = a.fk_action)';
		$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'element_element ee ON (a.id = ee.fk_target AND ee.targettype = "'.$actioncomm->element.'" AND ee.sourcetype = "'.$service->element.'")';
		$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'product p ON (p.rowid = ee.fk_source)';
		$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'actioncomm_extrafields ae ON (ae.fk_object = a.id)';
		$sql.= ' WHERE a.entity = '.$conf->entity; // TODO à faire évoluer potentiellement vers un getEntity
		$sql.= ' AND DATE_FORMAT(a.datep, "%Y-%m-%d") = "'.$date.'"';
		$sql.= ' AND er.resource_id IN ('.implode(',', $TResId).')';
		
		dol_syslog("fulcalendarscheduler.lib.php::getResourcesAllowed", LOG_DEBUG);
		$resql = $db->query($sql);
		if ($resql)
		{
			$societe = new Societe($db);
			$contact = new Contact($db);
			$service = new Product($db);
			
			$num = $db->num_rows($resql);
			$i = 0;
			while ($i < $num)
			{
				$obj = $db->fetch_object($resql);
				// Si traduction existe, on l'utilise, sinon on prend le libelle par defaut
				//$label=($langs->trans("ResourceTypeShort".$obj->code)!=("ResourceTypeShort".$obj->code)?$langs->trans("ResourceTypeShort".$obj->code):($obj->label!='-'?$obj->label:''));
				
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
				
				$service->id = $obj->fk_service;
				$service->ref = $obj->product_ref;
				$service->label = $obj->product_label;
				$service->type = $obj->product_type;
				
				// Surtout ne pas mettre de clé en indice, si non, un json encode en sortie est foireux
				$TEvent[] = array(
					'id' => $obj->fk_actioncomm
					,'type_code' => $obj->type_code
					,'fk_service' => $obj->fk_service
					,'product_ref' => $obj->product_ref
					,'link_service' => !empty($service->id) ? $service->getNomUrl(1) : ''
					,'resourceId' => $obj->resource_id
					,'fk_element_resource' => $obj->fk_element_resource 
					,'title' => $obj->label
					,'desc' => $obj->note
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
				);
				
				$i++;
			}
		}
		else
		{
			dol_print_error($db);
		}
	}
	
	return $TEvent;
}

/**
 * Fonction qui retourne un tableau associatif entre le code civilité et la couleur associée
 * 
 * TODO à faire évoluer pour paramétrer les couleurs par civilité disponible
 */
function getTColorCivility()
{
	$TColorCivility = array(
		'DR' => '#F8334E'
		,'MME' => '#E929B4'
		,'MLE' => '#E929B4'
		,'MTRE' => '#E85728'
		,'MR' => '#2455CE'
	);
	
	return $TColorCivility;
}
