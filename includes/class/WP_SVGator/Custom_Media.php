<?php

namespace WP_SVGator;

class Custom_Media {
	private function __construct() {
		add_action( 'widgets_init', function(){
			register_widget(new Widget_Media_SVGator());
		});
	}

	public static function run() {
		static $inst = null;
		if ($inst === null) {
			$inst = new self();
		}
		return $inst;
	}
}