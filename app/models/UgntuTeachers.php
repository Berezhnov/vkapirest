<?php

class UgntuTeachers extends BaseModel
{
	public $id;
	public $name;
	public $idDepartment;
	public $handleId;

	public function initialize ()
	{
		$this -> setSchema("desk");
		$this -> setSource("ugntu_teachers");

		$this -> belongsTo('idDepartment', 'UgntuDepartments', 'idDepartment');
		$this -> hasManyToMany('id', 'UgntuGroupsTeachers', 'idTeacher', 'idGroup', 'UgntuGroups', 'id');
	}

	public function columnMap ()
	{
		return [
			'id_teacher' => 'id',
			'name_teacher' => 'name',
			'id_department' => 'idDepartment',
			'handle_id' => 'handleId'
		];
	}

}
