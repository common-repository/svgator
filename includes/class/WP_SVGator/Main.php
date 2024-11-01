<?php

namespace WP_SVGator;

require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php';
require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-direct.php';

use Closure;
use Exception;
use SVGatorSDK\ExportException as SDKExportException;
use SVGatorSDK\Response as SDKResponse;
use SVGatorSDK\Main as SDKMain;
use WP_Post;
use WP_Query;
use WP_Filesystem_Direct;

class Main {
	const FORCE_DEV = false;
	//const FORCE_DEV = 'https://app.svgator.net/app-auth';
	const SVGATOR_API_OPTION = 'svgator_api';

	private function __construct() {
		$sdkAutoload = WP_SVGATOR_PLUGIN_DIR . 'sdk/autoload.php';
		if ( ! file_exists( $sdkAutoload ) ) {
			add_action( 'admin_notices', function () {
				?>
                <div class="error notice">
                    <p>SVGator SDK could not be loaded.</p>
                </div>
				<?php
			} );
		}

		require $sdkAutoload;

		Block::run();
		Svg_Support::run();
		Custom_Media::run();

		add_filter( 'wp_prepare_attachment_for_js', [ $this, 'prepareAttachmentJs' ], 1, 3 );

		add_action( 'admin_menu', [ $this, 'adminMenu' ] );

		add_action( 'wp_ajax_svgator_saveToken', [ $this, 'saveToken' ] );
		add_action( 'wp_ajax_svgator_getProjects', [ $this, 'getProjects' ] );
		add_action( 'wp_ajax_svgator_importProject', [ $this, 'importProject' ] );
		add_action( 'wp_ajax_svgator_logOut', [ $this, 'svgatorLogOut' ] );
		add_action( 'plugins_loaded', [ $this, 'pluginUpdateCheck' ] );
	}

	/**
	 * @param array $response
	 * @param WP_Post $attachment
	 * @param $meta
	 *
	 * @return array
	 */
	public function prepareAttachmentJs( array $response, WP_Post $attachment, $meta ): array {
		if ( $response['mime'] !== 'svgator/svg+xml' ) {
			return $response;
		}
		$response['icon']  = $response['url'];
		$response['image'] = $response['url'];

		$file = get_attached_file( $attachment->ID );
		// WP_Filesystem_Direct has an $args parameter that is mandatory, however it is not used
		$fs  = new WP_Filesystem_Direct( [] );
		$svg = file_exists( $file ) ? $fs->get_contents( $file ) : false;

		if ( $svg ) {
			$attributes = Svg_Support::parseAttributes( $svg );
			if (
				$attributes
				&& array_key_exists( 'width', $attributes )
				&& $attributes['width']
				&& array_key_exists( 'height', $attributes )
				&& $attributes['height']
			) {
				$response['width']      = $attributes['width'];
				$response['height']     = $attributes['height'];
				$response['responsive'] = false;
			} else {
				$response['responsive'] = true;
			}
		}

		return $response;
	}

	/**
	 * @return void
	 */
	public function pluginUpdateCheck(): void {
		if ( get_option( "WP_SVGATOR_VERSION" ) != WP_SVGATOR_VERSION ) {
			$this->pluginUpdateRun();
			update_option( "WP_SVGATOR_VERSION", WP_SVGATOR_VERSION );
		}
	}

	/**
	 * @return void
	 */
	private function pluginUpdateRun(): void {
		global $wpdb;
		$args  = [
			'nopaging'       => true,
			'post_type'      => 'attachment',
			'post_status'    => 'inherit',
			'post_mime_type' => 'image/svg+xml',
		];
		$query = new WP_Query( $args );

		if ( $query->have_posts() ) {
			$posts = $query->get_posts();
			foreach ( $posts as $aPost ) {
				$file = get_attached_file( $aPost->ID );
				// WP_Filesystem_Direct has an $args parameter that is mandatory, however it is not used
				$fs      = new WP_Filesystem_Direct( [] );
				$content = file_exists( $file ) ? $fs->get_contents( $file ) : false;
				if ( $content && Svg_Support::run()->belongsToSVGator( $content ) ) {
					wp_update_post( (object) [
						'ID'             => $aPost->ID,
						'post_mime_type' => 'svgator/svg+xml',
					] );
				}
			}
		}
		wp_reset_postdata();
	}

	/**
	 * @return void
	 */
	public function registerScripts(): void {
		wp_register_style(
			'WP_SVGatorAdmin',
			WP_SVGATOR_PLUGIN_URL . 'admin/css/svgator.css',
			[],
			WP_SVGATOR_VERSION
		);

		wp_register_script(
			'WP_SVGatorWidget',
			WP_SVGATOR_PLUGIN_URL . "/admin/js/WP_SVGatorWidget.js",
			[ 'WP_SVGatorMedia' ],
			WP_SVGATOR_VERSION,
			true
		);

		wp_register_script(
			'WP_SVGatorBlock',
			WP_SVGATOR_PLUGIN_URL . 'admin/js/WP_SVGatorBlock.js',
			[ 'WP_SVGatorMedia', 'wp-blocks', 'wp-element' ],
			WP_SVGATOR_VERSION,
			true
		);

		wp_register_script(
			'WP_SVGatorMedia',
			WP_SVGATOR_PLUGIN_URL . "/admin/js/WP_SVGatorMedia.js",
			[ 'wp-mediaelement', 'jquery' ],
			WP_SVGATOR_VERSION,
			true
		);

		wp_register_script(
			'WP_SVGatorFrontend',
			'https://cdn.svgator.com/sdk/svgator-frontend.latest.js',
			[],
			WP_SVGATOR_VERSION,
			false
		);

		wp_register_script(
			'WP_SVGatorMenu',
			WP_SVGATOR_PLUGIN_URL . 'admin/js/WP_SVGatorMenu.js',
			[ 'WP_SVGatorFrontend', 'jquery' ],
			WP_SVGATOR_VERSION,
			false
		);
	}

	/**
	 * @param array|string|null $list default null
	 *
	 * @return Closure
	 */
	public function enqueueScripts( $list = null ): Closure {
		if ( empty( $list ) ) {
			$list = [
				'WP_SVGatorMedia',
				'WP_SVGatorBlock',
				'WP_SVGatorFrontend',
				'WP_SVGatorMenu',
				'WP_SVGatorWidget',
			];
		} elseif ( is_string( $list ) ) {
			$list = [ $list ];
		}

		return function () use ( $list ) {
			$this->registerScripts();
			foreach ( $list as $aScript ) {
				wp_enqueue_script( $aScript );
			}
			wp_enqueue_style( 'WP_SVGatorAdmin' );
		};
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

	/**
	 * @return void
	 */
	function adminMenu(): void {
		$menu = new Menu();
		$menu->run();
	}

	/**
	 * @return void
	 */
	static function deactivator(): void {
		Deactivator::deactivate();
	}

	/**
	 * @return void
	 */
	function saveToken(): void {
		try {
			$loginNonce = sanitize_text_field( wp_unslash( $_POST['svgator_logIn_nonce'] ) );
			if ( ! wp_verify_nonce( $loginNonce, 'svgator_saveToken' ) ) {
				throw new Exception( 'SVGator login nonce verification failed.' );
			}

			if ( empty( $_POST['auth_code'] ) ) {
				throw new Exception( 'An auth_code was not provided.' );
			}

			$userOptions = get_user_option( self::SVGATOR_API_OPTION );

			if ( self::FORCE_DEV ) {
				$userOptions['endpoint'] = Main::FORCE_DEV . '/';
			}

			$authCode              = sanitize_key( $_POST['auth_code'] );
			$userOptions['app_id'] = ! empty( $_POST['app_id'] ) ? sanitize_key( $_POST['app_id'] ) : 'dynamic';

			$svg    = new SDKMain( $userOptions );
			$params = $svg->getAccessToken( $authCode );

			$requiredKeys = [ 'app_id', 'access_token', 'customer_id' ];
			if ( empty( $userOptions['secret_key'] ) ) {
				$requiredKeys[] = 'secret_key';
			}
			$userOptionsToSave = [];
			foreach ( $requiredKeys as $requiredKey ) {
				if ( empty( $params[ $requiredKey ] ) ) {
					throw new Exception( 'Could not retrieve "' . $requiredKey . '"' );
				}

				$userOptionsToSave[ $requiredKey ] = $params[ $requiredKey ];
			}
			update_user_option( get_current_user_id(), self::SVGATOR_API_OPTION, $userOptionsToSave );

			SDKResponse::send( [
				'success' => true,
			] );
		} catch ( Exception $e ) {
			SDKResponse::send( [
				'success' => false,
				'error'   => $e->getMessage(),
			] );
		}

		wp_die();
	}

	/**
	 * @return void
	 */
	function getProjects(): void {
		$userOptions = get_user_option( self::SVGATOR_API_OPTION, get_current_user_id() );

		try {
			if ( empty( $userOptions ) ) {
				throw new Exception( 'User tokens are not set. Please log in again.' );
			}

			if ( self::FORCE_DEV ) {
				$userOptions['endpoint'] = Main::FORCE_DEV . '/';
			}

			$svgator     = new SDKMain( $userOptions );
			$projectList = array_merge(
				array(
					'projects' => array(),
					'limits'   => array(),
				),
				$svgator->projects()->getAll()
			);

			SDKResponse::send( [
				'success'  => true,
				'response' => $projectList['projects'],
				'limits'   => $projectList['limits'],
				'nonce'    => wp_create_nonce( 'svgator_importProject' ),
			] );
		} catch ( Exception $e ) {
			SDKResponse::send( [
				'success' => false,
				'error'   => 'Failed to load projects. Please try to log in again.',
			] );
		}

		wp_die();
	}

	/**
	 * @return void
	 */
	function svgatorLogOut(): void {
		$nonce  = sanitize_text_field( wp_unslash( $_POST['svgator_logOut_nonce'] ) );
		$option = get_user_option( self::SVGATOR_API_OPTION, get_current_user_id() );
		if ( ! wp_verify_nonce( $nonce, 'svgator_logOut' ) || ! $option ) {
			SDKResponse::send( [
				'success' => false,
				'error'   => 'Something went wrong, please try again later!',
			] );
			wp_die();
		}

		delete_user_option( get_current_user_id(), self::SVGATOR_API_OPTION );

		SDKResponse::send( [
			'success' => true,
		] );

		wp_die();
	}

	/**
	 * @param string $svg
	 * @param string $project_id
	 *
	 * @return void
	 */
	private function addProjectId( string &$svg, string $project_id ): void {
		if ( ! $svg || ! $project_id ) {
			return;
		}

		$project_id = sanitize_key( $project_id );

		if ( ! $project_id ) {
			return;
		}

		$svg = preg_replace_callback(
			'@<svg.*?>@',
			function ( $match1 ) use ( $project_id ) {
				if ( ! preg_match( '@data-svgatorid=["\'](.+?)["\']@', $match1[0], $match2 ) ) {
					return str_replace( '>', ' data-svgatorid="' . $project_id . '">', $match1[0] );
				}
				if ( $match2[1] === $project_id ) {
					return $match1[0];
				}

				return str_replace( $match2[0], 'data-svgatorid="' . $project_id . '"', $match1[0] );
			},
			$svg
		);
	}

	/**
	 * @return void
	 */
	function importProject(): void {
		$userOptions = get_user_option( 'svgator_api', get_current_user_id() );

		try {
			$importProjectNonce = sanitize_text_field( wp_unslash( $_POST['svgator_importProject_nonce'] ) );
			if ( ! wp_verify_nonce( $importProjectNonce, 'svgator_importProject' ) ) {
				throw new Exception( 'SVGator import project nonce verification failed.' );
			}

			$project_id = sanitize_key( $_POST['project_id'] );
			$svgator    = new SDKMain( $userOptions );
			$project    = $svgator->projects()->get( $project_id );
			$svg        = $svgator->projects()->export( $project_id, 'web' );

			$limits = ! empty( $svg['limits'] ) ? $svg['limits'] : null;
			if ( empty( $svg['content'] ) ) {
				$svg['content'] = '';
			}

			$this->addProjectId( $svg['content'], $project_id );

			$resp = Media::create( [
				'content' => $svg['content'],
				'project' => $project,
			] );

			$attachment = wp_prepare_attachment_for_js( $resp['attachment'] );

			SDKResponse::send( [
				'success'  => true,
				'response' => [
					'attachment' => $attachment,
					'id'         => intval( $resp['attachment'] ),
					'html'       => wp_get_attachment_image( $resp['attachment'] ),
					'url'        => $resp['url'],
					'content'    => $svg['content'],
					'project'    => $project,
					'limits'     => $limits,
				],
			] );
		} catch ( SDKExportException $e ) {
			SDKResponse::send( [
				'success' => false,
				'error'   => $e->getMessage(),
				'data'    => $e->getData()
			] );
		} catch ( Exception $e ) {
			SDKResponse::send( [
				'success' => false,
				'error'   => $e->getMessage(),
			] );
		}

		wp_die();
	}
}
