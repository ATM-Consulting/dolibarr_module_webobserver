<?php

// To escape other module output
if (version_compare(PHP_VERSION, '5.4.0', '>=')) {
	ob_start(null, 0, PHP_OUTPUT_HANDLER_STDFLAGS ^ PHP_OUTPUT_HANDLER_REMOVABLE);
} else {
	ob_start(null, 0, false);
}

if(is_file('../main.inc.php'))$res = @include '../master.inc.php';
else  if(is_file('../../../main.inc.php'))$res = @include '../../../'.'master.inc.php';
else  if(is_file('../../../../main.inc.php'))$res = @include '../../../../master.inc.php';
else  if(is_file('../../../../../main.inc.php'))$res = @include '../../../../../master.inc.php';
else $res = @include '../../master.inc.php';

if (!$res) {
	die("Include of main fails");
}

// clean other modules print and failure
ob_clean();


require_once __DIR__ . '/../class/webobserver.class.php';

$webObserver = new WebObserver();

$webObserver->securityCheck();

print $webObserver::getInstanceJson();


ob_flush();
