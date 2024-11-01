<div>
    <div id="login-container">
        <p>To load your projects from SVGator.com, please connect your account by signing in to SVGator.com and authorizing this app.</p>
		<?php
		wp_nonce_field( 'svgator_saveToken', 'svgator_logIn_nonce' ); ?>
        <button id="login-to-svgator">Connect my account</button>
    </div>
    <div id="svgator-projects"></div>
    <div id="svgator-projects-pagination"></div>
</div>
