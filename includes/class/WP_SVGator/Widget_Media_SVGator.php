<?php

namespace WP_SVGator;

require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php';
require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-direct.php';

use WP_Widget;
use WP_Filesystem_Direct;

/**
 * Widget API: WP_Widget_Media_Image class
 *
 * @package WordPress
 * @subpackage Widgets
 * @since 4.8.0
 */

/**
 * Core class that implements an image widget.
 *
 * @since 4.8.0
 *
 * @see WP_Widget_Media
 * @see WP_Widget
 */
class Widget_Media_SVGator extends WP_Widget {

	public function __construct() {
		add_action( 'admin_enqueue_scripts', Main::run()->enqueueScripts( 'WP_SVGatorWidget' ) );
		add_action( 'elementor/editor/before_enqueue_scripts', Main::run()->enqueueScripts( 'WP_SVGatorWidget' ) );

		parent::__construct(
			'media_svgator',
			'SVGator',
			array(
				'description' => __( 'Displays an animated SVG.' ),
				'mime_type'   => 'svgator',
			)
		);
	}

	private function makeResponsive( &$svg ) {
		$dimensionRemoved = false;
		$svg              = preg_replace_callback( '/<svg.*?>/', function ( $tag ) use ( &$dimensionRemoved ) {
			$tag              = $tag[0];
			$tag              = preg_replace( '/(?:width|height)=["\'].*?["\']/i', '', $tag, - 1, $count );
			$dimensionRemoved = $count > 0;
			$tag              = preg_replace( '/(?:width|height)=[0-9]+/i', '', $tag, - 1, $count );
			$dimensionRemoved = $dimensionRemoved || $count > 0;

			return $tag;
		}, $svg );

		return $dimensionRemoved;
	}

	private function getSvg( $attachmentId ) {
		$svgPath = $attachmentId ? get_attached_file( $attachmentId ) : false;
		// WP_Filesystem_Direct has an $args parameter that is mandatory, however it is not used
		$fs = new WP_Filesystem_Direct( [] );

		return $svgPath ? $fs->get_contents( $svgPath ) : false;
	}

	// Creating widget front-end
	public function widget( $args, $instance ) {
		$svg_safe = ! empty( $instance['attachment_id'] ) ? $this->getSvg( $instance['attachment_id'] ) : false;
		$svg_safe = Svg_Support::fixScript( $svg_safe );
		$this->makeResponsive( $svg_safe );
		if ( ! $svg_safe || ! Svg_Support::run()->belongsToSVGator( $svg_safe ) ) {
			return;
		}
		print wp_kses_post( $args['before_widget'] );
		if ( ! empty( $instance['title'] ) ) {
			print wp_kses_post( $args['before_title'] );
			print esc_textarea( $instance['title'] );
			print wp_kses_post( $args['after_title'] );
		}
		// SVG coming from SVGator through API is safe
		print $svg_safe; // phpcs:ignore WordPress.Security.EscapeOutput
		print wp_kses_post( $args['after_widget'] );
	}

	public function form( $instance ) {
		$id   = function ( $name ) {
			return $this->get_field_id( $name );
		};
		$name = function ( $name ) {
			return $this->get_field_name( $name );
		};

		$svg    = ! empty( $instance['attachment_id'] ) ? $this->getSvg( $instance['attachment_id'] ) : false;
		$svgUrl = ! empty( $instance['attachment_id'] ) ? wp_get_attachment_url( $instance['attachment_id'] ) : 'about:blank';
		if ( ! $svg ) {
			$hasDimension = false;
			$responsive   = '';
			$attachmentId = '';
			$title        = '';
		} else {
			$hasDimension = $this->makeResponsive( $svg );
			$responsive   = ! empty( $instance['responsive'] ) && $instance['responsive'] ? ' checked="true"' : '';
			$attachmentId = (int) $instance['attachment_id'];
			$title        = ! empty( $instance['title'] ) ? $instance['title'] : '';
		}

		$classes = [];

		if ( ! $svg ) {
			$classes[] = 'empty';
		}

		if ( $hasDimension ) {
			$classes[] = 'has-dimension';
		}

		// building up $allowed_html list from the output below it (we know that only these tags and attributes appear in the output)
		$allowed_html = array(
			'div'   => array(
				'class' => array(),
			),
			'input' => array(
				'name'  => array(),
				'value' => array(),
				'type'  => array(),
				'id'    => array(),
				'class' => array(),
			),
			'p'     => array(),
			'label' => array(
				'for' => array(),
			),
		);

		print wp_kses(
			'<div class="media-widget-control svgator-widget-control ' . implode( ' ', $classes ) . '">',
			$allowed_html
		);

		print wp_kses(
			'<input'
			. ' type="hidden"'
			. ' id="' . $id( 'attachment_id' ) . '"'
			. ' name="' . $name( 'attachment_id' ) . '"'
			. ' value="' . $attachmentId . '"'
			. ' class="attachment_id"'
			. '>',
			$allowed_html
		);

		print wp_kses( '<p>', $allowed_html );
		print wp_kses( '<label for="' . $id( 'title' ) . '">Title:</label>', $allowed_html );
		print wp_kses(
			'<input'
			. ' type="text"'
			. ' id="' . $id( 'title' ) . '"'
			. ' name="' . $name( 'title' ) . '"'
			. ' class="widefat"'
			. ' value="' . htmlspecialchars( $title ) . '"'
			. '>',
			$allowed_html
		);
		print wp_kses( '</p>', $allowed_html );

		?>
        <div class="media-widget-preview media_image media_svgator">
            <div class="attachment-media-view">
                <button type="button" class="select-svgator-media button-add-media">Select animated SVG</button>
            </div>
            <div class="block-edit-media">
                <div class="media-widget-preview media_image media_svgator">
                    <object
                            type="image/svg+xml" data="<?php
					echo esc_url( $svgUrl ); ?>" class="media_svgator_svg"
                    ></object>
                </div>
                <p class="toggle-responsive">
                    <input
                            type="checkbox"
                            id="<?php
							echo sanitize_html_class( $id( 'responsive' ) ); ?>"
                            class="responsive"
                            name=<?php
							echo sanitize_html_class( '"' . $name( 'responsive' ) . '"' . $responsive ) ?>
                    >
                    <label
                            for="<?php
							echo sanitize_html_class( $id( 'responsive' ) ); ?>"
                    >
                        Make responsive
                    </label>
                </p>
                <p class="no-dimension">This SVG is already responsive</p>
                <p class="media-widget-buttons">
                    <button type="button" class="button select-svgator-media">Change animated SVG</button>
                </p>
            </div>
        </div>
		<?php

		if ( $svg ) {
			?>

			<?php
		}
		print wp_kses( '</div>', $allowed_html );
	}

	public function update( $new_instance, $old_instance ) {
		$instance                  = $old_instance;
		$instance['attachment_id'] = (int) $new_instance['attachment_id'];
		$instance['title']         = sanitize_text_field( $new_instance['title'] );
		$instance['responsive']    = ! empty( $new_instance['responsive'] ) && $new_instance['responsive'] ? true : false;

		return $instance;
	}
}
