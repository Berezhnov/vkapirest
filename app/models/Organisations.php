<?php

class Organisations extends BaseModel
{
	public $id;
	public $name;
	public $short;
	public $createDate;
	public $custom;

	public function initialize ()
	{
		$this -> setSchema("desk");
		$this -> setSource("organisations");

		$this -> hasMany('id', 'Users', 'idOrganisation');
		$this -> hasMany('id', 'News', 'idOrganisation');
	}

}
