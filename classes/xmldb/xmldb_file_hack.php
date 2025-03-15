<?php namespace local_tenant\xmldb;

use \xmldb_file;

class xmldb_file_hack extends xmldb_file {
    public function arr2xmldb_structure ($xmlarr) {
        $structure = new \local_tenant\xmldb\xmldb_structure_hack($this->path);
        $structure->arr2xmldb_structure($xmlarr);
        return $structure;
    }
}
