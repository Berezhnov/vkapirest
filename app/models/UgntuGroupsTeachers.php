<?php

class UgntuGroupsTeachers extends BaseModel
{
	public $id;
	public $idGroup;
	public $idTeacher;

	public function initialize ()
	{
		$this -> setSchema("desk");
		$this -> setSource("ugntu_groups_teachers");

		$this -> belongsTo('idGroup', 'UgntuGroups', 'id');
		$this -> belongsTo('idTeacher', 'UgntuTeachers', 'id');
	}

	public function columnMap ()
	{
		return [
			'id' => 'id',
			'idGroup' => 'idGroup',
			'idTeacher' => 'idTeacher'
		];
	}
}
