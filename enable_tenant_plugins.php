<?php

// This file must be included in the config.php, before "require_once(__DIR__ . '/lib/setup.php');"

if(!defined('MOODLE_INTERNAL')) {
    define('MOODLE_INTERNAL', true);
}

require_once(__DIR__ . '/../../lib/classes/component.php');

if(empty($CFG->running_installer)){
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
    private static bool $patched = false;

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
        self::patch_core_component();
    }

    protected static function inject_tenantplugins(){
        if(self::$injected){
            return;
        }

        // Attempt to inject from cache
        if(!CACHE_DISABLE_ALL and !self::is_developer()){
            if(self::init_tenants_from_cache()){
                self::$injected = true;
                return;
            }
        }

        // Makes cache and inject
        $cache = self::make_tentant_cache();
        self::init_tenants_from_cache($cache);
        self::write_tenant_component_cache($cache);
        self::$injected = true;
    }

    protected static function get_tenant_component_cache_filepath(){
        global $CFG;
        return "$CFG->cachedir/tenant_components.php";
    }

    protected static function write_tenant_component_cache(array $cache){
        $cachefile = self::get_tenant_component_cache_filepath();
        $content = '<?php $cache = '.var_export($cache, true).';';

        // Permissions might not be setup properly in installers.
        $dirpermissions = !isset($CFG->directorypermissions) ? 02777 : $CFG->directorypermissions;
        $filepermissions = !isset($CFG->filepermissions) ? ($dirpermissions & 0666) : $CFG->filepermissions;

        clearstatcache();
        $cachedir = dirname($cachefile);
        if (!is_dir($cachedir)) {
            mkdir($cachedir, $dirpermissions, true);
        }

        if ($fp = @fopen($cachefile.'.tmp', 'xb')) {
            fwrite($fp, $content);
            fclose($fp);
            @rename($cachefile.'.tmp', $cachefile);
            @chmod($cachefile, $filepermissions);
        }
        @unlink($cachefile.'.tmp'); // Just in case anything fails (race condition).
        self::invalidate_opcode_php_cache($cachefile);
    }

    protected static function read_tenant_component_cache() : array {
        $cachefile = self::get_tenant_component_cache_filepath();

        if(is_readable($cachefile)){
            include($cachefile);
            return $cache;
        }

        return [];
    }

    protected static function make_tentant_cache() : array {
        $cache = [
            'subplugins' => [
                self::COMPONENT => [],
            ],
            'plugins' => [
                self::TENANTPLUGINS_TYPE => [],
            ],
            'filemap' => [],
            'classmap' => [],
        ];
    
        $backup = self::$classmap;  // Backuping previous classmaps
        self::$classmap = []; // For self::load_classes()
    
        $plugins = self::fetch_plugins(self::TENANTPLUGINS_TYPE, self::get_tentant_plugins_dir());
        foreach ($plugins as $pluginname => $fulldir) {
            if(!isset($cache['subplugins'][self::COMPONENT][self::TENANTPLUGINS_TYPE])){
                $cache['subplugins'][self::COMPONENT][self::TENANTPLUGINS_TYPE] = [];
            }
    
            $cache['plugins'][self::TENANTPLUGINS_TYPE][$pluginname] = $fulldir;
            $cache['subplugins'][self::COMPONENT][self::TENANTPLUGINS_TYPE][$pluginname] = $fulldir;

            self::load_classes(self::TENANTPLUGINS_TYPE.'_'.$pluginname, "$fulldir/classes");
    
            // Mapping files (for get_plugins_with_function())
            foreach (self::$filestomap as $file) {
                if (file_exists("$fulldir/$file")) {
                    $cache['filemap'][$file][self::TENANTPLUGINS_TYPE][$pluginname] = "$fulldir/$file";
                }
            }
        }
    
        $cache['classmap'] = self::$classmap;
        self::$classmap = $backup; // Restauring classmap
        return $cache;
    }

    protected static function init_tenants_from_cache(?array $cache = null) : bool {
        if($cache === null){
            $cache = self::read_tenant_component_cache();
        }

        if(empty($cache)){
            return false;
        }

        if(!isset(self::$plugins[self::TENANTPLUGINS_TYPE])){
            self::$plugins[self::TENANTPLUGINS_TYPE] = [];
        }

        if(!isset(self::$subplugins[self::COMPONENT])){
            self::$subplugins[self::COMPONENT] = [];
        }

        // Appending plugin locations
        self::$plugins[self::TENANTPLUGINS_TYPE] = array_merge(
            self::$plugins[self::TENANTPLUGINS_TYPE],
            $cache['plugins'][self::TENANTPLUGINS_TYPE]
        );
        
        // Appending subplugins
        self::$subplugins[self::COMPONENT] = array_merge(
            self::$subplugins[self::COMPONENT],
            $cache['subplugins'][self::COMPONENT]
        );
        
        // Appending files to filemap
        foreach ($cache['filemap'] as $file => $plugins) {
            if(!empty($cache['filemap'][$file])){
                self::$filemap[$file] = array_merge(self::$filemap[$file], $cache['filemap'][$file]);
            }
        }

        // Appending classes to classmap
        self::$classmap = array_merge(self::$classmap, $cache['classmap']);

        // Fixing tentant plugins directory
        self::$plugintypes[self::TENANTPLUGINS_TYPE] = self::get_tentant_plugins_dir();
        return true;
    }

    /**
     * The core_component class still used in other parts
     * of moodle. This patch ensures that:
     * 
     * - Tenant plugins are installable via zip upload.
     *
     * @return void
     */
    protected static function patch_core_component(){
        if(self::$patched){
            return;
        }

        $refection_class = new \ReflectionClass(core_component::class);

        // Adding the correct tenant plugins directory
        $prop = $refection_class->getProperty('plugintypes');
        $prop->setAccessible(true);

        $plugintypes = $prop->getValue();
        if(!empty($plugintypes)){
            $plugintypes[self::TENANTPLUGINS_TYPE] = self::get_tentant_plugins_dir();
            $prop->setValue(null, $plugintypes);
        }

        self::$patched = true;
    }
}