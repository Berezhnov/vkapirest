<?php

class HakatonUgntuScheduleController extends ScheduleController
{
	private $apiUrl = 'http://www.raspisanie.rusoil.net/origins.php?function=';


	// из старых файлов
	public static $TIMES =
		[
			'1' => //Основное 1
				[
					['beg' => '8:45', 'end' => '10:20'],
					['beg' => '10:30', 'end' => '12:05'],
					['beg' => '12:15', 'end' => '13:50'],
					['beg' => '14:35', 'end' => '16:10'],
					['beg' => '16:20', 'end' => '17:55'],
					['beg' => '18:05', 'end' => '19:40'],
					['beg' => '19:50', 'end' => '21:15'],
					['beg' => '00:00', 'end' => '00:00'],
					['beg' => '00:00', 'end' => '00:00'],
					['beg' => '00:00', 'end' => '00:00'],
					['beg' => '00:00', 'end' => '00:00']
				],

			'2' => // Салават 12
				[
					['beg' => '8:30', 'end' => '10:00'],
					['beg' => '10:10', 'end' => '11:40'],
					['beg' => '12:10', 'end' => '13:40'],
					['beg' => '13:50', 'end' => '15:20'],
					['beg' => '15:30', 'end' => '17:00'],
					['beg' => '00:00', 'end' => '00:00'],
					['beg' => '00:00', 'end' => '00:00'],
					['beg' => '00:00', 'end' => '00:00'],
					['beg' => '00:00', 'end' => '00:00']
				],
			'3' => // Октябрьский 11
				[
					['beg' => '9:00', 'end' => '10:35'],
					['beg' => '10:45', 'end' => '12:20'],
					['beg' => '13:20', 'end' => '14:55'],
					['beg' => '15:05', 'end' => '16:40'],
					['beg' => '16:50', 'end' => '18:25'],
					['beg' => '18:35', 'end' => '20:05'],
					['beg' => '20:15', 'end' => '21:50'],
					['beg' => '00:00', 'end' => '00:00'],
					['beg' => '00:00', 'end' => '00:00'],
					['beg' => '00:00', 'end' => '00:00'],
					['beg' => '00:00', 'end' => '00:00']
				],
			'4' => // Стерлитамак 13
				[
					['beg' => '9:00', 'end' => '10:35'],
					['beg' => '10:45', 'end' => '12:20'],
					['beg' => '13:00', 'end' => '14:35'],
					['beg' => '14:45', 'end' => '16:20'],
					['beg' => '16:30', 'end' => '18:05'],
					['beg' => '18:30', 'end' => '20:05'],
					['beg' => '20:15', 'end' => '21:50'],
					['beg' => '00:00', 'end' => '00:00'],
					['beg' => '00:00', 'end' => '00:00'],
					['beg' => '00:00', 'end' => '00:00']
				],
			'5' => // АСИ 17
				[
					['beg' => '9:00', 'end' => '10:35'],
					['beg' => '10:45', 'end' => '12:20'],
					['beg' => '12:30', 'end' => '14:05'],
					['beg' => '15:05', 'end' => '16:40'],
					['beg' => '16:50', 'end' => '18:25'],
					['beg' => '18:35', 'end' => '20:10'],
					['beg' => '20:20', 'end' => '21:55'],
					['beg' => '00:00', 'end' => '00:00'],
					['beg' => '00:00', 'end' => '00:00'],
					['beg' => '00:00', 'end' => '00:00']
				]
		];
	public static $lessonTypes =
		[
			'1' => ['лек', 'green', 1, 'Лекция'],
			'2' => ['кнс', 'blue', 2, 'Консультация'],
			'3' => ['прк', 'blue', 2, 'Практика'],
			'4' => ['лаб', 'red', 3, 'Лаба'],
			'5' => ['РГР', 'yellow', 4, 'РГР'],
			'6' => ['КР', 'yellow', 4, 'КР'],
			'7' => ['КП', 'yellow', 4, 'КП'],
			'8' => ['зач', 'yellow', 4, 'Зачёт'],
			'9' => ['экз', 'yellow', 4, 'Экзамен'],
			'10' => ['срс', 'yellow', 4, 'Срс'],
			'11' => ['уч.пр', 'yellow', 4, 'Уч.пр'],
			'12' => ['пр.пр', 'yellow', 4, 'Пр.пр'],
			'13' => ['дипл', 'yellow', 4, 'Дипл'],
			'14' => ['ГЭК', 'yellow', 4, 'ГЭК'],
			'15' => ['—', 'yellow', 4, '—'],
			'16' => ['зач', 'yellow', 4, 'Зачёт'],
			'17' => ['тест', 'yellow', 4, 'Тест'],
			'18' => ['реп', 'yellow', 4, 'Реп'],
			'19' => ['кнс', 'blue', 2, 'Консультация'],
			'20' => ['кнс', 'blue', 2, 'Консультация'],
			'21' => ['КСРС', 'yellow', 4, 'КСРС'],
			'51' => ['лек+', 'green', 1, 'Лекция'],
			'53' => ['прк+', 'blue', 2, 'Практика'],
			'54' => ['лаб+', 'red', 3, 'Лаба'],
			'56' => ['КР+', 'yellow', 4, 'КР'],
			'57' => ['КП+', 'yellow', 4, 'КП'],
			'58' => ['зач+', 'yellow', 4, 'Зачёт'],
			'59' => ['экз+', 'yellow', 4, 'Экзамен'],
			'66' => ['зач+', 'yellow', 4, 'Зачёт']
		];

	public function getScheduleForResult ($resultKey, $weekNumber, $fullFio = false)
	{
		$isGroup = is_numeric($resultKey) ? false : true;
		$searchResult = $isGroup ? UgntuGroups::findFirstByName($resultKey) : UgntuTeachers::findFirstByHandleId($resultKey);

		if ($searchResult === null)
		{
			return $this -> returnErrorResponse('Не удалось определить группу или преподавателя');
		}

		if ($this -> currentDayNumber > 5)
		{
			$this -> maxWeeksForParse = 2;
		}

		$weekNumberForParse = 0; // номер недели, для которой получается расписание
		$parsedWeeksCount = 0;   // количество обработанных недель
		$daysPairs = [];         // массив расписания
		$currentHour = date("H");

		while ($parsedWeeksCount < $this -> maxWeeksForParse)
		{
			$weekNumberForParse = $weekNumber + $parsedWeeksCount;
			if ($weekNumberForParse > $this -> maxWeekNumber)
			{
				break;
			}

			//@TODO Дмим сделай так чтобы преподы и гуппы одинаков выдавались
			$cacheKey = "org_1_result_" . $this -> translit($resultKey) . "_$weekNumberForParse.cache";
			$weekDaysPairs = $this -> cache -> get($cacheKey);
			if (is_null($weekDaysPairs) or in_array($currentHour, ['22', '23']))
			{
				if ($isGroup)
				{
					$rawSchedule = $this -> parseGroupSchedule($resultKey, $weekNumberForParse, $searchResult -> ugntuFaculties -> idFilial);
					$weekDaysPairs = $this -> setFormatGroupsScheduleUgntu($rawSchedule, $weekNumberForParse, $searchResult -> idCorpus, $resultKey, $parsedWeeksCount);
				}
				else
				{
					$rawSchedule = $this -> parseTeacherSchedule($resultKey, $weekNumberForParse);
					$weekDaysPairs = $this -> setFormatTeacherScheduleUgntu($rawSchedule, $weekNumberForParse, $parsedWeeksCount);
				}
				$this -> cache -> set($cacheKey, $weekDaysPairs);
			}

			$daysPairs = array_merge($daysPairs, $weekDaysPairs);
			$parsedWeeksCount++;
		}
		$weekNumberForParse++;

		$daysPairs = array_values($daysPairs);

		return ['schedule' => $daysPairs, 'lastWeek' => $weekNumberForParse];
	}

	private function parseGroupSchedule ($nameGroup, $weekNumber, $idFilial)
	{
		$params = [];
		$params['gruppa'] = $nameGroup;
		$params['beginweek'] = $weekNumber;
		$params['endweek'] = $weekNumber;
		$params['id_filial'] =   (int)$idFilial;

		$curl = $this -> getCurl();
		curl_setopt($curl, CURLOPT_URL, $this -> apiUrl . 'get_rasp_student');
		curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($params));
		$response = curl_exec($curl);
		curl_close($curl);
		$schedule = json_decode($response, true);

		return $schedule;
	}

	private function setFormatGroupsScheduleUgntu ($schedule, $weekNumber, $defaultIdCorpus, $groupName, $parsedWeeksCount)
	{
		$daysAdd = ($weekNumber - $this -> currentWeek) * 7 - $this -> currentDayNumber;
		$daysPairs = $this -> createEmptyDays($weekNumber, $parsedWeeksCount);
		$previousPairNumber = 0;
		$previousDayNumber = 0;

		for ($i = 0, $max = sizeof($schedule); $i < $max; $i++)
		{
			$pairDayNumber = (int)$schedule[$i]['DAYWEEK'];
			$pairDate = $this -> parseDayWithPadding($pairDayNumber + $daysAdd)['formattedDate'];

			// если такой даты нет в списке, значит пара уже прошла
			if (!isset($daysPairs[$pairDate]))
			{
				continue;
			}

			$idCorpus = (isset($schedule[$i]['KORPUS']) and $schedule[$i]['KORPUS'] != '' and !is_null($schedule[$i]['KORPUS'])) ? $schedule[$i]['KORPUS'] : $defaultIdCorpus;
			$times = self::getTimes($idCorpus);
			$pairNumber = (int)$schedule[$i]['PARA'];
			$lessonTypeInfo = self::$lessonTypes[$schedule[$i]['VIDZANAT']];
			$subjectName = (!is_null($schedule[$i]['NDISC']) && $schedule[$i]['NDISC'] != '') ? $schedule[$i]['NDISC'] : '—';
			$subGroupNumber = (!is_null($schedule[$i]['PODGRUPPA']) && $schedule[$i]['PODGRUPPA'] != '') ? $schedule[$i]['PODGRUPPA'] : '0';

			list ($timeBegin, $timeEnd) = $this -> getTimesForPair($times, $pairNumber, $groupName);

			$pair =
				[
					'pairNumber' => $pairNumber,
					'subjectName' => mb_strtoupper(mb_substr($subjectName, 0, 1, 'utf-8'), 'utf-8') . mb_substr($subjectName, 1),
					'subGroupNumber' => $subGroupNumber,
					'audNumber' => $this -> clearString($schedule[$i]['AUD']),
					'uniquePairName' => $this -> clearString($schedule[$i]['FIO']),
					'uniquePairId' => (int)$schedule[$i]['KADR_ID'],
					'isDoubled' => '0',
					'lessonInfo' => ['name' => $lessonTypeInfo[0], 'number' => $lessonTypeInfo[2] - 1],
					'time' => ['begin' => $timeBegin, 'end' => $timeEnd]
				];

			if ($previousPairNumber === $pairNumber and $previousDayNumber === $pairDayNumber)
			{
				$pair['isDoubled'] = '1'; // если тот же день и та же пара, значит это сдвоенная пара (2 подгруппы, одно время)
			}

			$previousDayNumber = $pairDayNumber;
			$previousPairNumber = $pairNumber;
			$daysPairs[$pairDate]['pairs'][] = $pair;
		}
		return $daysPairs;
	}

	private function parseTeacherSchedule ($handleId, $weekNumber)
	{
		$params = [];
		$params['kadr_id'] = $handleId;
		$params['beginweek'] = $weekNumber;
		$params['endweek'] = $weekNumber;

		$curl = $this -> getCurl();
		curl_setopt($curl, CURLOPT_URL, $this -> apiUrl . 'get_rasp_prepod');
		curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($params));
		$response = curl_exec($curl);
		curl_close($curl);
		$schedule = json_decode($response, true);

		return isset($schedule['rasp']) ? $schedule['rasp'] : [];
	}

	private function setFormatTeacherScheduleUgntu ($schedule, $weekNumber, $parsedWeeksCount)
	{
		$daysAdd = ($weekNumber - $this -> currentWeek) * 7 - $this -> currentDayNumber;
		$daysPairs = $this -> createEmptyDays($weekNumber, $parsedWeeksCount);
		$previousPairNumber = 0;
		$previousDayNumber = 0;

		for ($i = 0, $max = sizeof($schedule); $i < $max; $i++)
		{
			$intervals = explode(";", $schedule[$i]['INTERVAL']);
			for ($j = 0, $pairsCount = sizeof($intervals); $j < $pairsCount; $j++)
			{
				if (!$intervals[$j])
				{
					continue;
				}

				$pairDayNumber = (int)$schedule[$i]['DAYWEEK'];
				$pairDate = $this -> parseDayWithPadding($pairDayNumber + $daysAdd)['formattedDate'];

				// если такой даты нет в списке, значит пара уже прошла
				if (!isset($daysPairs[$pairDate]))
				{
					continue;
				}

				$idCorpus = (isset($schedule[$i]['KORPUS']) and $schedule[$i]['KORPUS'] != '' and !is_null($schedule[$i]['KORPUS'])) ? $schedule[$i]['KORPUS'] : -1;
				$times = self::getTimes($idCorpus);
				$pairNumber = (int)$schedule[$i]['PARA'];
				$lessonTypeInfo = self::$lessonTypes[$schedule[$i]['VIDZANAT']];
				$subjectName = $this -> clearString((is_null($schedule[$i]['NDISC']) && $schedule[$i]['NDISC'] != '') ? '—' : $schedule[$i]['NDISC']);

				$pair =
					[
						'pairNumber' => $pairNumber,
						'subjectName' => $subjectName,
						'subGroupNumber' => '0',
						'audNumber' => $this -> clearString($schedule[$i]['AUD']),
						'uniquePairName' => $this -> clearString($schedule[$i]['GRUPPA']),
						'uniquePairId' => (int)$schedule[$i]['DISC'],
						'isDoubled' => '0',
						'lessonInfo' => ['name' => $lessonTypeInfo[0], 'number' => $lessonTypeInfo[2] - 1],
						'time' => ['begin' => $times[$pairNumber - 1]['beg'], 'end' => $times[$pairNumber - 1]['end']]
					];


				if ($previousPairNumber == $pair['pairNumber'] and $previousDayNumber == $pairDayNumber)
				{
					$pair['isDoubled'] = '1'; //если тот же день и та же пара, значит это сдвоенная пара (2 подгруппы)
				}

				$previousDayNumber = $pairDayNumber;
				$previousPairNumber = $pairNumber;
				$daysPairs[$pairDate]['pairs'][] = $pair;
			}
		}
		return $daysPairs;
	}

	// взял из старого там верное время
	public static function getTimes ($idCorpus = -1)
	{
		if ($idCorpus == -1 or $idCorpus === '')
		{
			return self::$TIMES[1];
		}
		switch ($idCorpus)
		{
			case ($idCorpus >= 200 and $idCorpus < 300) : //октябрьский
				return self::$TIMES[3];
				break;
			case ($idCorpus >= 300 and $idCorpus < 400) : //салават
				return self::$TIMES[2];
				break;
			case ($idCorpus >= 100 and $idCorpus < 200) : //стерлитамак
				return self::$TIMES[4];
				break;
			case 5 : //аси
			case 6 :
				return self::$TIMES[5];
				break;
			default : //основной корпус
				return self::$TIMES[1];
		}
	}

	private function getTimesForPair ($times, $pairNumber, $groupName)
	{
		$timeBegin = $times[$pairNumber - 1]['beg'];
		$timeEnd = $times[$pairNumber - 1]['end'];
		if (preg_match("/^.+в-\d{2}-\d{2}$/", $groupName) !== 0)
		{
			if ($pairNumber === 6)
			{
				$timeBegin = '18:55';
				$timeEnd = '20:30';
			}
			elseif ($pairNumber === 7)
			{
				$timeBegin = '20:35';
				$timeEnd = '22:00';
			}
		}

		return [$timeBegin, $timeEnd];
	}
}
