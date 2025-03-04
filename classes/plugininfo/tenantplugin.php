<?php namespace local_tenant\plugininfo;

class tenantplugin extends \core\plugininfo\base {
    public function __construct() {
        global $CFG;

        $this->typerootdir = "$CFG->dataroot/tenantplugins";
        $this->rootdir = "$this->typerootdir";

        
    }

    public function load_disk_version() {
        global $CFG;

        $this->typerootdir = "$CFG->dataroot/tenantplugins";
        $this->rootdir = "$this->typerootdir/$this->name";
        
        return parent::load_disk_version();
    }
}