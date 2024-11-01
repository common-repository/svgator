<?php

namespace SVGatorSDK;

class Response {
	/**
	 * @param $data
	 *
	 * @return void
	 */
	public static function send( $data ): void {
		header( 'Content-Type: application/json' );
		echo wp_json_encode( $data );
	}
}
