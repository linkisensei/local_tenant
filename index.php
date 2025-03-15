<?php

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/locallib.php');

$base_uri = (new moodle_url('/local/tenant'))->out();
$requested_uri = qualified_me();
$route =  str_replace($base_uri, '', $requested_uri);

if(!include_tenant_file($route)){
    header("HTTP/1.1 404 Not Found");
    exit();
}