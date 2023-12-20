<?php

require_once __DIR__ . '/../class/webobserver.class.php';

class WebObserverCron {

	public $output;

	function __construct()
	{
		global $langs, $conf;

		if(empty($langs) || get_class($langs) !== 'Translate'){
			$langs = new Translate('', $conf);
		}

		$langs->setDefaultLang(getDolGlobalString('MAIN_LANG_DEFAULT', 'en_US'));
		$langs->loadLangs(array('main', 'admin', 'cron', 'dict'));
		if(is_callable(array($langs, 'reload'))){
			$langs->reload('webobserver@webobserver');
		}else{
			$langs->load('webobserver@webobserver');
		}
	}


	public function addlog($message){
		if(empty($message)) return;

		if(!empty($this->output)){$this->output.='<br/>';}
		$this->output.= $message;
	}


	/**
	 * @param int $socket_timeout to prevent execution time breaks in second
	 * @return int 0 on ok (cron call) | 0 > on error
	 */
	public function sendWebHostInstanceMonitoringPing($socket_timeout = 60)
	{
		global $db, $user;

		require_once __DIR__ . '/../class/webobserver.class.php';

		$errors = 0;

		$webObserver = new WebObserver();
		$webObserver->setSocketTimeOut($socket_timeout);

		$webHostResponse = $webObserver->callWebHost();
		if($webHostResponse){
			$this->output = $webHostResponse;
		}else{
			$errors = 1;
		}

		return $errors;
	}




}
