<?php

function local_tenant_after_config(){
    if(defined('PHPUNIT_TEST') && PHPUNIT_TEST){
        require_once(__DIR__ . '/locallib.php');
        apply_patches_during_phpunit_buildconfig();
    }
}