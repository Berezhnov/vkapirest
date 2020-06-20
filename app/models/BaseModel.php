<?php

class BaseModel extends \Phalcon\Mvc\Model
{
	/**
	 * преобразование даты
	 * @param string $date
	 * @return string
	 */
	public static function parseDate ($date)
	{
		//2016-02-17 01:39 разбиваем по пробелу на дату и время
		$date = explode(' ', $date);
		$time = $date[1];
		$date = $date[0];

		$date = explode('-', $date);
		$mounth = '';
		switch ($date[1])
		{
			case '01':
				$mounth = 'января';
				break;
			case '02':
				$mounth = 'февраля';
				break;
			case '03':
				$mounth = 'марта';
				break;
			case '04':
				$mounth = 'апреля';
				break;
			case '05':
				$mounth = 'мая';
				break;
			case '06':
				$mounth = 'июня';
				break;
			case '07':
				$mounth = 'июля';
				break;
			case '08':
				$mounth = 'августа';
				break;
			case '09':
				$mounth = 'сентября';
				break;
			case '10':
				$mounth = 'октября';
				break;
			case '11':
				$mounth = 'ноября';
				break;
			case '12':
				$mounth = 'декабря';
				break;
		}

		$date[2] = preg_replace("/^0/", '', $date[2]);
		return $date[2] . ' ' . $mounth . ' ' . implode(':', array_slice(explode(':', $time), 0, 2));
	}

	/**
	 * генерация уникального значения на основе уникального поля
	 * @param string $salt
	 * @param string[] $existingParams
	 * @return string $hash
	 */
	public static function generateUnicValue ($salt, $existingParams)
	{
		$uniqueValue = sha1($salt . rand(0, 1000000000));
		while (in_array($uniqueValue, $existingParams))
		{
			$uniqueValue = sha1($salt . rand(0, 100000000));
		}
		return $uniqueValue;
	}
}
