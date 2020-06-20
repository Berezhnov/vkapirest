<?php

class UgntuGroups extends BaseModel
{
	public $id;
	public $name;
	public $idFaculty;
	public $bellCategory;
	public $idCorpus;
	public $teachersFetched;

	public function initialize ()
	{
		$this -> setSchema("desk");
		$this -> setSource("ugntu_groups");

		$this -> belongsTo('idFaculty', 'UgntuFaculties', 'idFaculty');
		$this -> hasManyToMany('id', 'UgntuGroupsTeachers', 'idGroup', 'idTeacher', 'UgntuTeachers', 'id');
	}

	public function columnMap ()
	{
		return [
			'id_group' => 'id',
			'name_group' => 'name',
			'id_faculty' => 'idFaculty',
			'id_corpus' => 'idCorpus',
			'bellCategory' => 'bellCategory',
			'teachersFetched' => 'teachersFetched'
		];
	}

}
