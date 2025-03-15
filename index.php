<?php

require(__DIR__ . '/../../config.php');

use \local_tenant\xmldb\xmldb_file_hack;

// $file = new xmldb_file_hack('C:\\wamp64\\moodledatas\\lfx\\moodledata\\tenantplugins\\quacker\\db\\install.xml');
// $file->loadXMLStructure();
// echo "<pre>";
// print_r($file->getStructure());

$plugintypes = \core_component::get_plugin_types();
var_dump($plugintypes);

// $dbman = $DB->get_manager()->install_from_xmldb_structure($file->getStructure());