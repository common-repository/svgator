<?php

namespace WP_SVGator;

class View {
	private $filePath = '';

	public function __construct( $filePath ) {
		$this->filePath = $filePath;
	}

	public function render() {
		require_once WP_SVGATOR_PLUGIN_DIR . $this->filePath . '.php';
	}
}
