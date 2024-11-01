<?php
spl_autoload_register('WP_SVGator_autoloader');

function WP_SVGator_autoloader($className) {

	if (!preg_match('@^WP_SVGator\b@', $className)) {
		return;
	}

	$className = preg_replace('@[^a-z0-9\_\\\\]+@i', '', $className);

	$file = str_replace('\\', DIRECTORY_SEPARATOR, $className) . '.php';

	$file = WP_SVGATOR_PLUGIN_DIR . 'includes' . DIRECTORY_SEPARATOR . 'class' . DIRECTORY_SEPARATOR . $file;

	if(!file_exists($file)) {
		return;
	}

	require_once $file;
}
