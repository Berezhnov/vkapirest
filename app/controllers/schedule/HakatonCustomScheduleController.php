<?php

class HakatonCustomScheduleController extends ScheduleController
{
	// левых универам ставим -1
	public $idOrganisation = -1;

	public function getScheduleForResult ($resultKey, $weekNumber)
	{
		$customGroup = CustomGroups::findFirstById($resultKey);

		if ($customGroup === null)
		{
			return $this -> returnErrorResponse('Не удалось определить группу или преподавателя');
		}

		$this -> maxWeeksForParse = 3;
		$weekNumberForParse = 0; // номер недели, для которой получается расписание
		$parsedWeeksCount = 0;   // количество обработанных недель
		$daysPairs = [];         // массив расписания

		while ($parsedWeeksCount < $this -> maxWeeksForParse)
		{
			$weekNumberForParse = $weekNumber + $parsedWeeksCount;
			if ($weekNumberForParse > $this -> maxWeekNumber)
			{
				break;
			}

			$weekDaysPairs = $this -> parseSchedule($weekNumberForParse, $parsedWeeksCount, $customGroup);
			$daysPairs = array_merge($daysPairs, $weekDaysPairs);
			$parsedWeeksCount++;
		}
		$weekNumberForParse++;

		$daysPairs = array_values($daysPairs);
		return ['schedule' => $daysPairs, 'lastWeek' => $weekNumberForParse];
	}

	protected function parseSchedule ($weekNumber, $parsedWeeksCount, $customGroup)
	{
		$daysPairs = $this -> createEmptyDays($weekNumber, $parsedWeeksCount);
		return $daysPairs;
	}

}
