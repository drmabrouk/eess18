<?php
/**
 * Plugin Name: School Management (إدارة المدرسة)
 * Description: نظام شامل لإدارة السلوك، المخالفات، والتقارير المدرسية.
 * Version: 97.0.0
 * Author: AHMED MABROUK
 * Language: ar
 * Text Domain: school-management
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit;
}

define('SM_VERSION', '97.0.0');
define('SM_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SM_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * The code that runs during plugin activation.
 */
function activate_school_management() {
    require_once SM_PLUGIN_DIR . 'includes/class-sm-activator.php';
    SM_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 */
function deactivate_school_management() {
    require_once SM_PLUGIN_DIR . 'includes/class-sm-deactivator.php';
    SM_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_school_management');
register_deactivation_hook(__FILE__, 'deactivate_school_management');

/**
 * Core class used to maintain the plugin.
 */
require_once SM_PLUGIN_DIR . 'includes/class-school-management.php';

function run_school_management() {
    $plugin = new School_Management();
    $plugin->run();
}

run_school_management();
