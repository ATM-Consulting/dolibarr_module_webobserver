<?php

require_once __DIR__ . '/../class/webobserver.class.php';
require_once __DIR__ . '/../class/jsonResponse.class.php';

class WebObserverCron {

	public $output;

	function __construct()
	{
		global $langs, $conf;

		if(empty($langs) || get_class($langs) !== 'Translate'){
			$langs = new Translate('', $conf);
		}

		$langs->setDefaultLang(!empty($conf->global->MAIN_LANG_DEFAULT)?$conf->global->MAIN_LANG_DEFAULT:'en_US');
		$langs->loadLangs(array('main', 'admin', 'cron', 'dict'));
		if(is_callable(array($langs, 'reload'))){
			$langs->reload('webobserver@webobserver');
		}else{
			$langs->load('webobserver@webobserver');
		}
	}

	/**
	 * Update instance data on linked wehbost
	 * @return int|string Errors
	 */
	public function updateInstanceData()
	{
		global $conf, $langs;

		$webObserver  = new WebObserver();

		$webObserver->securityCheck();

		$jsonResponse = new webObserver\JsonResponse();

		$instanceData = $webObserver->getInstanceData();
		$webHostUrl   = $conf->global->WEBOBSERVER_WEBHOST_URL;

		if (dol_strlen($webHostUrl) > 0 && filter_var($webHostUrl, FILTER_VALIDATE_URL)) {
			if (is_object($instanceData) && !empty($instanceData)) {

				$jsonResponse->result = 1;
				$jsonResponse->msg = 1;
				$jsonResponse->data = json_encode($instanceData);

				$instanceDataJson = $jsonResponse->getJsonResponse();

				$ch = curl_init( $webHostUrl );
				curl_setopt( $ch, CURLOPT_POST, 1);
				curl_setopt( $ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
				curl_setopt( $ch, CURLOPT_POSTFIELDS, $instanceDataJson);
				curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true);
				$webHostResponse = curl_exec( $ch );
				curl_close($ch);

				$this->output = $webHostResponse;

				return 0;
			} else {
				return $langs->transnoentities('EmptyInstanceData');
			}
		} else {
			return $langs->transnoentities('InvalidWebHostUrl');
		}



	public function addlog($message){
		if(empty($message)) return;

		if(!empty($this->output)){$this->output.='<br/>';}
		$this->output.= $message;
	}


	/**
	 * @param int $verifOffset time in second
	 * @param int $socket_timeout to prevent execution time breaks in second
	 * @return int 0 on ok (cron call) | 0 > on error
	 */
	public function instanceMonitoring($verifOffset = 600, $socket_timeout = 60)
	{
		global $db, $user;

		require_once __DIR__ . '/../class/webobserver.class.php';

		if(!empty($_SERVER['REQUEST_TIME'])){
			// ne sachant pas quand le script des taches cron est lancé il n'est pas impossible que d'autres script ai pris leurs temps.
			$timestart = $_SERVER['REQUEST_TIME']; // dispo PHP 5.4.0
		}else{ $timestart = time(); }

		if(empty($socket_timeout)) $socket_timeout = 60; // la valeur 0 est interdite
		if(empty($verifOffset)) $verifOffset = 600; // strange behavior cron call allways send empty value for first param
		$verifOffset = max($socket_timeout+1, $verifOffset); // pour eviter les doubles executions de cron

		$errors = 0;

		$webObserver = new WebObserver();
		$webObserver->setSocketTimeOut($socket_timeout);




		return $errors;
	}




}
