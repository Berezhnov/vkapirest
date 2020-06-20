<?php
/*
 * Modified: prepend directory path of current file, because of this file own different ENV under between Apache and command line.
 * NOTE: please remove this comment.
 */
defined('BASE_PATH') || define('BASE_PATH', getenv('BASE_PATH') ?: realpath(dirname(__FILE__) . '/../..'));
defined('APP_PATH') || define('APP_PATH', BASE_PATH . '/app');

return new \Phalcon\Config([
	'database' => [
		'adapter' => 'Mysql',
		'host' => 'localhost',
		'username' => 'root',
		'password' => 'e181J*h2',
		'dbname' => 'desk',
		'charset' => 'utf8',
	],

	'application' => [
		'modelsDir' => APP_PATH . '/models/',
		'controllersDir' => APP_PATH . '/controllers/',
		'migrationsDir' => APP_PATH . '/migrations/',
		'librariesDir' => APP_PATH . '/libraries/',
		'jobsControllersDir' => APP_PATH . '/controllers/jobs/',
		'newsControllersDir' => APP_PATH . '/controllers/news/',
		'refreshDataControllersDir' => APP_PATH . '/controllers/refreshData/',
		'scheduleDir' => APP_PATH . '/controllers/schedule',
		'viewsDir' => APP_PATH . '/views/',
		'baseUri' => '/api/',
	]

]);
