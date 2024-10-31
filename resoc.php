<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://resoc.io
 * @since             1.0.0
 * @package           Resoc
 *
 * @wordpress-plugin
 * Plugin Name:       Resoc Social Images
 * Plugin URI:        https://resoc.io/resoc-uri/
 * Description:       Improve the images used to illustrate your content when it is share on social networks and messaging services.
 * Version:           1.1.0
 * Author:            Resoc
 * Author URI:        https://resoc.io/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       resoc
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'RESOC_VERSION', '1.1.0' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-resoc-activator.php
 */
function activate_resoc() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-resoc-activator.php';
	Resoc_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-resoc-deactivator.php
 */
function deactivate_resoc() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-resoc-deactivator.php';
	Resoc_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_resoc' );
register_deactivation_hook( __FILE__, 'deactivate_resoc' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-resoc.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_resoc() {

	$plugin = new Resoc();
	$plugin->run();

}
run_resoc();

function resoc_plugin_base_name() {
  return plugin_basename( __FILE__ );
};
