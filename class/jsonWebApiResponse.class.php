<?php

namespace webObserver;
class JsonWebApiResponse
{

	/**
	 * the call status to determine if success or fail
	 *
	 * @var int $result
	 */
	public $result = 0;

	/**
	 * data to return to call can be all type you want
	 *
	 * @var mixed
	 */
	public $data;

	/**
	 * debug data
	 *
	 * @var mixed
	 */
	public $debug;

	/**
	 * returned message used usually as set event message
	 *
	 * @var string $msg
	 */
	public $msg = '';



	public function __construct()
	{

	}

	/**
	 * return json encoded of object
	 *
	 * @return string JSON
	 */
	public function getJsonResponse()
	{
		$jsonResponse = new \stdClass();
		$jsonResponse->result = $this->result;
		$jsonResponse->msg = $this->msg;
		$jsonResponse->data = $this->data;
		$jsonResponse->debug = $this->debug;

		return json_encode($jsonResponse, JSON_PRETTY_PRINT);
	}

	/**
	 * @param $string
	 * @return bool
	 */
	public static function isJson($string): bool
	{
		json_decode($string);
		return json_last_error() === JSON_ERROR_NONE;
	}

	/**
	 * return json encoded of object
	 *
	 * @return string JSON
	 */
	public function parseJsonResponse($response)
	{
		if(!is_object($response)){
			if(!self::isJson($response)){
				return false;
			}
			$obj = json_decode($response);
		}else{
			$obj = $response;
		}

		if(isset($obj->result)){ $this->result = intval($obj->result); }
		if(isset($obj->msg)){ $this->msg = strval($obj->msg); }
		if(isset($obj->data)){ $this->data = $obj->data; }
		if(isset($obj->debug)){ $this->debug = $obj->debug; }

		return true;
	}


	public function getJsonData($url){
		$response = false;
		$res = @file_get_contents($url);
		$this->http_response_header = $http_response_header;
		$this->TResponseHeader = self::parseHeaders($http_response_header);
		if($res !== false){
			$pos = strpos($res, '{');
			if($pos > 0){
				// cela signifie qu'il y a une erreur ou que la sortie n'est pas propre
				$res = substr($res, $pos);
			}

			$response = json_decode($res);
		}

		return $response;
	}
}
