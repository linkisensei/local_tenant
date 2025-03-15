<?php namespace local_tenant\helpers;

use \moodle_url;

class url_helper {
    public static function make_tenant_url(
        string $plugin,
        string $relative_path = '/',
        ?array $params = null,
        ?string $anchor = null
    ) : moodle_url {
        $url = '/local/tenant/' . trim($plugin, '/') . '/' . trim($relative_path, '/');
        return new moodle_url($url, $params, $anchor);
    }
}
