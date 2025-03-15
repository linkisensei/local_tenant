<?php namespace local_tenant\plugininfo;

class tenantplugin extends \core\plugininfo\base {
    public function __construct() {
        global $CFG;

        require_once($CFG->dirroot . '/local/tenant/locallib.php');

        $this->typerootdir = get_tenant_plugins_location();
        $this->rootdir = "$this->typerootdir";        
    }

    public function load_disk_version() {
        global $CFG;

        // $this->typerootdir = get_tenant_plugins_location();
        $this->rootdir = "$this->typerootdir/$this->name";
        
        return parent::load_disk_version();
    }

    /**
     * Since the default install.xml doesn't work with
     * tenantplugins, we will uninstall tables from _install.xml
     *
     * @return void
     */
    public function uninstall_cleanup(){
        $filepath = $this->typerootdir . '/db/_install.xml';
        if(file_exists($filepath)){
            \local_tenant\helpers\database_helper::delete_tables_from_xmldb_file($filepath);
        }
    }
}