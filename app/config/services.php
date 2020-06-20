<?php

include(APP_PATH . '/libraries/vendor/autoload.php');
include(APP_PATH . '/libraries/ExcelLibrary.php');

use Phalcon\Cache\Adapter\Libmemcached;
use Phalcon\Cache\Adapter\Stream;
use Phalcon\Storage\Serializer\Json;
use Phalcon\Mvc\View\Simple as View;
use Phalcon\Url as UrlResolver;

use Phalcon\Cache;
use Phalcon\Storage\SerializerFactory;

class JsonSerializer extends Json
{
	public function unserialize ($data) : void
	{
		$this -> data = json_decode($data, true);
	}
}

$di -> set('cache', function () {

		$serializerFactory = new SerializerFactory();
		$jsonSerializer = new JsonSerializer();
		$options = ['serializer' => $jsonSerializer, 'lifetime' => 600, 'servers' => [['host' => 'localhost', 'port' => '11211']]];
		$adapter = new Libmemcached($serializerFactory, $options);
		return new Cache($adapter);
});

$di -> set('cacheFiles', function () {

		$serializerFactory = new SerializerFactory();
		$jsonSerializer = new JsonSerializer();
		$options = ['serializer' => $jsonSerializer, 'lifetime' => 48 * 3600, 'storageDir' => '../app/cache/'];
		$adapter = new Stream($serializerFactory, $options);
		return new Cache($adapter);
});

/**
 * Shared configuration service
 */
$di -> setShared('config', function () {
	return include APP_PATH . "/config/config.php";
});

/**
 * Sets the view component
 */
$di -> setShared('view', function () {
	$config = $this -> getConfig();

	$view = new View();
	$view -> setViewsDir($config -> application -> viewsDir);
	return $view;
});

/**
 * The URL component is used to generate all kind of urls in the application
 */
$di -> setShared('url', function () {
	$config = $this -> getConfig();

	$url = new UrlResolver();
	$url -> setBaseUri($config -> application -> baseUri);
	return $url;
});

/**
 * Database connection is created based in the parameters defined in the configuration file
 */
$di -> setShared('db', function () {
	$config = $this -> getConfig();

	$class = 'Phalcon\Db\Adapter\Pdo\\' . $config -> database -> adapter;
	$params = [
		'host' => $config -> database -> host,
		'username' => $config -> database -> username,
		'password' => $config -> database -> password,
		'dbname' => $config -> database -> dbname,
		'charset' => $config -> database -> charset,
		'options' => [
			PDO::ATTR_EMULATE_PREPARES => true,
			PDO::ATTR_STRINGIFY_FETCHES => false,
			PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4'
		]
	];

	if ($config -> database -> adapter == 'Postgresql')
	{
		unset($params['charset']);
	}

	$connection = new $class($params);

	return $connection;
});

