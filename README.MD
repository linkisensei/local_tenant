
# Local Tenant - Moodle Plugin

The **Local Tenant** plugin was developed to address a specific need within Moodle: allowing the installation of `tenantplugin` subplugins directly in the `moodledata`.

> I understand that this plugin's concept is unconventional and deviates from Moodle's standard architecture. However, I have a very specific use case where multiple tenants share the same Moodle files and cannot have individual customizations.
> By implementing subplugins of type tenantplugin, it becomes possible to apply different customizations for each tenant without modifying the core Moodle files. If they each have your own moodledata.

**WARNING! This plugin is not compatible with instances that already use another custom autoloader (defined via the COMPONENT_CLASSLOADER constant).**


## 📌 Features
- Enables the installation of `tenantplugin` subplugins in `moodledata`.
- Integrates seamlessly with Moodle's standard workflow without requiring complex structural changes.
- Uses a component cache file similar to the core_components.
- Enables plugin files to be accessible via browser (like `/local/tenant/quacker/index.php`).

### Supported Moodle plugin features
- ✔️ lib.php callbacks  
- ✔️ Scheduled tasks  
- ✔️ Custom Events  
- ✔️ Event observers  
- ✔️ Moodle cache  
- ✔️ Capabilities  
- ✔️ Lang strings  
- ✔️ Autoloading  
- ✔️ Plugin settings  
- ✔️ install.php  
- ✔️ Plugin configs  
- ✔️ PHPUnit tests 
- ✔️ Privacy provider  
- ✔️ upgrade.php  
- ✔️ uninstall.php
- ✔️ Installing subplugins via ZIP (using tool_installaddon)
- ✔️ Plugin renderer
- ✔️ Mustache templates
- ✔️ AMD modules
- ❌ db/install.xml (A helper is provided to initialize database tables during installation)


----------

## 📥 Installation

1.  Install the plugin normally in the `local/tenant` directory of your Moodle.
    
2.  Add the following line to your `config.php` file, **right before**  `require_once(__DIR__ . '/lib/setup.php');`:
    
    ```php
    require_once(__DIR__ . '/local/tenant/enable_tenant_plugins.php');
    ```
3.  Add the following line to your `config.php` file, **right after**  `require_once(__DIR__ . '/lib/setup.php');`:

    ```php
    require_once(__DIR__ . '/local/tenant/patch_abort_after_config_scripts.php');
    ```
4.  Access Moodle Administration and run the database upgrade.
    
5.  If necessary, clear Moodle's cache (MUC) to ensure proper functionality.
    
### Tenant plugins pages

Follow the instructions below according to your web server.

#### Apache (.htaccess)

For Apache users, add the following `.htaccess` file inside the `local/tenant` directory:

```apache
RewriteEngine On

RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^ index.php [QSA,L]
```

#### Nginx

For Nginx, you need to add the following configuration inside your server block:

```nginx
location /local/tenant/ {
    try_files $uri /local/tenant/index.php?$query_string;
}
```

#### Caddy

For Caddy users, add the following rule to your `Caddyfile`:

```caddy
route /local/tenant/* {
    try_files {path} /local/tenant/index.php?{query}
}
```

#### Lighttpd

For Lighttpd users, add the following rewrite rule:

```lighttpd
url.rewrite-if-not-file = (
    "^/local/tenant/(.*)$" => "/local/tenant/index.php?$1"
)
```


----------

## 🛠 Implementing Subplugins

To add a subplugin to Local Tenant, follow these steps:

1.  Develop a subplugin of type `tenantplugin`, following Moodle's official subplugin documentation: 📄 [Subplugins Documentation](https://docs.moodle.org/dev/Subplugins)
    
2.  Install the subplugin by placing it in the `moodledata/tenantplugins` directory.
    
3.  Access the **Site Administration** page to run the database upgrade.

> You can use the [tenantplugin_quacker](https://github.com/linkisensei/tenantplugin_quacker) plugin as inspiration.

----------

Due to a **path validation issue**, subplugins of type `tenantplugin` **cannot** contain the file `db/install.xml`.

### 🛠 **How to Install and Uninstall Tables Properly**

To **install** and **uninstall** database tables for the plugin, you need to:

1. **Rename** the `install.xml` file.
2. **Pass its absolute path** to the helper function below inside the installation function (`db/install.php`).

#### ✅ **Example: Installing Tables**

```php
// db/install.php
function xmldb_tenantplugin_PLUGINNAME_install() {
    \local_tenant\helpers\database_helper::install_from_xmldb_file(__DIR__ . '/dbinstall.xml');
}
```

#### ✅ **Example: Uninstalling Tables**
During **uninstallation**, all tables found in valid `.xml` files inside the `db/` folder will be **removed automatically**. 🗑️


----------

## ❓ Frequently Asked Questions

#### My subplugin did not appear, and Moodle did not prompt for an upgrade. What should I do?
-   Try clearing Moodle's cache (MUC).
-   Revisit the **Site Administration** page and check for pending updates.
-   Ensure the subplugin is correctly installed in the appropriate directory.

#### How do I build AMD modules?
- I couldn't find a proper pay to do this. So you must copy your plugin into local/tenant/fake and build it from there.

----------

## 📄 License

This plugin is distributed under the **GPLv3** license. For more information, check the `LICENSE` file.