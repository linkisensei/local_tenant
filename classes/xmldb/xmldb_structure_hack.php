<?php namespace local_tenant\xmldb;

use \xmldb_structure;
use \xmldb_table;

class xmldb_structure_hack extends xmldb_structure {
    /**
     * Load data from XML to the structure
     * @param array $xmlarr
     * @return bool
     */
    public function arr2xmldb_structure($xmlarr) {

        global $CFG;

        $result = true;

        // Process structure attributes (path, comment and version)
        if (isset($xmlarr['XMLDB']['@']['PATH'])) {
            $this->path = trim($xmlarr['XMLDB']['@']['PATH']);
        } else {
            $this->errormsg = 'Missing PATH attribute';
            $this->debug($this->errormsg);
            $result = false;
        }

        if (isset($xmlarr['XMLDB']['@']['VERSION'])) {
            $this->version = trim($xmlarr['XMLDB']['@']['VERSION']);
        } else {
            $this->errormsg = 'Missing VERSION attribute';
            $this->debug($this->errormsg);
            $result = false;
        }
        if (isset($xmlarr['XMLDB']['@']['COMMENT'])) {
            $this->comment = trim($xmlarr['XMLDB']['@']['COMMENT']);
        } else if (!empty($CFG->xmldbdisablecommentchecking)) {
            $this->comment = '';
        } else {
            $this->errormsg = 'Missing COMMENT attribute';
            $this->debug($this->errormsg);
            $result = false;
        }

        // Iterate over tables
        if (isset($xmlarr['XMLDB']['#']['TABLES']['0']['#']['TABLE'])) {
            foreach ($xmlarr['XMLDB']['#']['TABLES']['0']['#']['TABLE'] as $xmltable) {
                if (!$result) { //Skip on error
                    continue;
                }
                $name = trim($xmltable['@']['NAME']);
                $table = new xmldb_table($name);
                $table->arr2xmldb_table($xmltable);
                $this->tables[] = $table;
                if (!$table->isLoaded()) {
                    $this->errormsg = 'Problem loading table ' . $name;
                    $this->debug($this->errormsg);
                    $result = false;
                }
            }
        } else {
            $this->errormsg = 'Missing TABLES section';
            $this->debug($this->errormsg);
            $result = false;
        }

        // Perform some general checks over tables
        if ($result && $this->tables) {
            // Check tables names are ok (lowercase, a-z _-)
            if (!$this->checkNameValues($this->tables)) {
                $this->errormsg = 'Some TABLES name values are incorrect';
                $this->debug($this->errormsg);
                $result = false;
            }
            // Compute prev/next.
            $this->fixPrevNext($this->tables);
            // Order tables
            if ($result && !$this->orderTables($this->tables)) {
                $this->errormsg = 'Error ordering the tables';
                $this->debug($this->errormsg);
                $result = false;
            }
        }

        // Set some attributes
        if ($result) {
            $this->loaded = true;
        }
        $this->calculateHash();
        return $result;
    }
}
