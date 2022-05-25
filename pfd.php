<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://www.fiverr.com/junaidzx90
 * @since             1.0.0
 * @package           Pfd
 *
 * @wordpress-plugin
 * Plugin Name:       Post for a Day
 * Plugin URI:        https://www.fiverr.com
 * Description:       This is a short description of what the plugin does. It's displayed in the WordPress admin area.
 * Version:           1.0.0
 * Author:            junaidzx90
 * Author URI:        https://www.fiverr.com/junaidzx90
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       pfd
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
define( 'PFD_VERSION', '1.0.2' );
define( 'PFD_URL', plugin_dir_url( __FILE__ ) );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-pfd-activator.php
 */
function activate_pfd() {
	global $wpdb;
	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

	$pfd_courses = "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}pfd_courses` (
		`ID` INT NOT NULL AUTO_INCREMENT,
		`name` VARCHAR(255) NOT NULL,
		`course_tag` VARCHAR(255) NOT NULL,
		`date` DATE NOT NULL,
		`duration` INT NOT NULL,
		`working_days` INT NOT NULL,
		`temp_category` INT NOT NULL,
		`course_lines` LONGTEXT NOT NULL,
		`created` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
		PRIMARY KEY (`ID`)) ENGINE = InnoDB";
	dbDelta($pfd_courses);

	if ( ! wp_next_scheduled( 'pfd_cat_replace_cron' ) ) {
		wp_schedule_event( time(), 'half_hour', 'pfd_cat_replace_cron');
	}
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-pfd-deactivator.php
 */
function deactivate_pfd() {
	
}

register_activation_hook( __FILE__, 'activate_pfd' );
register_deactivation_hook( __FILE__, 'deactivate_pfd' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-pfd.php';

if( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

require plugin_dir_path( __FILE__ ) . 'includes/templates/class-courses-table.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_pfd() {

	$plugin = new Pfd();
	$plugin->run();

}
run_pfd();
