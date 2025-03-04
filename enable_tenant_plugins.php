<?php

// This file must be included in the config.php, before "require_once(__DIR__ . '/lib/setup.php');"

if(!defined('MOODLE_INTERNAL')) {
    define('MOODLE_INTERNAL', true);
}

require_once(__DIR__ . '/../../lib/classes/component.php');

if(empty($CFG->running_installer) && !empty($CFG->rolesactive)){
    define('COMPONENT_CLASSLOADER', 'core_component_hack::classloader');
}

/**
 * This call will replace the default moodle autoloader.
 * 
 * It will alter the in memory directory of the "tenantplugin'
 * subplugins to the moodledata.
 * 
 * It will also make sure the plugin is detected by callbacks (lib.php)
 * and all features related to subplugins.
 */
class core_component_hack extends core_component {

    private static bool $injected = false;

    const TENANTPLUGINS_TYPE = 'tenantplugin';
    const COMPONENT = 'local_tenant';

    public static function get_tentant_plugins_dir() : string {
        global $CFG;
        return "$CFG->dataroot/tenantplugins";
    }

    public static function classloader($classname) {
        static::init();
        parent::classloader($classname);
    }

    protected static function init() {
        parent::init();
        self::inject_tenantplugins();
    }

    protected function inject_tenantplugins(){
        if(self::$injected){
            return;
        }

        $plugins = self::fetch_plugins(self::TENANTPLUGINS_TYPE, self::get_tentant_plugins_dir());
        foreach ($plugins as $pluginname => $fulldir) {
            if(!isset(self::$subplugins[self::COMPONENT])){
                self::$subplugins[self::COMPONENT] = [];
            }

            if(!isset(self::$subplugins[self::COMPONENT][self::TENANTPLUGINS_TYPE])){
                self::$subplugins[self::COMPONENT][self::TENANTPLUGINS_TYPE] = [];
            }

            if(!isset(self::$plugins[self::TENANTPLUGINS_TYPE])){
                self::$plugins[self::TENANTPLUGINS_TYPE] = [];
            }

            self::$plugins[self::TENANTPLUGINS_TYPE][$pluginname] = $fulldir;
            self::$subplugins[self::COMPONENT][self::TENANTPLUGINS_TYPE][$pluginname] = $fulldir;
            self::load_classes(self::TENANTPLUGINS_TYPE.'_'.$pluginname, "$fulldir/classes");

            // Mapping files (for get_plugins_with_function())
            foreach (self::$filestomap as $file) {
                if (file_exists("$fulldir/$file")) {
                    self::$filemap[$file][self::TENANTPLUGINS_TYPE][$pluginname] = "$fulldir/$file";
                }
            }
        }
        self::$injected = true;
    }

}