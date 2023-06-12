<?php


class webObserverCron {

	public $output;


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
