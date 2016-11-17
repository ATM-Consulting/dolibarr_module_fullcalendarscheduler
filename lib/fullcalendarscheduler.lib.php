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
	
	if (!empty($conf->global->FULLCALENDAR_SCHEDULER_RESOURCES_TYPE_ALLOWED))
	{
		$sql = 'SELECT r.rowid as fk_resource, r.ref, ctr.code';
		$sql.= ' FROM '.MAIN_DB_PREFIX.'resource r';
		$sql.= ' INNER JOIN '.MAIN_DB_PREFIX.'c_type_resource ctr ON (r.fk_code_type_resource = ctr.code)';
		$sql.= ' WHERE ctr.active > 0';
		$sql.= ' AND ctr.code IN ("'.implode('","', explode(',', $conf->global->FULLCALENDAR_SCHEDULER_RESOURCES_TYPE_ALLOWED)).'")';
		
		dol_syslog("fulcalendarscheduler.lib.php::getResourcesAllowed", LOG_DEBUG);
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
				// Surtout ne pas mettre de clé en indice, si non, un json encode en sortie est foireux
				$TRes[] = array('id' => $obj->fk_resource, 'title' => $obj->ref, 'code' => $obj->code, 'eventColor' => random_color());
				
				$i++;
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
	global $db;
	
	$TEvent = $TResId = array();
	foreach ($TResource as &$l)
	{
		$TResId[] = $l['id'];
	}
	
	if (!empty($TResId))
	{
		if (empty($date)) $date = date('Y-m-d');
		
		$sql = 'SELECT a.id as fk_actioncomm, er.resource_id, a.label, a.note, a.fk_soc, s.nom as company_name, sp.civility, sp.lastname, sp.firstname, a.datep, a.datep2, a.fulldayevent, er.rowid as fk_element_resource ';
		$sql.= ' FROM '.MAIN_DB_PREFIX.'actioncomm a';
		$sql.= ' INNER JOIN '.MAIN_DB_PREFIX.'element_resources er ON (er.element_id = a.id AND er.element_type = "action")';
		$sql.= ' INNER JOIN '.MAIN_DB_PREFIX.'resource r ON (er.resource_id = r.rowid)';
		$sql.= ' INNER JOIN '.MAIN_DB_PREFIX.'societe s ON (s.rowid = a.fk_soc)';
		$sql.= ' INNER JOIN '.MAIN_DB_PREFIX.'socpeople sp ON (sp.rowid = a.fk_contact)';
		$sql.= ' WHERE DATE_FORMAT(a.datep, "%Y-%m-%d") = "'.$date.'"';
		$sql.= ' AND er.resource_id IN ('.implode(',', $TResId).')';
		
		dol_syslog("fulcalendarscheduler.lib.php::getResourcesAllowed", LOG_DEBUG);
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
				
				// Surtout ne pas mettre de clé en indice, si non, un json encode en sortie est foireux
				$TEvent[] = array(
					'id' => $obj->fk_actioncomm
					,'resourceId' => $obj->resource_id
					,'fk_element_resource' => $obj->fk_element_resource 
					,'title' => $obj->label
					,'desc' => $obj->note
					,'fk_soc' => $obj->fk_soc
					,'company_name' => $obj->company_name
					,'contact_civility' => $obj->civility
					,'contact_lastname' => $obj->lastname
					,'contact_firstname' => $obj->firstname
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


// TODO remove quand une meilleur solution pour la couleur sera ready
function random_color_part() {
    return str_pad( dechex( mt_rand( 0, 255 ) ), 2, '0', STR_PAD_LEFT);
}

function random_color() {
    return '#' . random_color_part() . random_color_part() . random_color_part();
}
