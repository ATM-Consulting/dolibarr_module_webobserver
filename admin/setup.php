<?php
/* Copyright (C) 2004-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2022 John Botella <john.botella@atm-consulting.fr>
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
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    webobserver/admin/setup.php
 * \ingroup webobserver
 * \brief   WebObserver setup page.
 */

// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) {
	$res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
}
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME']; $tmp2 = realpath(__FILE__); $i = strlen($tmp) - 1; $j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) {
	$i--; $j--;
}
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/main.inc.php")) {
	$res = @include substr($tmp, 0, ($i + 1))."/main.inc.php";
}
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php")) {
	$res = @include dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php";
}
// Try main.inc.php using relative path
if (!$res && file_exists("../../main.inc.php")) {
	$res = @include "../../main.inc.php";
}
if (!$res && file_exists("../../../main.inc.php")) {
	$res = @include "../../../main.inc.php";
}
if (!$res) {
	die("Include of main fails");
}

global $langs, $user;

// Libraries
require_once DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php";
require_once '../lib/webobserver.lib.php';
require_once "../class/jsonWebApiResponse.class.php";

// Translations
$langs->loadLangs(array("admin", "webobserver@webobserver"));

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('webobserversetup', 'globalsetup'));

// Access control
if (!$user->admin) {
	accessforbidden();
}

// Parameters
$action = GETPOST('action', 'aZ09');
$backtopage = GETPOST('backtopage', 'alpha');
$modulepart = GETPOST('modulepart', 'aZ09');	// Used by actions_setmoduleoptions.inc.php

$value = GETPOST('value', 'alpha');
$label = GETPOST('label', 'alpha');
$scandir = GETPOST('scan_dir', 'alpha');


if(file_exists(DOL_DOCUMENT_ROOT.'/core/class/html.formsetup.class.php')){
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formsetup.class.php';
}
else{
	require_once __DIR__ . '/../retrocompatibility/core/class/html.formsetup.class.php';
}

$error = 0;
$setupnotempty = 0;

// Set this to 1 to use the factory to manage constants. Warning, the generated module will be compatible with version v15+ only
$useFormSetup = 0;
// Convert arrayofparameter into a formSetup object

$formSetup = new FormSetup($db);


$item = $formSetup->newItem('WEBOBSERVER_HOOK_URL');
$item->fieldOutputOverride = dol_buildpath('/webobserver/public/get-data.php', 2);
$item->fieldInputOverride = $item->fieldOutputOverride;


// Setup conf webhost token
$formSetup->newItem('WEBOBSERVER_TOKEN')->setAsSecureKey();



$formSetup->newItem('WEBOBSERVER_CRON_CONF_TITLE')->setAsTitle();

$formSetup->newItem('WEBOBSERVER_INSTANCE_REF');

$formSetup->newItem('WEBOBSERVER_WEBHOST_URL');

$item = $formSetup->newItem('WEBOBSERVER_INSTANCE_ID');
$item->fieldAttr['type'] = 'number';
$item->fieldAttr['min'] = 1;
$item->fieldAttr['step'] = 1;

$item = $formSetup->newItem('WEBOBSERVER_INSTANCE_REF');


$formSetup->newItem('WEBOBSERVER_USE_GIT_AND_SHELL_CMD')->setAsYesNo();


$setupnotempty = count($formSetup->items);



/*
 * Actions
 */

include DOL_DOCUMENT_ROOT.'/core/actions_setmoduleoptions.inc.php';

if (intval(DOL_VERSION) < 15 && $action == 'update' && !empty($formSetup) && is_object($formSetup) && !empty($user->admin)) {
	$formSetup->saveConfFromPost();
	return;
}
elseif($action == 'sendWebHostPing' ){
	require_once __DIR__ . '/../class/webobserver.class.php';

	$errors = 0;

	$webObserver = new WebObserver();
	$webHostResponse = $webObserver->callWebHost();
	$textSrvResponse = $langs->trans('ServerResponse').' : <br/>';
	$srvPingMsg = '';

	if($webHostResponse){
		$jsonResponse = new WebObserver\JsonWebApiResponse();
		if($jsonResponse->parseJsonResponse($webHostResponse)){
			if($jsonResponse->result > 0){
				setEventMessage($textSrvResponse.$jsonResponse->msg);
			}else{
				setEventMessage($textSrvResponse.$jsonResponse->msg, 'errors');
			}
		}
		else{
			setEventMessage($textSrvResponse.$webObserver->error, 'errors');
		}
	}else{
		setEventMessage($textSrvResponse.$webObserver->error, 'errors');
	}
}
elseif($action == 'getDataSendForPing' ){
	require_once __DIR__ . '/../class/webobserver.class.php';

	$errors = 0;

	$webObserver = new WebObserver();
	$textSrvResponse = $langs->trans('DataSendByPing').' : <br/>';
	$instanceData = $webObserver->getInstanceData();
	$srvPingMsg = '';
	if($instanceData){
		$webHostResponse = json_encode($instanceData, JSON_PRETTY_PRINT);
	}
}

/*
 * View
 */

$form = new Form($db);

$help_url = '';
$page_name = "WebObserverSetup";

llxHeader('', $langs->trans($page_name), $help_url);

// Subheader
$linkback = '<a href="'.($backtopage ? $backtopage : DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1').'">'.$langs->trans("BackToModuleList").'</a>';

print load_fiche_titre($langs->trans($page_name), $linkback, 'title_setup');

// Configuration header
$head = webobserverAdminPrepareHead();
print dol_get_fiche_head($head, 'settings', $langs->trans($page_name), -1, "webobserver@webobserver");

// Setup page goes here
echo '<span class="opacitymedium">'.$langs->trans("WebObserverSetupPage").'</span><br><br>';


if ($action == 'edit') {
	print $formSetup->generateOutput(true);
} else {
	if ($setupnotempty) {
		print $formSetup->generateOutput();

		print '<div class="tabsAction">';
		print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=edit&token='.newToken().'">'.$langs->trans("Modify").'</a>';
		print '</div>';
	} else {
		print '<br>'.$langs->trans("NothingToSetup");
	}


	print '<div >';
	print '<a class="butAction" href="'.dol_buildpath("webobserver/admin/setup.php",1).'?action=sendWebHostPing&token='.newToken().'" >'.$langs->trans('SendWebHostPing').'</a>';
	print '<a class="butAction" href="'.dol_buildpath("webobserver/admin/setup.php",1).'?action=getDataSendForPing&token='.newToken().'" >'.$langs->trans('GetDataSendForPing').'</a>';
	print '</div>';

	if(!empty($webHostResponse)){
		print '<h4>'.$langs->trans('serverResponse').' :</h4>';
		preg_match_all("/(\n)/", dol_htmlentities($webHostResponse), $matches);
		$total_lines = count($matches[0]) + 1;
		print '<textarea disabled style="width: 100%; min-height: 400px;" rows="'.$total_lines.'" >'.dol_htmlentities($webHostResponse).'</textarea>';
	}

}


// Page end
print dol_get_fiche_end();

llxFooter();
$db->close();
