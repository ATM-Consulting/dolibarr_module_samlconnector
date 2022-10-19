<?php

use OneLogin\Saml2\Metadata;

const NOLOGIN = true;

// Load Dolibarr environment
$res = 0;
$main_inc = 'main.inc.php';
for($i = 0 ; $i < 5 && ! $res ; $i++) $res = @include str_repeat('../', $i).$main_inc;

require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once __DIR__.'/lib/autoload.php';
require_once __DIR__.'/lib/samlconnectorSettings.php';

header('Content-Type: application/xml');
$data = Metadata::builder(saml_settings()['sp']);
echo $data;
