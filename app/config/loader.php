<?php

/**
 * Registering an autoloader
 */
$loader = new \Phalcon\Loader();

$loader -> registerDirs(
	[
		$config -> application -> scheduleDir,
		$config -> application -> modelsDir,
		$config -> application -> controllersDir,
		$config -> application -> librariesDir,
	]
) -> register();
