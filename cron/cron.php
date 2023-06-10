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
		global $conf, $db, $langs;

		$webObserver = new WebObserver($db);

		$instanceData = $webObserver->getInstanceData();
		$webHostUrl   = $conf->global->WEBOBSERVER_WEBHOST_URL;

		if (dol_strlen($webHostUrl) > 0) {
			if (is_object($instanceData) && !empty($instanceData)) {

				$instanceDataJson = json_encode($instanceData);

				$ch = curl_init( $webHostUrl );
				curl_setopt( $ch, CURLOPT_POST, 1);
				curl_setopt( $ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
				curl_setopt( $ch, CURLOPT_POSTFIELDS, ['json_data' => $instanceDataJson]);
				curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1);
				curl_exec( $ch );
				curl_close($ch);

				return 0;
			} else {
				return $langs->transnoentities('EmptyInstanceData');
			}
		} else {
			return $langs->transnoentities('InvalidWebHostUrl');
		}
	}
}
