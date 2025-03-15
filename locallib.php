<?php

function get_tenant_plugins_location() : string {
    global $CFG;

    static $path = '';

    if(!$path){
        if(defined("PHPUNIT_TEST") && PHPUNIT_TEST){
            $env_path = getenv("TENANT_PLUGINS_CUSTOM_DIR");
            if(!empty($env_path) && file_exists($env_path)){
                $CFG->tenant_plugins_custom_dir = getenv("TENANT_PLUGINS_CUSTOM_DIR");
            }
        }

        if(!empty($CFG->tenant_plugins_custom_dir) && file_exists($CFG->tenant_plugins_custom_dir)){
            $path = $CFG->tenant_plugins_custom_dir;
        }else{
            $path = "$CFG->dataroot/tenantplugins";
        }
    }

    return $path;
}

function apply_patches_during_phpunit_buildconfig(){
    if(!defined('PHPUNIT_TEST') || !PHPUNIT_TEST){
        return;
    }

    if(!in_array('--buildconfig', $_SERVER['argv'])){
        return;
    }

    patch_tenants_path_in_phpunit_xml();
}

function patch_tenants_path_in_phpunit_xml(){
    global $CFG;

    $real_path = get_tenant_plugins_location();
    $broken_path = substr($real_path, strlen($CFG->dirroot) + 1);
    $filepath = "$CFG->dirroot/phpunit.xml";

    register_shutdown_function(function () use ($filepath, $broken_path, $real_path) {
        if(!is_writable($filepath)){
            return;
        }

        $content = file_get_contents($filepath);
        if ($content === false) {
            return;
        }

        if(str_contains($content, $real_path)){
            return;
        }

        $patched_content = str_replace($broken_path, $real_path, $content);
        file_put_contents($filepath, $patched_content);
    });
}

function include_tenant_file(string $relative_path) : bool {
    global $PAGE, $CFG, $DB, $OUTPUT, $SITE, $COURSE;

    $base = realpath(get_tenant_plugins_location());
    $path = $base . '/' . trim($relative_path, '/');
    $real_path = realpath($path);

    if($real_path === false || strpos($real_path, $base) !== 0) {
        return false; // Only includes files inside $base;
    }

    if(file_exists($real_path) && is_dir($real_path)){
        if(file_exists("$real_path/index.php")){
            $real_path = "$real_path/index.php";
        }elseif(file_exists("$real_path/index.html")){
            $real_path = "$real_path/index.html";
        }else{
            return false;
        }
    }

    if(is_readable($real_path)){
        include($real_path);
        return true;
    }

    return false;
}