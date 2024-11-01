<?php

namespace WP_SVGator;

require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php';
require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-direct.php';

use WP_Filesystem_Direct;

class Media {
	/**
	 * @param $params
	 *
	 * @return array
	 * @throws \Exception
	 */
	public static function create( $params ) {
		$uploadDir = wp_upload_dir();

		$basePath = $uploadDir['basedir'] . '/';
		$baseUrl  = $uploadDir['baseurl'] . '/';
		if ( wp_mkdir_p( $uploadDir['path'] ) ) {
			$basePath = $uploadDir['path'] . '/';
			$baseUrl  = $uploadDir['url'] . '/';
		}

		$sanitizedTitle = sanitize_file_name( $params['project']->title );
		$file           = $basePath . $sanitizedTitle . '.svg';
		$url            = $baseUrl . $sanitizedTitle . '.svg';

		$i = 1;
		while ( file_exists( $file ) ) {
			$file = $basePath . $sanitizedTitle . '-' . $i . '.svg';
			$url  = $baseUrl . $sanitizedTitle . '-' . $i . '.svg';
			$i ++;
		}

		// WP_Filesystem_Direct has an $args parameter that is mandatory, however it is not used
		$fs  = new WP_Filesystem_Direct( [] );
		$res = $fs->put_contents( $file, $params['content'], 0644 );

		if ( ! $res ) {
			throw new \Exception( 'Could not write file to media library.' );
		}

		$attachment = [
			'post_mime_type' => 'svgator/svg+xml',
			'post_title'     => $params['project']->title,
			'post_content'   => '',
			'post_status'    => 'inherit',
		];

		$attachment = wp_insert_attachment( $attachment, $file );

		$attachment_data = wp_generate_attachment_metadata( $attachment, $file );
		if ( ! $attachment_data ) {
			$attachment_data = [];
		}
		$attachment_data['svgatorid'] = $params['project']->id;
		wp_update_attachment_metadata( $attachment, $attachment_data );

		return [
			'attachment' => $attachment,
			'url'        => $url,
		];
	}
}
