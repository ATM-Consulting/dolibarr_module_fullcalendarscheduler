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
 * 	\file		admin/fullcalendarscheduler.php
 * 	\ingroup	fullcalendarscheduler
 * 	\brief		This file is an example module setup page
 * 				Put some comments here
 */
// Dolibarr environment
$res = @include("../../main.inc.php"); // From htdocs directory
if (! $res) {
    $res = @include("../../../main.inc.php"); // From "custom" directory
}

// Libraries
require_once DOL_DOCUMENT_ROOT . "/core/lib/admin.lib.php";
require_once '../lib/fullcalendarscheduler.lib.php';

// Translations
$langs->load("fullcalendarscheduler@fullcalendarscheduler");

// Access control
if (! $user->admin) {
    accessforbidden();
}

// Parameters
$action = GETPOST('action', 'alpha');


/*
 * Actions
 */
if (preg_match('/set_(.*)/',$action,$reg))
{
	$code=$reg[1];
	$value=GETPOST($code);
	if ($code == 'FULLCALENDAR_SCHEDULER_RESOURCES_TYPE_ALLOWED') $value = implode(',',$value);
	
	if (dolibarr_set_const($db, $code, $value, 'chaine', 0, '', $conf->entity) > 0)
	{
		header("Location: ".$_SERVER["PHP_SELF"]);
		exit;
	}
	else
	{
		dol_print_error($db);
	}
}
	
if (preg_match('/del_(.*)/',$action,$reg))
{
	$code=$reg[1];
	if (dolibarr_del_const($db, $code, 0) > 0)
	{
		Header("Location: ".$_SERVER["PHP_SELF"]);
		exit;
	}
	else
	{
		dol_print_error($db);
	}
}

/*
 * View
 */
$page_name = "fullcalendarschedulerSetup";
llxHeader('', $langs->trans($page_name));

// Subheader
$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php">'
    . $langs->trans("BackToModuleList") . '</a>';
print_fiche_titre($langs->trans($page_name), $linkback);

// Configuration header
$head = fullcalendarschedulerAdminPrepareHead();
dol_fiche_head(
    $head,
    'settings',
    $langs->trans("Module104852Name"),
    0,
    "fullcalendarscheduler@fullcalendarscheduler"
);

// Setup page goes here
$form=new Form($db);
$var=false;
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Parameters").'</td>'."\n";
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="100">'.$langs->trans("Value").'</td>'."\n";

$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("FULLCALENDARSCHEDULER_LOCALE_LANG").'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="right" width="300">';
print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="set_FULLCALENDARSCHEDULER_LOCALE_LANG">';
print '<input name="FULLCALENDARSCHEDULER_LOCALE_LANG" type="text" value="'.$conf->global->FULLCALENDARSCHEDULER_LOCALE_LANG.'" placeholder="fr" />';
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print '</form>';
print '</td></tr>';

$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("FULLCALENDAR_SCHEDULER_RESOURCES_TYPE_ALLOWED").'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="right" width="300">';
print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="set_FULLCALENDAR_SCHEDULER_RESOURCES_TYPE_ALLOWED">';
print $form->multiselectarray('FULLCALENDAR_SCHEDULER_RESOURCES_TYPE_ALLOWED', getAllCodeResource(), explode(',', $conf->global->FULLCALENDAR_SCHEDULER_RESOURCES_TYPE_ALLOWED),  0, 0, 'minwidth200');
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print '</form>';
print '</td></tr>';

$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("FULLCALENDARSCHEDULER_BUSINESSHOURS_WEEK_START").'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="right" width="300">';
print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="set_FULLCALENDARSCHEDULER_BUSINESSHOURS_WEEK_START">';
print '<input name="FULLCALENDARSCHEDULER_BUSINESSHOURS_WEEK_START" type="text" value="'.$conf->global->FULLCALENDARSCHEDULER_BUSINESSHOURS_WEEK_START.'" placeholder="08:00" />';
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print '</form>';
print '</td></tr>';

$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("FULLCALENDARSCHEDULER_BUSINESSHOURS_WEEK_END").'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="right" width="300">';
print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="set_FULLCALENDARSCHEDULER_BUSINESSHOURS_WEEK_END">';
print '<input name="FULLCALENDARSCHEDULER_BUSINESSHOURS_WEEK_END" type="text" value="'.$conf->global->FULLCALENDARSCHEDULER_BUSINESSHOURS_WEEK_END.'" placeholder="18:00" />';
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print '</form>';
print '</td></tr>';

$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("FULLCALENDARSCHEDULER_BUSINESSHOURS_WEEKEND_START").'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="right" width="300">';
print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="set_FULLCALENDARSCHEDULER_BUSINESSHOURS_WEEKEND_START">';
print '<input name="FULLCALENDARSCHEDULER_BUSINESSHOURS_WEEKEND_START" type="text" value="'.$conf->global->FULLCALENDARSCHEDULER_BUSINESSHOURS_WEEKEND_START.'" placeholder="10:00" />';
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print '</form>';
print '</td></tr>';

$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("FULLCALENDARSCHEDULER_BUSINESSHOURS_WEEKEND_END").'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="right" width="300">';
print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="set_FULLCALENDARSCHEDULER_BUSINESSHOURS_WEEKEND_END">';
print '<input name="FULLCALENDARSCHEDULER_BUSINESSHOURS_WEEKEND_END" type="text" value="'.$conf->global->FULLCALENDARSCHEDULER_BUSINESSHOURS_WEEKEND_END.'" placeholder="16:00" />';
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print '</form>';
print '</td></tr>';

$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("FULLCALENDARSCHEDULER_DEFAULTTIMEDEVENTDURATION").'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="right" width="300">';
print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="set_FULLCALENDARSCHEDULER_DEFAULTTIMEDEVENTDURATION">';
print '<input name="FULLCALENDARSCHEDULER_DEFAULTTIMEDEVENTDURATION" type="text" value="'.$conf->global->FULLCALENDARSCHEDULER_DEFAULTTIMEDEVENTDURATION.'" placeholder="00:01:00" />';
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print '</form>';
print '</td></tr>';

$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("FULLCALENDARSCHEDULER_SNAP_DURATION").'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="right" width="300">';
print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="set_FULLCALENDARSCHEDULER_SNAP_DURATION">';
print '<input name="FULLCALENDARSCHEDULER_SNAP_DURATION" type="text" value="'.$conf->global->FULLCALENDARSCHEDULER_SNAP_DURATION.'" placeholder="00:30:00" />';
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print '</form>';
print '</td></tr>';

/*
$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("ParamLabel").'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="300">';
print ajax_constantonoff('CONSTNAME');
print '</td></tr>';
*/

print '</table>';

llxFooter();

$db->close();