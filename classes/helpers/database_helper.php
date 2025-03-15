<?php namespace local_tenant\helpers;

use \ddl_exception;

class database_helper {

    /**
     * This function will load one entire XMLDB file and call 
     * the database manager's install_from_xmldb_structure.
     *
     * @param string $file full path to the XML file to be used
     * @return void
     */
    public static function install_from_xmldb_file(string $filepath){
        global $DB;

        $xmldb_file = new \local_tenant\xmldb\xmldb_file_hack($filepath);
        $xmldb_file->loadXMLStructure();
        $DB->get_manager()->install_from_xmldb_structure($xmldb_file->getStructure());
    }


    /**
     * This function will delete all tables found in XMLDB file from db
     *
     * @param string $file Full path to the XML file to be used.
     * @return void
     */
    public static function delete_tables_from_xmldb_file(string $filepath) {
        global $DB;

        $xmldb_file = new \local_tenant\xmldb\xmldb_file_hack($filepath);

        if (!$xmldb_file->fileExists()) {
            throw new ddl_exception('ddlxmlfileerror', null, 'File does not exist');
        }

        $loaded    = $xmldb_file->loadXMLStructure();
        $structure = $xmldb_file->getStructure();

        if (!$loaded || !$xmldb_file->isLoaded()) {
            // Show info about the error if we can find it
            if ($structure) {
                if ($errors = $structure->getAllErrors()) {
                    throw new ddl_exception('ddlxmlfileerror', null, 'Errors found in XMLDB file: '. implode (', ', $errors));
                }
            }
            throw new ddl_exception('ddlxmlfileerror', null, 'not loaded??');
        }

        $db_manager = $DB->get_manager();

        if ($xmldb_tables = $structure->getTables()) {
            // Delete in opposite order, this should help with foreign keys in the future.
            $xmldb_tables = array_reverse($xmldb_tables);
            foreach($xmldb_tables as $table) {
                if ($db_manager->table_exists($table)) {
                    $db_manager->drop_table($table);
                }
            }
        }
    }
}
