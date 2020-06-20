<?php

class Notes extends BaseModel
{
	public $id;
	public $idOrganisation;
	public $resultKey;
	public $text;
	public $label;
	public $images;
	public $date;
	public $createDate;
	public $isPrivate;
	public $idPerson;

	public function initialize ()
	{
		$this -> setSchema("desk");
		$this -> setSource("notes");

		$this -> skipAttributesOnUpdate(['createDate']);
		$this -> belongsTo('idPerson', 'Persons', 'id');
	}
}
