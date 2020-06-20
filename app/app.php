<?php
date_default_timezone_set('Europe/Moscow');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL ^ E_DEPRECATED);

use Phalcon\Mvc\Micro\Collection as MicroCollection;

$collection = new MicroCollection();
$collection -> setHandler(new HakatonController());
$collection -> setPrefix('/hakaton');
$collection -> get('/organisations', 'getOrganisations');
$collection -> get('/organisations/{idOrganisation:[0-9]+}/{text}', 'getOrganisationGroups');
$collection -> get('/{universityId:[0-9]+}/{text}', 'searchGroups');
$collection -> post('/organisations', 'createOrganisation');
$collection -> post('/organisations/{idOrganisation:[0-9]+}/groups', 'createResult');
$collection -> get('/organisations/{idOrganisation:[0-9]+}/{resultKey}/data', 'getData');
$collection -> get('/schedule/{idOrganisation:[0-9]+}/{resultKey}/{weekNumber:[0-9]+}', 'getSchedule');
$collection -> post('/notes/{idOrganisation:[0-9]+}/{resultKey}', 'addNote');
$collection -> post('/events/{idOrganisation:[0-9]+}/{resultKey}', 'addEvent');
$collection -> delete('/notes/{idNote:[0-9]+}', 'removeNote');
$collection -> delete('/events/{idNote:[0-9]+}', 'removeEvent');
$collection -> get('/test', 'test');
$collection -> get('/notes/{idOrganisation:[0-9]+}/{resultKey}', 'getNotes');
$app -> mount($collection);

$app -> response -> setHeader("Access-Control-Allow-Headers", "Content-Type, Id");
$app -> response -> setHeader("Access-Control-Allow-Origin", "*");
$app -> response -> setHeader("Access-Control-Allow-Methods", "GET, PUT, POST, DELETE, OPTIONS");
$app -> response -> sendHeaders();

$app -> after(function () use ($app) {
	$data = $app -> getReturnedValue();
	$formattedData = [];
	$formattedData['data'] = (is_array($data) and isset($data['success']) and $data['success'] === false) ? [] : $data;
	$formattedData['success'] = (is_array($data) and isset($data['success'])) ? $data['success'] : true;
	$formattedData['message'] = (is_array($data) and isset($data['message'])) ? $data['message'] : '';
	$formattedData['code'] = (is_array($data) and isset($data['code'])) ? $data['code'] : 0;
	$app -> response -> setJsonContent($formattedData);
	$app -> response -> send();
}
);

$app -> notFound(function () use ($app) {
	$isPreflightRequest = $app -> request -> getMethod() === "OPTIONS";
	if ($isPreflightRequest)
	{
		$app -> response -> setStatusCode(200, "CORS Preflight Request");
		$app -> response -> send();
	}
	else
	{
		$app -> response -> setStatusCode(404, "Not Found");
		echo $app['view'] -> render('404');
	}
});
