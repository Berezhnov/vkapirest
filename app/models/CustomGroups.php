<?php

class CustomGroups extends BaseModel
{
	public $id;
	public $idOrganisation;
	public $createDate;
	public $name;

	public function initialize ()
	{
		$this -> setSchema("desk");
		$this -> setSource("customGroups");
	}
}
