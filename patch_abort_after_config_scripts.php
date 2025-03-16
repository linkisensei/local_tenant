<?php

if(defined('ABORT_AFTER_CONFIG') && ABORT_AFTER_CONFIG){
    $script_name = basename($_SERVER['SCRIPT_NAME']);
    $patchable_scripts = ['requirejs.php', 'javascript.php'];
    
    if(in_array($script_name, $patchable_scripts)){
        core_component_hack::patch_abort_after_config_script();
    }
}