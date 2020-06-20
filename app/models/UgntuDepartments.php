<?php

class UgntuDepartments extends BaseModel
{
	public $id;
	public $idDepartment;
	public $name;
	public $short;

	public function initialize ()
	{
		$this -> setSchema("desk");
		$this -> setSource("ugntu_departments");

		$this -> hasMany('idDepartment', 'UgntuTeachers', 'idDepartment');
	}

	public function columnMap ()
	{
		return [
			'id_row' => 'id',
			'id_department' => 'idDepartment',
			'name_department' => 'name',
			'short' => 'short'
		];
	}

}
