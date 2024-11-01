<?php
/**
 * Fired during plugin deactivation
 * @since      0.0.1
 * @package    Svgator.com
 * @subpackage Svgator.com/includes
 */

namespace WP_SVGator;

/**
 * Fired during plugin deactivation.
 * This class defines all code necessary to run during the plugin's deactivation.
 * @since      0.0.1
 * @package    Svgator.com
 * @subpackage Svgator.com/includes
 * @author     SVGator.com <contact@svgator.com>
 */
class Deactivator {

	/**
	 * Called when the plugin is deactivated
	 * @since 0.0.1
	 */
	public static function deactivate() {
		$users = get_users();
		foreach ( $users as $user ) {
			delete_user_option( $user->ID, 'svgator_api' );
		}
	}

}
