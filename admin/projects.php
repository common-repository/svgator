<?php

$userTokens = get_user_option( 'svgator_api', get_current_user_id() );
$cssClass   = empty( $userTokens )
              || empty( $userTokens['app_id'] )
              || empty( $userTokens['secret_key'] )
              || empty( $userTokens['customer_id'] )
              || empty( $userTokens['access_token'] ) ? 'logged-out' : 'logged-in';
?>
    <div
            class="svgator-wrap wrap <?php
			echo sanitize_html_class( $cssClass ) ?>"
    >
        <div id="svgator-header">
            <h1>
				<?php
				echo esc_html( get_admin_page_title() ) ?>
            </h1>
            <form id="svgator-filter">
				<?php
				wp_nonce_field( 'svgator_logOut', 'svgator_logOut_nonce' ); ?>
                <input type="search" placeholder="Search project...">
            </form>
            <div class="svgator-limits svgator-hidden">
                <strong class="used"></strong>/<strong class="limit"></strong>
                exports used.
                <a href="https://app.svgator.com/pricing#/" target="_blank"><strong>Upgrade now</strong></a>
                for unlimited exports.
            </div>
            <button class="logout">Disconnect</button>
        </div>
		<?php
		require 'snippets/projects.php';
		?>
        <div id="wp-svgator-notice">
            <div class="wp-svgator-notice-message"></div>
            <span class="wp-svgator-notice-close">x</span>
        </div>
    </div>
<?php
