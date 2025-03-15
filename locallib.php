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