<?php

class Events extends BaseModel
{
	public $id;
	public $idOrganisation;
	public $idPerson;
	public $resultKey;
	public $text;
	public $addition1;
	public $addition2;
	public $time1;
	public $time2;
	public $createDate;
	public $eventNumber;
	public $eventTypeName;
	public $color;
	public $date;
	public $dayNumber;
	public $isPrivate;
	public $startDate;
	public $endDate;
	public $repeatsCount;


	public function initialize ()
	{
		$this -> setSchema("desk");
		$this -> setSource("events");

		$this -> skipAttributesOnUpdate(['createDate']);
		$this -> belongsTo('idPerson', 'Persons', 'id');
	}
}
