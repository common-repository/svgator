<?php

namespace WP_SVGator;

class Block {
	const block_type_name = 'wp-svgator/insert-svg';

	private function __construct() {
		add_action( 'init', [ $this, 'registerBlock' ] );
	}

	public static function run() {
		static $inst = null;
		if ( $inst === null ) {
			$inst = new self();
		}

		return $inst;
	}

	function registerBlock() {
		$userId = get_current_user_id();

		if ( ! $userId ) {
			return;
		}

		$userTokens = get_user_option( 'svgator_api', $userId );

		$loggedOut = empty( $userTokens )
		             || empty( $userTokens['app_id'] )
		             || empty( $userTokens['secret_key'] )
		             || empty( $userTokens['customer_id'] )
		             || empty( $userTokens['access_token'] );

		if ( $loggedOut ) {
			return;
		}

		add_action( 'enqueue_block_editor_assets', Main::run()->enqueueScripts( 'WP_SVGatorBlock' ) );
		add_action( 'enqueue_block_editor_assets', function () {
			wp_localize_script( 'WP_SVGatorBlock', 'wp_svgator', [
				'plugin_logo' => plugins_url( '../../admin/imgs/logo.svg', __DIR__ ),
			] );
		} );

		register_block_type( $this::block_type_name );
	}
}
