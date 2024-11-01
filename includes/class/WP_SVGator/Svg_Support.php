<?php

namespace WP_SVGator;

require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php';
require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-direct.php';

use WP_Block;
use WP_Filesystem_Direct;

class Svg_Support {
	const SVG_MAX_INLINE_SIZE = 1001318;

	private function __construct() {
		if ( ! is_admin() ) {
			// for older versions
			add_filter( 'the_content', [ $this, 'wp_svgInlineReplace' ] );
			// for newer versions
			add_filter( 'render_block', [ $this, 'wp_svgInlineReplaceBlock' ], 10, 3 );
		}
		add_filter( 'post_thumbnail_html', [ $this, 'wp_svgInlineReplace' ] );
	}

	/**
	 * @return self
	 */
	public static function run(): self {
		static $inst = null;
		if ( $inst === null ) {
			$inst = new self();
		}

		return $inst;
	}

	function wp_svgInlineReplaceBlock( $content, $block_array, WP_Block $instance ) {
		if ( empty($instance->block_type->name) || $instance->block_type->name !== Block::block_type_name ) {
			return $content;
		}

		return $this->wp_svgInlineReplace( $content );
	}

	function wp_svgInlineReplace( $content ) {
		if ( empty( $content ) ) {
			return $content;
		}

		return preg_replace_callback(
			'/(<img[^>]*?>)/',
			[ $this, 'getImageReplacement' ],
			$content
		);
	}

	function getImageReplacement( $imgMatch ) {
		if ( empty( $imgMatch ) || empty( $imgMatch[0] ) ) {
			return '';
		}

		$imgTag = $imgMatch[0];

		$svgContent = $this->getSvg( $imgTag );
		$svgContent = static::fixScript( $svgContent );
		$svgContent = trim( $svgContent );
		$svgContent = $this->keepAttributes( $imgTag, $svgContent );

		return $svgContent;
	}

	function keepAttribute( $svgContent, $attrName, $newValue = false ) {
		return preg_replace_callback(
			'@<svg[^>]*>@',
			function ( $match ) use ( $attrName, $newValue ) {
				$svgTag     = $match[0];
				$quotedAttr = preg_quote( $attrName, '/' );

				if ( $attrName === 'style' ) {
					if ( ! $newValue ) {
						return $svgTag;
					}
					if ( preg_match( '@\b' . $quotedAttr . '(?:=["\'](.*?)["\'])?@', $svgTag, $match ) ) {
						$newValue = $match[1] . $newValue;
					}
				}

				// removing existing attribute
				$svgTag = preg_replace( '@\b' . $quotedAttr . '(?:=["\'].*?["\'])?@', '', $svgTag );

				// adding back needed value, if needed
				if ( $newValue ) {
					$svgTag = str_replace(
						'<svg',
						'<svg ' . htmlspecialchars( $attrName ) . '="' . htmlspecialchars( $newValue ) . '"',
						$svgTag
					);
				}

				return $svgTag;
			},
			$svgContent
		);
	}

	function keepAttributes( $imgTag, $svgContent ) {
		$imgAttributes = static::parseAttributes( $imgTag );

		$attrToKeeps = [ 'width', 'height', 'class', 'style' ];

		foreach ( $attrToKeeps as $attrName ) {
			$newValue   = ! empty( $imgAttributes[ $attrName ] ) ? $imgAttributes[ $attrName ] : false;
			$svgContent = $this->keepAttribute( $svgContent, $attrName, $newValue );
		}

		return $svgContent;
	}

	/**
	 * @param string $content
	 *
	 * @return array|false
	 */
	public static function parseAttributes( string $content ) {
		if ( ! $content || ! preg_match( '@<(img|svg)\b[^>]*>@', $content, $match ) ) {
			return false;
		};
		$svg   = ! str_contains( $match[0], '/>' ) ? str_replace( '>', '/>', $match[0] ) : $match[0];
		$svg   = @simplexml_load_string( $svg );
		$attrs = $svg ? $svg->attributes() : false;

		$attrs = $attrs ? (array) $attrs : false;

		return $attrs && ! empty( $attrs['@attributes'] ) ? $attrs['@attributes'] : [];
	}

	/**
	 * @param string $svgContent
	 *
	 * @return array|string
	 */
	public static function fixScript( string $svgContent ) {
		//Remove CDATA, since WordPress does not allow it inside the content
		$svgContent = str_replace( '<![CDATA[', '', $svgContent );
		$svgContent = str_replace( ']]>', '', $svgContent );

		$startOfScript = strpos( $svgContent, '<script>' );
		if ( $startOfScript === false ) {
			return $svgContent;
		}

		//add a space after the < char if it is followed by a letter
		$startOfScript += strlen( '<script>' );
		$endOfScript   = strpos( $svgContent, '</script>' );
		$scriptContent = substr( $svgContent, $startOfScript, $endOfScript - $startOfScript );
		$scriptContent = preg_replace( '/<([a-z])/', '< $1', $scriptContent );

		return substr_replace( $svgContent, $scriptContent, $startOfScript, $endOfScript - $startOfScript );
	}

	function getSvg( $imgTag ) {
		$src = preg_match( '/src="([^"]+)"/', $imgTag, $srcMatch );
		if ( ! $src || empty( $srcMatch ) || empty( $srcMatch[1] ) ) {
			return $imgTag;
		}

		$srcUrl = wp_parse_url( $srcMatch[1], PHP_URL_PATH );
		$srcExt = pathinfo( $srcUrl, PATHINFO_EXTENSION );
		if ( 'svg' !== $srcExt ) {
			return $imgTag;
		}

		$svgHost  = wp_parse_url( $srcMatch[1], PHP_URL_HOST );
		$thisHost = wp_parse_url( get_site_url(), PHP_URL_HOST );
		if ( $thisHost !== $svgHost ) {
			return $imgTag;
		}

		$mainPath     = wp_parse_url( trailingslashit( get_site_url() ), PHP_URL_PATH );
		$relativePath = preg_replace( '@^' . preg_quote( $mainPath, '@' ) . '@', '', $srcUrl, 1 );
		$svgLocalPath = ABSPATH . $relativePath;

		if ( ! file_exists( $svgLocalPath ) ) {
			return $imgTag;
		}

		$fileSize = filesize( $svgLocalPath );
		if ( $fileSize > Svg_Support::SVG_MAX_INLINE_SIZE ) {
			return '<object type="image/svg+xml" data="' . $srcMatch[1] . '"></object>';
		}

		// WP_Filesystem_Direct has an $args parameter that is mandatory, however it is not used
		$fs         = new WP_Filesystem_Direct( [] );
		$svgContent = $fs->get_contents( $svgLocalPath );

		if ( ! $svgContent || ! $this->belongsToSVGator( $svgContent ) ) {
			return $imgTag;
		}

		return $svgContent;
	}

	/**
	 * @param string $svgContent
	 *
	 * @return bool
	 */
	public function belongsToSVGator( string &$svgContent ): bool {
		if ( str_contains( $svgContent, '__SVGATOR_PLAYER__' ) ) {
			return true;
		}

		preg_match( '@<svg.*?>@', $svgContent, $match );

		return $match && ! empty( $match[0] ) && str_contains( $match[0], 'data-svgatorid' );
	}
}
