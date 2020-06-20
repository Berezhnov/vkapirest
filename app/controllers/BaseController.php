<?php

use Phalcon\Mvc\Controller;
date_default_timezone_set('Europe/Moscow');

/**
 * Class BaseController
 * @property Phalcon\Cache cache
 * @property Phalcon\Cache cacheFiles
 * @property Phalcon\Logger\Adapter\Stream notificationsLogger
 */
class BaseController extends Controller
{
	public $timezoneOffset = 2;

	/**
	 * очистка строки от лишних символов
	 * @param string $string
	 * @return string
	 */
	protected function clearString ($string)
	{
		$string = preg_replace("/;/", " ", $string);
		$string = preg_replace("/ {2,}/", " ", $string);
		return trim(preg_replace("/\(.*|;$|^-$|\.(?=-)|\. \./", "", $string));
	}

	/**
	 * возвращает экземпляр curl
	 * @return mixed
	 */
	protected function getCurl ()
	{
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 30);
		return $curl;
	}

	/**
	 * @param string $date
	 * @param bool $withTime
	 * @return string
	 */
	protected function formatDate ($date, $withTime = false)
	{
		if (!preg_match("/:/", $date))
		{
			$date .= ' 00:00:00';
		}

		$today = date("Y-m-d");
		$yesterday = date("Y-m-d", strtotime("now -1 days"));
		$splittedDate = explode(' ', $date);
		$splittedFormattedDate = explode(' ', BaseModel::parseDate($date));
		$time = implode(' ', array_slice($splittedFormattedDate, 2, 2));
		$timeAddition = $withTime ? ", $time" : '';

		if ($splittedDate[0] === $today)
		{
			return 'сегодня' . $timeAddition;
		}
		elseif ($splittedDate[0] === $yesterday)
		{
			return 'вчера' . $timeAddition;
		}
		else
		{
			$formattedDate = implode(' ', array_slice($splittedFormattedDate, 0, 2));
			return $formattedDate . $timeAddition;
		}
	}

	/**
	 * парсинг параметров из тела запроса
	 * @param array $keys
	 * @return array
	 */
	protected function parseBody ($keys)
	{
		$data = $this -> request -> getJsonRawBody(true);
		$result = [];
		for ($i = 0, $max = sizeof($keys); $i < $max; $i++)
		{
			$result[] = isset($data[$keys[$i]]) ? $data[$keys[$i]] : null;
		}
		return $result;
	}

	/**
	 * возвращет ошибку на ajax запрос
	 * @param string $text
	 * @param int $code
	 */
	protected function returnErrorResponse ($text, $code = 0)
	{
		$this -> response -> setJsonContent(['data' => [], 'success' => false, 'message' => $text, 'code' => $code]);
		$this -> response -> send();
		die();
	}

	protected function rus2translit ($string)
	{
		$converter = [
			'а' => 'a',   'б' => 'b',   'в' => 'v',
			'г' => 'g',   'д' => 'd',   'е' => 'e',
			'ё' => 'e',   'ж' => 'zh',  'з' => 'z',
			'и' => 'i',   'й' => 'y',   'к' => 'k',
			'л' => 'l',   'м' => 'm',   'н' => 'n',
			'о' => 'o',   'п' => 'p',   'р' => 'r',
			'с' => 's',   'т' => 't',   'у' => 'u',
			'ф' => 'f',   'х' => 'h',   'ц' => 'c',
			'ч' => 'ch',  'ш' => 'sh',  'щ' => 'sch',
			'ь' => '\'',  'ы' => 'y',   'ъ' => '\'',
			'э' => 'e',   'ю' => 'yu',  'я' => 'ya'
		];
		return strtr($string, $converter);
	}

	protected function translit ($str)
	{
		$str = mb_strtolower($str, 'utf-8');
		$str = $this -> rus2translit($str);
		$str = preg_replace('~[^-a-z0-9_]+~u', '-', $str);
		$str = trim($str, "-");
		return $str;
	}
}