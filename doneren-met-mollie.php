<?php
/*
Plugin Name: Doneren met Mollie
Description: Donaties ontvangen via Mollie
Version: 2.1.4
Author: Nick Dijkstra
Author URI: http://nickdijkstra.nl
Text Domain: doneren-met-mollie
Domain Path: /languages/
*/

if (!defined('ABSPATH')) {
    die('Please do not load this file directly!');
}

// Plugin Version
if (!defined('DMM_VERSION')) {
    define('DMM_VERSION', '2.0.0');
}

// Plugin Folder Path
if (!defined('DMM_PLUGIN_PATH')) {
    define('DMM_PLUGIN_PATH', plugin_dir_path(__FILE__));
}

define('DMM_PLUGIN_BASE', plugin_basename(__FILE__));

global $wpdb;

// Includes
require_once DMM_PLUGIN_PATH . 'includes/config.php';
require_once DMM_PLUGIN_PATH . 'includes/class-webhook.php';
require_once DMM_PLUGIN_PATH . 'includes/class-start.php';

if(!class_exists('Mollie_API_Client'))
    require_once DMM_PLUGIN_PATH . 'libs/mollie-api-php/src/Mollie/API/Autoloader.php';

$dmm_webook = new Dmm_Webhook();
$dmm = new Dmm_Start();

// Admin includes and functions
if (is_admin()) {
    if(!class_exists('WP_List_Table'))
        require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';

    require_once DMM_PLUGIN_PATH . 'includes/class-donations-table.php';
    require_once DMM_PLUGIN_PATH . 'includes/class-donors-table.php';
    require_once DMM_PLUGIN_PATH . 'includes/class-subscriptions-table.php';
    require_once DMM_PLUGIN_PATH . 'includes/class-admin.php';

    $dmm_admin = new Dmm_Admin();
}

// Register hook
register_activation_hook(__FILE__, array($dmm, 'dmm_install_database'));
register_uninstall_hook(__FILE__, 'dmm_uninstall_database');

function dmm_uninstall_database()
{
    global $wpdb;
    $table_name = DMM_TABLE_DONATIONS;
    $table_name1 = DMM_TABLE_DONORS;
    $table_name2 = DMM_TABLE_SUBSCRIPTIONS;

    delete_option('dmm_plugin_version');

    $wpdb->query("DROP TABLE IF EXISTS $table_name");
    $wpdb->query("DROP TABLE IF EXISTS $table_name1");
    $wpdb->query("DROP TABLE IF EXISTS $table_name2");
}

function dmm_load_locale() {
    load_plugin_textdomain(DMM_TXT_DOMAIN, false, dirname(plugin_basename(__FILE__)) . '/languages/');
}
add_action('plugins_loaded', 'dmm_load_locale');