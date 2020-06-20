<?php

class HakatonController extends BaseController
{
	public $startDate = '2019-09-01 00:00:00'; //начало учебного года (1 сентября). это чтобы отсчитывать недели, потому что первое сентября это не фактчто пн

	public $currentWeek; public $currentDayNumber;
	public $currentDate;
	public static $weekDays  = ['Понедельник', 'Вторник', 'Среда', 'Четверг', 'Пятница', 'Суббота', 'Воскресенье'];
	public $firstRequest = false;
	public $forceDate = '';

	public function getOrganisations ()
	{
		$res = [];
		$organisations = Organisations::find(["createDate > '2020-06'"]);
		for ($i = 0, $max = sizeof($organisations); $i < $max; $i++)
		{
			$res[] = $organisations[$i] -> toArray();
		}

		return $res;
	}

	public function getOrganisationGroups ($idOrganisation, $search)
	{
		$organisation = Organisations::findFirstById($idOrganisation);
		$organisation = $organisation -> toArray();

		$search = urldecode($search);
		switch ($idOrganisation)
		{
			case "1":   // УГНТУ по выгруженным данным
				$result = [];
				$groups = UgntuGroups::find(["LOWER(name) LIKE LOWER('$search%')", 'limit' => '10']);
				foreach ($groups as $group)
				{
					$result[] = ['idResult' => $group -> name, 'nameResult' => $group -> name, 'department' => $group -> ugntuFaculties -> name];
				}

				$teachers = UgntuTeachers::find(["LOWER(name) LIKE LOWER('$search%')", 'limit' => '10']);
				foreach ($teachers as $teacher)
				{
					$result[] = ['idResult' => $teacher -> handleId, 'nameResult' => $teacher -> name, 'department' => $teacher -> ugntuDepartments -> short];
				}

				return $result;
				break;
			default :
				$result = [];
				$groups = CustomGroups::find(["idOrganisation='$idOrganisation' AND LOWER(name) LIKE LOWER('$search%')", 'limit' => '6', 'columns' => 'id, name']);
				for ($i = 0, $max = sizeof($groups); $i < $max; $i++)
				{
					$group = $groups[$i];
					$result[] = ['idResult' => $group -> id, 'nameResult' => $group -> name, 'department' => ''];
				}
				return $result;
				break;
		}
	}

	public function createOrganisation ()
	{
		list($short) = $this -> parseBody(['short']);

		$organisation = new Organisations();
		$organisation -> short = $short;
		$organisation -> name = $short;
		$organisation -> createDate = date("Y-m-d H:i:s");
		$organisation -> custom = '1';

		if (!$organisation -> save())
		{
			$this -> returnErrorResponse('ERROR');
		}

		return $organisation -> toArray();
	}
	public function createResult ($idOrganisation)
	{
		list($name) = $this -> parseBody(['name']);

		$result = new CustomGroups();
		$result -> name = $name;
		$result -> createDate = date("Y-m-d H:i:s");
		$result -> idOrganisation = $idOrganisation;

		if (!$result -> save())
		{
			$this -> returnErrorResponse('ERROR');
		}

		return ['idResult' => $result -> id, 'nameResult' => $name];
	}

	public function getSchedule ($idOrganisation, $resultKey, $weekNumber)
	{
		$organisation = Organisations::findFirstById($idOrganisation);
		if ($organisation === false)
		{
			$this -> returnErrorResponse('Организация не найдена');
		}

		$resultKey = urldecode($resultKey);
		//$initialRequest = true;
		$forceDate = $this -> request -> getHeader('ForceDate');
		$id = $this -> request -> getHeader('Id');

		$weekNumber = (int)$weekNumber;
		$this -> forceDate = $forceDate;

		return $this -> fetchSchedule($idOrganisation, $resultKey, $weekNumber, $id);
	}

	public function fetchSchedule ($idOrganisation, $resultKey, $weekNumber, $id)
	{
		$this -> setTimeMarks();

		if ($this -> forceDate !== "")
		{
			$weekNumber = $this -> getWeekFromDate($this -> forceDate);
			if ($weekNumber < 1)
			{
				$weekNumber = 1;
			}
		}
		elseif ($weekNumber === 0)
		{
			$weekNumber = $this -> currentWeek;
		}

		$controller = $this -> getScheduleController($idOrganisation);
		$controller -> forceDate = $this -> forceDate;
		$controller -> setTimeMarks();

		$scheduleData = $controller -> getScheduleForResult($resultKey, $weekNumber);

		// если на определенную дату запрос
		if ($this -> forceDate !== '')
		{
			for ($i = 0, $max = sizeof($scheduleData['schedule']); $i < $max; $i++)
			{
				if ($scheduleData['schedule'][$i]['date'] < $this -> forceDate)
				{
					unset($scheduleData['schedule'][$i]);
				}
			}
			$scheduleData['schedule'] = array_values($scheduleData['schedule']);
		}

		$scheduleData = $this -> addDaysInfo($scheduleData, $idOrganisation, $resultKey, $id);

		$scheduleData['currentWeek'] = $this -> currentWeek;
		return $scheduleData;
	}
	public function setTimeMarks ()
	{
		$this -> currentDate = date('Y-m-d', strtotime("now +{$this -> timezoneOffset} hours"));
		$this -> currentDayNumber = date('N', strtotime("now +{$this -> timezoneOffset} hours"));
		$this -> currentWeek = $this -> getWeekFromDate(date("Y-m-d", strtotime("now +{$this -> timezoneOffset} hours")));
	}

	public function getWeekFromDate ($date)
	{
		// день недели 1 сентября (пн - 1, вс - 7)
		$diff = date('N', strtotime($this -> startDate));

		// количество часов разницы
		$diff = ($diff - 1) * 24 + $this -> timezoneOffset;

		// дата в переводе на уфимское время и +часы разницы
		$ufaTime = date('Y-m-d H:i:s', strtotime($date) + $diff * 3600);

		$weekNumber = (int)floor((strtotime($ufaTime) - strtotime($this -> startDate)) / (3600 * 24 * 7));

		return $weekNumber;
	}
	public function createEmptyDays ($weekNumber, $parsedWeeksCount = 0)
	{
		$emptyDays = [];
		$tommorowHours = 24 + $this -> timezoneOffset;
		$currentDate = date("Y-m-d", strtotime("now +{$this -> timezoneOffset} hours"));
		$tomorrowDate = date("Y-m-d", strtotime("now +$tommorowHours hours"));

		// если формируем массив для текущей недели и это запрос обновления и это первая неделя в ответе
		if ($weekNumber === $this -> currentWeek and $this -> firstRequest and $parsedWeeksCount === 0 and !$this -> forceDate)
		{
			// начинаем с текущего дня (пн - 1, поэтому убираем 1)
			$startDayNumber = $this -> currentDayNumber - 1;

			// т.к. неделя текущая, ничего не прибавляем для формирования дней
			$futureDaysCount = 0;
		}
		else
		{
			// иначе начинаем с понедельника
			$startDayNumber = 0;

			// разница в днях, учитывающая разницу в неделях
			$futureDaysCount = ($weekNumber - $this -> currentWeek) * 7 - $this -> currentDayNumber + 1;
		}

		for ($i = $startDayNumber; $i < 7; $i++, $futureDaysCount++)
		{
			$futureDate = $this -> parseDayWithPadding($futureDaysCount);
			$formattedDate = $futureDate['formattedDate'];
			if ($futureDate['date'] === $currentDate)
			{
				$formattedDate = 'сегодня';
			}
			elseif ($futureDate['date'] === $tomorrowDate)
			{
				$formattedDate = 'завтра';
			}

			$emptyDays[$futureDate['formattedDate']] =
				[
					'date' => $futureDate['date'],
					'formattedDate' => $formattedDate,
					'dayName' => self::$weekDays[$i],
					'dayNumber' => $i + 1,
					'weekNumber' => $weekNumber,
					'pairs' => [],
					'notesCount' => 0,
					'eventsCount' => 0,
					'notes' => [],
					'events' => []
				];
		}

		return $emptyDays;
	}

	public function parseDayWithPadding ($daysDiff)
	{
		$hoursDiff = $daysDiff * 24 + $this -> timezoneOffset;
		$date = date("Y-m-d", strtotime("now +$hoursDiff hours"));
		$splittedDate = explode('-', $date);
		$mounth = '';

		switch ($splittedDate[1])
		{
			case '01': $mounth = 'января'; break;
			case '02': $mounth = 'февраля'; break;
			case '03': $mounth = 'марта'; break;
			case '04': $mounth = 'апреля'; break;
			case '05': $mounth = 'мая'; break;
			case '06': $mounth = 'июня'; break;
			case '07': $mounth = 'июля'; break;
			case '08': $mounth = 'августа'; break;
			case '09': $mounth = 'сентября'; break;
			case '10': $mounth = 'октября'; break;
			case '11': $mounth = 'ноября'; break;
			case '12': $mounth = 'декабря'; break;
		}

		$splittedDate[2] = preg_replace("/^0/", '', $splittedDate[2]);
		return ['date' => $date, 'formattedDate' => $splittedDate[2] . ' ' . $mounth];
	}

	protected function createDailySchedule ($idOrganisation, $resultKey, $pairs)
	{
		$dailySchedule = new DailySchedule();
		$dailySchedule -> date = $this -> currentDate;
		$dailySchedule -> pairs = json_encode($pairs);
		$dailySchedule -> pairsCount = sizeof($pairs);
		$dailySchedule -> idOrganisation = $idOrganisation;
		$dailySchedule -> resultKey = $resultKey;
		return $dailySchedule -> save();
	}

	private function addDaysInfo ($scheduleData, $idOrganisation, $resultKey, $idUser)
	{
		$person = Persons::findFirstByPhone($idUser);
		for ($i = 0, $max = sizeof($scheduleData['schedule']); $i < $max; $i++)
		{
			$encodedKey = urlencode($resultKey);
			$date = $scheduleData['schedule'][$i]['date'];
			$dateFilter = $date !== "" ? "AND date = '$date'" : "";
			$query = Notes::query() -> columns("id, text, label, date, isPrivate, idPerson") ->
			where("idOrganisation = '$idOrganisation' AND (isPrivate = '1' AND idPerson='{$person -> id}' OR isPrivate = '0') AND (resultKey = '$resultKey' OR resultKey = '$encodedKey') $dateFilter");

			$notesCursor = $query -> execute();
			$notes = [];
			foreach ($notesCursor as $note)
			{
				$noteAr = $note -> toArray();
				$notes[] = $noteAr;
			}
			$scheduleData['schedule'][$i]['notes'] = $notes;

			$query = Events::query() -> columns("id, text, addition1, addition2, time1, time2, eventNumber, eventTypeName, color, date, isPrivate, dayNumber, repeatsCount, idPerson") ->
			where("idOrganisation = '$idOrganisation' AND (isPrivate = '1' AND idPerson='{$person -> id}' OR isPrivate = '0') AND (resultKey = '$resultKey' OR resultKey = '$encodedKey') $dateFilter");

			$eventsCursor = $query -> execute();
			$events = [];
			foreach ($eventsCursor as $event)
			{
				$eventAr = $event -> toArray();
				$events[] = $eventAr;
			}
			$scheduleData['schedule'][$i]['events'] = $events;
		}

		return $scheduleData;
	}

	public function addNote ($idOrganisation, $resultKey)
	{
		$resultKey = urldecode($resultKey);
		list($text, $label, $isPrivate, $date, $vkUserId) = $this -> parseBody(['text', 'label', 'isPrivate', 'date', 'id']);
		$person = $this -> createNewUser($vkUserId, $idOrganisation, $resultKey);

		$note = new Notes();
		$note -> idOrganisation = $idOrganisation;
		$note -> resultKey = $resultKey;
		$note -> text = $text;
		$note -> label = $label;
		$note -> date = $date;
		$note -> createDate = date("Y-m-d H:i:s");
		$note -> isPrivate = $isPrivate;
		$note -> idPerson = $person -> id;
		$note -> images = json_encode([]);
		$note -> save();

		$noteAr = $note -> toArray();
		$noteAr['images'] = [];
		$noteAr['formattedDate'] = $this -> formatDate($date);
		$noteAr['isPrivate'] = $isPrivate ? '1' : '0';
		$noteAr['isAuthor'] = '1';

		/*if (!$isPrivate)
		{
			$notificationText = "$text" . ($label !== '' && $label !== null ? " [$label]" : '');
			$NotificationsController = new NotificationsController();
			$NotificationsController -> sendNotification(["res_" . $this -> translit($resultKey)], "Новая групповая заметка", $notificationText);
		}*/

		return $noteAr;
	}


	public function addEvent ($idOrganisation, $resultKey)
	{
		$resultKey = urldecode($resultKey);

		list($text, $addition1, $addition2, $time1, $time2, $eventNumber, $eventTypeName, $color, $repeatsCount, $isPrivate, $phone, $date) =
			$this -> parseBody(['text', 'addition1', 'addition2', 'time1', 'time2', 'eventNumber', 'eventTypeName', 'color', 'repeatsCount', 'isPrivate', 'id', 'date']);
		$repeatsCount = (int)$repeatsCount;

		$person = Persons::findFirstByPhone($phone);
		if ($person === false)
		{
			//@TODO sdelay norm soobshniya
			$this -> returnErrorResponse('ERROR');
		}

		$startDate = $date;
		$endDate = date("Y-m-d", strtotime($date) + ($repeatsCount - 1) * 7 * 24 * 3600);

		$event = new Events();
		$event -> idOrganisation = $idOrganisation;
		$event -> resultKey = $resultKey;
		$event -> text = $text;
		$event -> addition1 = $addition1;
		$event -> addition2 = $addition2;
		$event -> time1 = $time1;
		$event -> time2 = $time2;
		$event -> eventNumber = $eventNumber;
		$event -> eventTypeName = $eventTypeName;
		$event -> color = $color;
		$event -> date = $date;
		$event -> createDate = date("Y-m-d H:i:s");
		$event -> dayNumber = date("N", strtotime($date));
		$event -> isPrivate = $isPrivate;
		$event -> idPerson = $person -> id;
		$event -> startDate = $startDate;
		$event -> endDate = $endDate;
		$event -> repeatsCount = $repeatsCount;
		$event -> save();

		$eventAr = $event -> toArray();
		$eventAr['formattedDate'] = $this -> formatDate($date);
		$eventAr['isPrivate'] = $isPrivate ? '1' : '0';
		$eventAr['isAuthor'] = '1';


		/*		if (!$isPrivate)
				{
					$NotificationsController = new NotificationsController();
					$NotificationsController -> sendNotification(["res_" . $this -> translit($resultKey)], "Новое групповое событие", $text);
				}*/

		return $eventAr;
	}


	public function removeNote ($idNote)
	{
		$note = Notes::findFirstById($idNote);
		$note -> delete();
	}

	// хзкак отправлять уведомления
	public function test ()
	{
		echo file_get_contents('https://api.vk.com/api.php?oauth=1&method=secure.sendNotification&message=test&user_id=27719557&user_ids=27719557&v=5.110&access_token=f1e842dcf1e842dcf1e842dca4f19af29eff1e8f1e842dcaf058cdd0121e01faef9b2ab');
	}

	public function removeEvent ($idEvent)
	{
		$note = Events::findFirstById($idEvent);
		$note -> delete();
	}

	public function getNotes ($idOrganisation, $resultKey)
	{
		$id = $this -> request -> getHeader('Id');
		$person = Persons::findFirstByPhone($id);
		$encodedKey = urldecode($resultKey);
		$query = Notes::query() -> columns("id, text, label, date, isPrivate, idPerson") ->
		where("idOrganisation = '$idOrganisation' AND (isPrivate = '1' AND idPerson='{$person -> id}' OR isPrivate = '0') AND (resultKey = '$resultKey' OR resultKey = '$encodedKey') ORDER by date DESC");

		$notesCursor = $query -> execute();
		$notes = [];
		foreach ($notesCursor as $note)
		{
			$noteAr = $note -> toArray();
			$notes[] = $noteAr;
		}

		return $notes;
	}

	public function getData ($idOrganisation, $resultKey)
	{
		$organisation = Organisations::findFirstById($idOrganisation);
		if ($organisation === false)
		{
			$this -> returnErrorResponse('ERROR');
		}

		$resultKey = urldecode($resultKey);
		$result = null;
		switch ($idOrganisation)
		{
			case "1":
				if (is_numeric($resultKey))
				{
					$teacher = UgntuTeachers::findFirstByHandleId($resultKey);
					$result = ['idResult' => $teacher -> handleId, 'nameResult' => $teacher -> name, 'department' => $teacher -> ugntuDepartments -> short];
				}
				else
				{
					$group = UgntuGroups::findFirstByName($resultKey);
					$result = ['idResult' => $group -> name, 'nameResult' => $group -> name, 'department' => $group -> ugntuFaculties -> name];
				}
				break;
			default :
				$group = CustomGroups::findFirstById($resultKey);
				$result = ['idResult' => $group -> id, 'nameResult' => $group -> name, 'department' => ''];
				break;
		}


		return ['organisation' => $organisation -> toArray(), 'result' => $result];
	}

	private function getScheduleController ($idOrganisation)
	{
		switch ($idOrganisation)
		{
			case "1":
				$className = 'HakatonUgntuScheduleController';
				break;
			default :
				$className = 'HakatonCustomScheduleController';
				break;
		}

		return new $className();
	}
	public function createNewUser ($vkUserId, $idOrganisation, $idResult)
	{
		// кароч я тут прописал чтобы в старуб базу пулялось вместо теелфона айди чтбы таблцу не создавать
		// не запутайся при создании заметок
		$person = Persons::findFirstByPhone($vkUserId);
		if ($person !== null)
		{
			$person -> idOrganisation = $idOrganisation;
			$person -> resultKey = $idResult;
			$person -> save();
			return $person;
		}
		else
		{
			$person = new Persons();
			$person -> idOrganisation = $idOrganisation;
			$person -> resultKey = $idResult;
			$person -> phone = $vkUserId; //@TODO не накосячить
			$person -> createDate = date("Y-m-d H:i:s");
			$person -> save();
			return $person;
		}
	}


}
