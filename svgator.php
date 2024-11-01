<?php
/**
 * Plugin Name: SVGator
 * Plugin URI: https://www.svgator.com/help/getting-started/how-to-add-svg-to-wordpress
 * Description: Import your animated SVGs from SVGator.com
 * Version: 1.3.2
 * Author: SVGator
 * Author URI: https://www.svgator.com
 * License: GPL 2
 *
 * @package    Svgator.com
 */

/*
	Copyright 2020 Smartware INC.
	This program is free software; you can redistribute it
	under the terms of the GNU General Public License as published by
	the Free Software Foundation; either version 2 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS PARTICULAR PURPOSE.
	See GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA
*/

use WP_SVGator\Main;

//If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

const WP_SVGATOR_VERSION = '1.3.2';
define( 'WP_SVGATOR_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WP_SVGATOR_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require WP_SVGATOR_PLUGIN_DIR . 'includes/autoload.php';
Main::run();

register_deactivation_hook( __FILE__, [ '\WP_SVGator\Main', 'deactivator' ] );
