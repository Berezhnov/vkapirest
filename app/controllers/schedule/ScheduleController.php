<?php

setcookie('XDEBUG_PROFILE', null, 0);
ini_set('xdebug.var_display_max_depth', 8);
set_time_limit(600);

class ScheduleController extends BaseController
{
	// дата, с которой начинается учебный год
	public $startDate = '2019-09-01 00:00:00';

	// номер текущей недели
	public $currentWeek;

	// номер текущего дня (1 - пн, 7 - вс)
	public $currentDayNumber;

	public $currentDate;

	// максимальный номер недели
	public $maxWeekNumber = 40;

	// количество недель, отдаваемых за запрос
	public $maxWeeksForParse = 2;

	public static $weekDays  = ['Понедельник', 'Вторник', 'Среда', 'Четверг', 'Пятница', 'Суббота', 'Воскресенье'];

	// если запрос на определенную дату
	public $forceDate = '';

	/**
	 * получение расписания
	 * @param string $idOrganisation - id учебного заведения
	 * @param string $resultKey - ключ для поиска группы или препода
	 * @param string $weekNumber - номер недели, с которой нужно получить расписание
	 * @return mixed
	 */
	public function getSchedule ($idOrganisation, $resultKey, $weekNumber)
	{
		//setcookie('XDEBUG_PROFILE', null, 0);
		//ini_set('xdebug.var_display_max_depth', 8);

		$organisation = Organisations::findFirstById($idOrganisation);
		if ($organisation === false)
		{
			$this -> returnErrorResponse('Организация не найдена');
		}

		$resultKey = urldecode($resultKey);
		$initialRequest = $this -> request -> getHeader('InitialRequest') === '1' ? true : false;
		//$initialRequest = true;
		$fullFio = $this -> request -> getHeader('Full-Fio');
		$addPublicNotesInSchedule = $this -> request -> getHeader('AddPublicNotes');
		$addPrivateNotesInSchedule = $this -> request -> getHeader('AddPrivateNotes');
		$addPublicEventsInSchedule = $this -> request -> getHeader('AddPublicEvents');
		$addPrivateEventsInSchedule = $this -> request -> getHeader('AddPrivateEvents');
		$forceDate = $this -> request -> getHeader('ForceDate');
		$phone = $this -> request -> getHeader('Phone');
		$timezoneOffset = (int)$this -> request -> getHeader('TimezoneOffset');

		$person = null;
		if ($phone !== '')
		{
			$person = PersonsController::createOrUpdatePerson($phone, $idOrganisation, $resultKey);
		}

		$weekNumber = (int)$weekNumber;
		$this -> initialRequest = $initialRequest;
		$this -> firstRequest = $weekNumber === 0 ? true : false;
		$this -> timezoneOffset = (int)$timezoneOffset;
		$this -> forceDate = $forceDate;

		return $this -> fetchSchedule($idOrganisation, $resultKey, $weekNumber, $fullFio, $person,
			$addPublicNotesInSchedule, $addPrivateNotesInSchedule, $addPublicEventsInSchedule, $addPrivateEventsInSchedule, $phone);
	}

	/**
	 * @param string $idOrganisation
	 * @param string $resultKey
	 * @param string $weekNumber
	 * @param boolean $fullFio
	 * @param Persons $person
	 * @param $addPublicNotesInSchedule
	 * @param $addPrivateNotesInSchedule
	 * @param $addPublicEventsInSchedule
	 * @param $addPrivateEventsInSchedule
	 * @param string $phone
	 * @return mixed
	 */
	public function fetchSchedule ($idOrganisation, $resultKey, $weekNumber, $fullFio, $person,
	                               $addPublicNotesInSchedule, $addPrivateNotesInSchedule, $addPublicEventsInSchedule,
	                               $addPrivateEventsInSchedule, $phone = '')
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
		$controller -> timezoneOffset = $this -> timezoneOffset;
		$controller -> initialRequest = $this -> initialRequest;
		$controller -> firstRequest = $this -> firstRequest;
		$controller -> forceDate = $this -> forceDate;
		$controller -> setTimeMarks();

		$scheduleData = $controller -> getScheduleForResult($resultKey, $weekNumber, $fullFio === '1' ? true : false);

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

		$scheduleData = $this -> addDaysInfo($scheduleData, $idOrganisation, $resultKey, $person, $addPublicNotesInSchedule, $addPrivateNotesInSchedule, $addPublicEventsInSchedule, $addPrivateEventsInSchedule);
		if ($this -> firstRequest)
		{
			$scheduleData = $this -> addAdvertInfo($scheduleData, $idOrganisation, $phone);
		}

		$scheduleData['currentWeek'] = $this -> currentWeek;
		return $scheduleData;
	}

	/**
	 * сбор расписания для текущего для (нужно для push-уведомлений перед парами)
	 */
	public function gatherDailySchedule ()
	{
		set_time_limit(60 * 60 * 3);
		$connectedOrganisation = Organisations::find();
		foreach ($connectedOrganisation as $organisation)
		{
			$controller = $this -> getScheduleController($organisation -> id);
			$controller -> collectDailySchedule($organisation -> id);
		}
	}

	/**
	 * получение номера текущей недели и номер дня
	 */
	public function setTimeMarks ()
	{
		$this -> currentDate = date('Y-m-d', strtotime("now +{$this -> timezoneOffset} hours"));
		$this -> currentDayNumber = date('N', strtotime("now +{$this -> timezoneOffset} hours"));
		$this -> currentWeek = $this -> getWeekFromDate(date("Y-m-d", strtotime("now +{$this -> timezoneOffset} hours")));
	}

	/**
	 * получение номера недели по дате
	 * @param string $date
	 * @return int
	 */
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

	/**
	 * создание дней без пар для дня недели
	 * @param int $weekNumber - номер недели, для которой создается пустой массив дней
	 * @param int $parsedWeeksCount - количество сформированный недель для запроса
	 * @return array
	 */
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

	/**
	 * получение даты с отступом от текущего дня
	 * @param int $daysDiff
	 * @return mixed
	 */
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

	/**
	 * сохранение информации о дневном расписании (для уведомлений)
	 * @param $idOrganisation
	 * @param $resultKey
	 * @param $pairs
	 * @return bool
	 */
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

	/**
	 * добавление информации о событиях и заметках в массив расписания
	 * @param array $scheduleData
	 * @param string $idOrganisation
	 * @param string $resultKey
	 * @param Persons $person
	 * @param string $addPublicNotesInSchedule
	 * @param string $addPrivateNotesInSchedule
	 * @param string $addPublicEventsInSchedule
	 * @param string $addPrivateEventsInSchedule
	 * @return array
	 */
	private function addDaysInfo ($scheduleData, $idOrganisation, $resultKey, $person,
	                              $addPublicNotesInSchedule, $addPrivateNotesInSchedule, $addPublicEventsInSchedule, $addPrivateEventsInSchedule)
	{
		$notesController = new NotesController();
		$eventsController = new EventsController();
		for ($i = 0, $max = sizeof($scheduleData['schedule']); $i < $max; $i++)
		{
			$scheduleData['schedule'][$i]['notesCount'] = $notesController::getNotesCount($idOrganisation, $resultKey, $scheduleData['schedule'][$i]['date'], $person);
			$scheduleData['schedule'][$i]['eventsCount'] = $eventsController::getEventsCount($idOrganisation, $resultKey, $scheduleData['schedule'][$i]['date'], $person);
			$scheduleData['schedule'][$i]['notes'] = [];
			$scheduleData['schedule'][$i]['events'] = [];

			if ($scheduleData['schedule'][$i]['notesCount'] !== 0 and $addPublicNotesInSchedule === '1' or $addPrivateNotesInSchedule === '1')
			{
				$scheduleData['schedule'][$i]['notes'] = $notesController -> fetchNotes($idOrganisation, $resultKey,
					$scheduleData['schedule'][$i]['date'], $person, $addPublicNotesInSchedule === '1' ? true : false, $addPrivateNotesInSchedule === '1' ? true : false);
			}

			if ($scheduleData['schedule'][$i]['eventsCount'] !== 0 and $addPublicEventsInSchedule === '1' or $addPrivateEventsInSchedule === '1')
			{
				$scheduleData['schedule'][$i]['events'] = $eventsController -> fetchEvents($idOrganisation, $resultKey,
					$scheduleData['schedule'][$i]['date'], $person, $addPublicEventsInSchedule === '1' ? true : false, $addPrivateEventsInSchedule === '1' ? true : false);
			}
		}

		return $scheduleData;
	}

	/**
	 * @param array $scheduleData
	 * @param $idOrganisation
	 * @param $phone
	 * @return array
	 */
	private function addAdvertInfo ($scheduleData, $idOrganisation, $phone)
	{
		$withoutAdPhones = ['79991343060', '79273452615', '79270850000', '79371608093',
			'79174144481', '79174178349', '79224807230', '79174297994', '79659435620', '79050027152', '79174766246',
			'79174411735', '79174553850', '79608045184', '79373309291', '79659362798', '79996232082'];

		if (in_array($phone, $withoutAdPhones))
		{
			return $scheduleData;
		}

		$hour = (int)date("H");
		if (true or $hour % 2 === 0)
		{
			$defaultVariants = [
				['isAdvert' => true, 'isNative' => true, 'header' => 'Отключи рекламу. PRO-версия.', 'link' => 'purchase',
					'text' => 'Её цена это 36% от цены Доширака. 15% от цены латте, которое ты взял утром в CoffeeLike. И 17% от цены шавы в плове.',
					'imageLink' => 'https://5.avatars.yandex.net/get-eda/1387779/aea9bd86190de89d9272c4325e4eaf8a/700x525'],
				['isAdvert' => true, 'isNative' => true, 'header' => 'Нравится приложение? Оставь отзыв!', 'link' => 'rate',
					'text' => 'Разработчик читает хорошие отзывы по вечерам и плачет от умиления.',
					'imageLink' => 'https://i1.sndcdn.com/artworks-000372762501-5yuset-t500x500.jpg'],
				['isAdvert' => true, 'isNative' => true, 'header' => 'Оставил отзыв - отключил рекламу', 'link' => 'rate',
					'text' => 'Авторизуйся, оставь отзыв в AppStore/Play market, свяжись с нами в VK или whatsapp и мы отключим для тебя рекламу.',
					'imageLink' => 'https://s2.hostingkartinok.com/uploads/images/2012/07/4b60252ec3254d3d0db9214b9ed846a6.png'],
				/*['isAdvert' => true, 'isNative' => true, 'header' => 'Нужна реклама?', 'link' => 'https://wa.me/79170465300',
					'text' => 'Закажи её у нас. 4 200 уникальных пользователей в день.',
					'imageLink' => 'https://i1.sndcdn.com/avatars-000516289629-nxh2ps-t500x500.jpg'],*/
			];

			$defaultVariants = [
				[
					'isAdvert' => true, 'isNative' => true,
					'header' => 'Будьте внимательны',
					'link' => 'https://vk.com/@team-vs-coronavirus',
					'text' => 'Мойте руки, надевайте маски, занимайтесь сексом и берегите себя и своих близких.',
					'imageLink' => 'data:image/jpeg;base64,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'
				]
			];

		}
		else
		{
			$defaultVariants = [
				['isAdvert' => true, 'isNative' => false]
			];
		}

		$variants = $defaultVariants;

		$date2 = date("Y-m-d H:i:s");
/*		$variants = [
			[
				'isAdvert' => true, 'isNative' => true,
				'header' => 'Будьте внимательны',
				'link' => 'https://vk.com/@team-vs-coronavirus',
				'text' => 'Мойте руки, надевайте маски, занимайтесь сексом и берегите себя и своих близких.',
				'imageLink' => 'data:image/jpeg;base64,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'
			]
		];*/

		$advert = $this -> getRandomAdvert($variants);
		if ($advert === null)
		{
			return $scheduleData;
		}

		$org = Organisations::findFirstById($idOrganisation);
		if (preg_match("/угнту/i", $org -> name) and $idOrganisation !== '1')
		{
			$advert = ['isAdvert' => true, 'isNative' => true, 'header' => 'Не та организация', 'link' => '',
				'text' => 'Вы выбрали не ту организацию, поэтому расписания нет. Зайдите в меню (справа), затем в профиль, сбросьте организацию и выберите УГНТУ, подключенное к системе!',
				'imageLink' => ''];
		}

		$advertPosition = 1;
		$schedule = $scheduleData['schedule'];
		array_splice($schedule, $advertPosition, 0, [$advert]);
		$scheduleData['schedule'] = $schedule;
		$scheduleData['advertPosition'] = $advertPosition;


		return $scheduleData;
	}

	private function getRandomAdvert ($variants)
	{
		if (sizeof($variants) === 0)
		{
			return null;
		}
		elseif (sizeof($variants) === 1)
		{
			$key = 0;
		}
		else
		{
			$previousKey = $this -> cache -> get('lastAdKey');
			$key = $previousKey;
			while ($key === $previousKey)
			{
				$key = array_rand($variants);
			}
		}

		$this -> cache -> set('lastAdKey', $key);
		return $variants[$key];
	}

	private function getScheduleController ($idOrganisation)
	{
		switch ($idOrganisation)
		{
			case "1":
				$className = 'UgntuScheduleController';
				break;
			case "253":
				$className = 'InkScheduleController';
				break;
			case "277":
				$className = 'YatkScheduleController';
				break;
			default :
				$className = 'CustomScheduleController';
				break;
		}

		return new $className();
	}

	public function test ()
	{

	}
}
