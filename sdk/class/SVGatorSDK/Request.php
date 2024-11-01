<?php

namespace SVGatorSDK;

use Exception;
use ReflectionClass;

class Request {
	private static ?Request $instance = null;

	private string $endpoint = 'https://app.svgator.com/api/app-auth/';
	const ENTITY_TOKEN = 'token';
	const ENTITY_PROJECT = 'project';
	const ENTITY_PROJECTS = 'projects';
	const ENTITY_EXPORT = 'export';

	private string $app_id;
	private string $secret_key;
	private string $customer_id;
	private string $access_token;

	private array $_defaults = [
		'method'      => 'GET',
		'timeout'     => 10,
		'redirection' => 5,
		'httpversion' => '1.0',
		'blocking'    => true,
		'headers'     => [],
		'body'        => null,
		'cookies'     => [],
	];

	private function __construct() {
		if ( in_array( @$_SERVER['HTTP_HOST'], [ 'wp.local', 'localhost:8081', 'wp.local:8081' ], true ) ) {
			$this->endpoint = 'https://app.svgator.net/api/app-auth/';
		} elseif ( str_contains( @$_SERVER['HTTP_HOST'], '.svgator.net' ) ) {
			/*
			 * this is because dev cannot access dev
			 * app-svgator2 is the container's internal domain name for dev
			 * should be done differently
			 */
			$this->endpoint = 'http://app-svgator2/api/app-auth/';
		}
	}

	/**
	 * @param string $name
	 *
	 * @return null
	 */
	public function getKey( string $name ) {
		if ( ! empty( $this->{$name} ) ) {
			return $this->{$name};
		}

		return null;
	}

	/**
	 * @return self
	 */
	public static function getInstance(): self {
		if ( static::$instance === null ) {
			static::$instance = new self();
		}

		return static::$instance;
	}

	/**
	 * @param array $params default []
	 *
	 * @return void
	 */
	public function setAppParams( array $params = [] ): void {
		$keys = [
			'endpoint',
			'app_id',
			'secret_key',
			'customer_id',
			'access_token',
		];

		foreach ( $keys as $key ) {
			if ( ! empty( $params[ $key ] ) ) {
				$this->{$key} = $params[ $key ];
			}
		}
	}

	/**
	 * @param string $entity
	 *
	 * @return bool
	 */
	private function isValidEntity( string $entity ): bool {
		$oClass    = new ReflectionClass( __CLASS__ );
		$constants = $oClass->getConstants();
		foreach ( $constants as $key => $value ) {
			if ( str_starts_with( $key, 'ENTITY_' ) && $entity === $value ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @param array $params
	 *
	 * @return string
	 */
	private function getHash( array $params ): string {
		ksort( $params );
		$hash = implode( '', $params );
		if ( ! empty( $this->secret_key ) ) {
			$hash .= $this->secret_key;
		}

		return hash( 'sha256', $hash );
	}

	/**
	 * @param array $args
	 *
	 * @return array
	 */
	private function mergeArgs( array $args ): array {
		if ( is_array( $args ) ) {
			return array_merge( $this->_defaults, $args );
		}

		return array_merge( $this->_defaults, [] );
	}

	/**
	 * @param array $args
	 *
	 * @return void
	 */
	private function setUserAgent( array &$args ): void {
		if ( ! empty( $args['headers']['User-Agent'] ) ) {
			$args['user-agent'] = $args['headers']['User-Agent'];
		} elseif ( ! empty( $args['headers']['user-agent'] ) ) {
			$args['user-agent'] = $args['headers']['user-agent'];
		}
	}

	/**
	 * @param string $entity
	 * @param array $params default []
	 * @param string $type default 'json'
	 *
	 * @return bool|array|string
	 * @throws ExportException
	 */
	public function makeRequest( string $entity, array $params = [], string $type = 'json' ) {
		if ( ! $this->isValidEntity( $entity ) ) {
			throw new Exception( 'Invalid entity' );
		}

		$time = time();
		if ( ! empty( $this->app_id ) ) {
			$params['app_id'] = $this->app_id;
		}
		if ( ! empty( $this->customer_id ) ) {
			$params['customer_id'] = $this->customer_id;
		}
		if ( ! empty( $this->access_token ) ) {
			$params['access_token'] = $this->access_token;
		}
		$params['time'] = $time;

		$req = Request::getInstance();

		$endpoint = $this->endpoint;
		if ( ! empty( $params['endpoint'] ) ) {
			$endpoint = $params['endpoint'];
		}

		$url = $endpoint . $entity
		       . '?' . http_build_query( $params )
		       . '&hash=' . $this->getHash( $params );

		$responseHeaders = [];
		$res             = $req->make( $url, [], $responseHeaders );
		$contentType     = ! empty( $responseHeaders['content-type'] )
			? $responseHeaders['content-type']
			: null;

		if ( $type === 'text' && stripos( $contentType, 'application/json' ) === false ) {
			return $res;
		}

		$json = json_decode( $res, true );

		if ( ! $json ) {
			throw new Exception( esc_html( $res ) );
		}

		if ( ! empty( $json['error'] ) ) {
			$data = ! empty( $json['data'] ) ? $json['data'] : $json;
			throw new ExportException( esc_html( $json['error'] ), 422, null, esc_html( $data ) );
		}

		return $json;
	}

	/**
	 * @param string $url
	 * @param array $args default []
	 * @param array $headers default []
	 *
	 * @return string
	 */
	public function make( string $url, array $args = [], array &$headers = [] ): string {
		$mergedArgs = $this->mergeArgs( $args );

		$raw_response = wp_remote_request( $url, $mergedArgs );

		$headers = wp_remote_retrieve_headers( $raw_response );

		return wp_remote_retrieve_body( $raw_response );
	}
}
