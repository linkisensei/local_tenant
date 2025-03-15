<?php

function local_tenant_after_config(){
    require_once(__DIR__ . '/locallib.php');
    apply_patches_during_phpunit_buildconfig();
}