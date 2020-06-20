<?php

class UgntuFaculties extends BaseModel
{
	public $id;
	public $idFilial;
	public $idFaculty;
	public $name;
	public $short;

	public function initialize ()
	{
		$this -> setSchema("desk");
		$this -> setSource("ugntu_faculties");

		$this -> hasMany('idFaculty', 'UgntuGroups', 'idFaculty');
	}

	public function columnMap ()
	{
		return [
			'id_row' => 'id',
			'id_filial' => 'idFilial',
			'id_faculty' => 'idFaculty',
			'name_faculty' => 'name',
			'short' => 'short'
		];
	}

}
