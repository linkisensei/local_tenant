<?php

/**
 * Executed on installation of local_tenant
 *
 * @return bool
 */
function xmldb_local_tenant_install() {
    require_once(__DIR__ . '/../enable_tenant_plugins.php');

    $plugins_dir = core_component_hack::get_tentant_plugins_dir();

    if (!is_dir($plugins_dir)) {
        return mkdir($plugins_dir, 0755, true);
    }

    return true;
}
