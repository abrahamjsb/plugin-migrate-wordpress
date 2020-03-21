<?php
/**
 * Plugin Name:       Primicias24 migration 
 * Plugin URI:        https://example.com/plugins/the-basics/
 * Description:       This plugin is made to migrate the former cms of primicias24 to the new database in wordpress format
 * Version:           1.0
 * Requires at least: 5.2
 * Requires PHP:      7.2
 * Author:            Abraham Sánchez
 * Author URI:        https://asdev.com.ve
 * License:           GPL
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       wpp24-migration
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
define( 'PLUGIN_NAME_VERSION', '1.0.0' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-primicias-main.php';


/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_primicias_migration() {

	$plugin = new Primicias\Migration\PrimiciasMain();
	$plugin->run();

}
run_primicias_migration();