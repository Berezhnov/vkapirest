<?php

class Persons extends BaseModel
{
	public $id;
	public $phone;
	public $createDate;
	public $idOrganisation;
	public $resultKey;

	public function initialize ()
	{
		$this -> setSchema("desk");
		$this -> setSource("persons");

		$this -> hasMany('id', 'Notes', 'idPerson');
		$this -> hasMany('id', 'Events', 'idPerson');
		$this -> belongsTo('idOrganisation', 'Organisations', 'id');
	}
}
