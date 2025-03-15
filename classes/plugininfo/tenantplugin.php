<?php namespace local_tenant\plugininfo;

class tenantplugin extends \core\plugininfo\base {
    public function __construct() {
        global $CFG;

        require_once($CFG->dirroot . '/local/tenant/locallib.php');

        $this->typerootdir = get_tenant_plugins_location();
        $this->rootdir = "$this->typerootdir";
    }

    public function load_disk_version() {
        $this->rootdir = "$this->typerootdir/$this->name";        
        return parent::load_disk_version();
    }

    /**
     * Since the default install.xml doesn't work with
     * tenantplugins, it will uninstall tables from any valid
     * .xml file in the db directory.
     *
     * @return void
     */
    public function uninstall_cleanup(){
        $db_dir = $this->typerootdir . "/db";

        if(!file_exists($db_dir) || !is_dir($db_dir)){
            return;
        }

        foreach (glob("$db_dir/*.xml") as $filepath) {
            try {
                \local_tenant\helpers\database_helper::delete_tables_from_xmldb_file($filepath);
            } catch (\Throwable $th) {
                debugging($filepath . ' ' . $th->getMessage(), DEBUG_DEVELOPER);
            }
        }
    }
}