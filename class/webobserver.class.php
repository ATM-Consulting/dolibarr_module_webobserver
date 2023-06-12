<?php

class WebObserver {



	/**
	 * @var string 		Error string
	 * @see             $errors
	 */
	public $error;

	/**
	 * @var string[]	Array of error strings
	 */
	public $errors = array();

	/**
	 * @var reponse_code  a http_response_header parsed reponse code
	 */
	public $reponse_code;

	/**
	 * @var http_response_header  the last call $http_response_header
	 */

	public $http_response_header;

	/**
	 * @var TResponseHeader  the last call $http_response_header parsed <- for most common usage (see self::parseHeaders() function)
	 */
	public $TResponseHeader;


	public static function getInstanceData(){

		global $conf, $dolibarr_main_db_host, $dolibarr_main_db_name, $dolibarr_main_db_user, $dolibarr_main_db_type;

		$instance = new stdClass;

		$instance->apiname = 'serverobserver';
		$instance->apiversion = '1.0';

		// Dolibarr main informations
		$instance->dolibarr = new stdClass;
		$instance->dolibarr->version = DOL_VERSION;
		$instance->dolibarr->version1 = $conf->global->MAIN_VERSION_LAST_INSTALL;
		$instance->dolibarr->theme = $conf->theme;

		$instance->dolibarr->path=new stdClass;
		$instance->dolibarr->path->http = dol_buildpath('/',2);

		$instance->dolibarr->data = new stdClass;
		$instance->dolibarr->data->path = DOL_DATA_ROOT;
		$instance->dolibarr->data->size = self::getDirSize($instance->dolibarr->data->path, DOL_DATA_ROOT);

		$instance->dolibarr->htdocs=new stdClass;
		$instance->dolibarr->htdocs->path = DOL_DOCUMENT_ROOT;
		$instance->dolibarr->htdocs->size = self::getDirSize($instance->dolibarr->htdocs->path, DOL_DATA_ROOT);

		$instance->dolibarr->repertoire_client=new stdClass;
		$instance->dolibarr->repertoire_client->path = dirname(dirname(DOL_DOCUMENT_ROOT));
		$instance->dolibarr->repertoire_client->size = self::getDirSize($instance->dolibarr->repertoire_client->path, DOL_DATA_ROOT);

		// Informations about Dolibarr database
		$instance->db=new stdClass;
		$instance->db->host = $dolibarr_main_db_host;
		$instance->db->name = $dolibarr_main_db_name;
		$instance->db->user = $dolibarr_main_db_user;
		$instance->db->type = $dolibarr_main_db_type;

		// Informations about users in Dolibarr
		$instance->user=new stdClass;
		$instance->user->all = self::nb_user();
		$instance->user->active = self::nb_user(true);
		$instance->user->date_last_login = self::last_login() ;

		// Security informations
		$instance->security=new stdClass;
		$instance->security->database_pwd_encrypted = $conf->global->DATABASE_PWD_ENCRYPTED;
		$instance->security->main_features_level = $conf->global->MAIN_FEATURES_LEVEL;
		$instance->security->install_lock = file_exists(DOL_DATA_ROOT . '/install.lock');

		// Informations about module activated on the instance
		$instance->module = new stdClass;

		// fix une fonction de _module_active n'existe pas avant la 4.0
		//if (version_compare(DOL_VERSION, '4.0.0') > 0)
		$instance->module = self::module_active();

		return $instance;
	}

	/**
	 * renvoi le json des données de l'instance
	 * @return false|string
	 */
	public static function getInstanceJson(){
		return  json_encode(self::getInstanceData());
	}


	/**
	 * Check security parameters
	 * Check hash and time parameters
	 */
	public static function securityCheck() {
		global $conf;

		if(empty($conf->global->WEBOBSERVER_TOKEN)){
			return exit('Invalid token configuration');
		}

		$token = $conf->global->WEBOBSERVER_TOKEN;

		// Vérification paramètres
		if(!isset($_GET['hash'])) exit('Missing parameter');
		if(!isset($_GET['time'])) exit('Missing parameter');

		// Vérification token
		$hashToCheck = $_GET['hash'];
		$tokenTime = $_GET['time'];
		$now = time();
		$hash = md5($token . $tokenTime);
		if($hash != $hashToCheck) exit('Invalid hash');
		if($tokenTime < $now - 180) exit('Invalid hash');
		if($tokenTime > $now + 180) exit('Invalid hash');
	}



	/**
	 * Get size of a directory on the server, in bytes
	 * @param $dir	Absolute path of the directory to scan
	 * @return int	Size of the diectory or -1 if $dir is not a directory
	 */
	public static function getDirSize($dir) {
		if(is_dir($dir)) {
			$cmd = 'du -sb ' . $dir;
			$res = shell_exec($cmd);

			return (int)$res;
		}

		return -1;
	}

	/**
	 * Get informations about disk space
	 * @param $dir		Directory to scan
	 * @return stdClass	Data about total space, used, left and percentages
	 */
	public static function getSystemSize($dir=__DIR__) {
		$res = new stdClass();
		$res->bytes_total = disk_total_space($dir);
		$res->bytes_left = disk_free_space($dir);
		$res->bytes_used = $res->bytes_total - $res->bytes_left;
		$res->percent_used = round($res->bytes_used * 100 / $res->bytes_total);
		$res->percent_left = 100 - $res->percent_used;

		return $res;
	}


	/**
	 * Specific functions to get informations about Dolibarr (Modules, Users, ...)
	*/
	public static function module_active() {
		include_once DOL_DOCUMENT_ROOT . '/core/lib/functions2.lib.php';

		global $db, $conf;
		$modNameLoaded = array();
		$modulesdir = dolGetModulesDirs();

		foreach ($modulesdir as $dir)
		{
			$handle = @opendir($dir);
			if (is_resource($handle))
			{
				while (($file = readdir($handle)) !== false)
				{
					if (is_readable($dir.$file) && substr($file, 0, 3) == 'mod' && substr($file, dol_strlen($file) - 10) == '.class.php')
					{
						$modName = substr($file, 0, dol_strlen($file) - 10);

						if ($modName)
						{
							try
							{
								$res = include_once $dir.$file; // A class already exists in a different file will send a non catchable fatal error.
								if (class_exists($modName))
								{
									try {
										$objMod = new $modName($db);

										$pubname = is_callable(array( $objMod, 'getPublisher')) ? $objMod->getPublisher() : $objMod->editor_name;
										$puburl = is_callable(array( $objMod, 'getPublisherUrl')) ? $objMod->getPublisherUrl() : $objMod->editor_url;

										$modNameLoaded[$modName] = new stdClass();
										$modNameLoaded[$modName]->dir = $dir;
										$modNameLoaded[$modName]->name = $objMod->name;
										$modNameLoaded[$modName]->numero = $objMod->numero;
										$modNameLoaded[$modName]->version = $objMod->version;
										$modNameLoaded[$modName]->source = $objMod->isCoreOrExternalModule();
										$modNameLoaded[$modName]->gitinfos = self::getModuleGitInfos($dir);
										$modNameLoaded[$modName]->editor_name = dol_escape_htmltag($pubname);
										$modNameLoaded[$modName]->editor_url = dol_escape_htmltag($puburl);
										$modNameLoaded[$modName]->active = !empty($conf->global->{$objMod->const_name});
									}
									catch (Exception $e)
									{
										dol_syslog("Failed to load ".$dir.$file." ".$e->getMessage(), LOG_ERR);
									}
								}
								else
								{
									print "Warning bad descriptor file : ".$dir.$file." (Class ".$modName." not found into file)<br>";
								}
							}
							catch (Exception $e)
							{
								dol_syslog("Failed to load ".$dir.$file." ".$e->getMessage(), LOG_ERR);
							}
						}
					}
				}
				closedir($handle);
			}
			else
			{
				dol_syslog("htdocs/admin/modules.php: Failed to open directory ".$dir.". See permission and open_basedir option.", LOG_WARNING);
			}
		}

		return $modNameLoaded;
	}

	/**
	 * @param $dir
	 * @return stdClass
	 */
	public static function getModuleGitInfos($dir) {
		global $doneDir;
		if(isset($doneDir[$dir])) return $doneDir[$dir];

		$cmd = 'cd ' . $dir . ' && git status';
		$status = shell_exec($cmd);
		$cmd = 'cd ' . $dir . ' && git rev-parse --abbrev-ref HEAD';
		$branch = shell_exec($cmd);

		$doneDir[$dir] = new stdClass();
		$doneDir[$dir]->status = $status;
		$doneDir[$dir]->branch = $branch;

		return $doneDir[$dir];
	}

	/**
	 * @return mixed
	 */
	public static function last_login() {
		global $db;

		$sql = "SELECT MAX(datelastlogin) as datelastlogin FROM ".MAIN_DB_PREFIX."user WHERE 1 ";
		$sql.=" AND statut=1 AND rowid>1"; // pas l'admin

		$res = $db->query($sql);

		$obj = $db->fetch_object($res);

		return $obj->datelastlogin;
	}

	public static function nb_user($just_actif = false) {
		global $db;

		$sql = "SELECT count(*) as nb FROM ".MAIN_DB_PREFIX."user WHERE 1 ";

		if($just_actif) {
			$sql.=" AND statut=1 ";
		}

		$res = $db->query($sql);

		$obj = $db->fetch_object($res);

		return (int)$obj->nb;
	}


	/**
	 * @param int $value Durée d'expiration (en secondes) pour les flots basés sur les sockets.
	 */
	public function setSocketTimeOut($value = 0){
		if($value>0){
			$this->default_socket_timeout = intval($value);
		}

		ini_set('default_socket_timeout', $this->default_socket_timeout);
	}


	public function call($useCache = true){
		global $conf;

		// Use cache
		if($useCache && !empty($this->data)){
			return $this->data;
		}


		$instanceId = false;
		$instanceRef = false;

		$url = $this->getWebHostTargetUrl();
		if(!$url){
			$this->setError('Configuration WebHost target URL not set');
			return false;
		}

		if(!empty($conf->global->WEBOBSERVER_INSTANCE_ID)){
			$instanceId = $conf->global->WEBOBSERVER_INSTANCE_ID;
		}

		if(!empty($conf->global->WEBOBSERVER_INSTANCE_REF)){
			$instanceRef = $conf->global->WEBOBSERVER_INSTANCE_REF;
		}

		if(empty($instanceId) && empty($instanceRef)){
			$this->setError('Configuration ID OR REF not set');
			return false;
		}


		if($url!==false){
			$time = time();
			$hash = md5($this->webInstance->api_token . $time);

			$url.= '?action=set-info-instance-dolibarr';
			$url.= '&hash='.$hash.'&time='.$time;

			if(!empty($instanceId)){
				$url.= '&id='.intval($instanceId);
			}

			if(!empty($instanceRef)){
				$url.= '&ref='.urlencode($instanceRef);
			}

			$res = $this->getJsonData($url);

			if(!empty($this->data)) {
				return $this->data;
			}else{
				$this->setError('@file_get_contents fail => '.$url.' : '.$res);
			}
		}else{
			$this->setError('url not valid => '.$url);
		}

		return false;
	}


	public static function http_response_code_msg($code = NULL)
	{
		if ($code !== NULL) {

			switch ($code) {
				case 100:
					$text = 'Continue';
					break;
				case 101:
					$text = 'Switching Protocols';
					break;
				case 200:
					$text = 'OK';
					break;
				case 201:
					$text = 'Created';
					break;
				case 202:
					$text = 'Accepted';
					break;
				case 203:
					$text = 'Non-Authoritative Information';
					break;
				case 204:
					$text = 'No Content';
					break;
				case 205:
					$text = 'Reset Content';
					break;
				case 206:
					$text = 'Partial Content';
					break;
				case 300:
					$text = 'Multiple Choices';
					break;
				case 301:
					$text = 'Moved Permanently';
					break;
				case 302:
					$text = 'Moved Temporarily';
					break;
				case 303:
					$text = 'See Other';
					break;
				case 304:
					$text = 'Not Modified';
					break;
				case 305:
					$text = 'Use Proxy';
					break;
				case 400:
					$text = 'Bad Request';
					break;
				case 401:
					$text = 'Unauthorized';
					break;
				case 402:
					$text = 'Payment Required';
					break;
				case 403:
					$text = 'Forbidden';
					break;
				case 404:
					$text = 'Not Found';
					break;
				case 405:
					$text = 'Method Not Allowed';
					break;
				case 406:
					$text = 'Not Acceptable';
					break;
				case 407:
					$text = 'Proxy Authentication Required';
					break;
				case 408:
					$text = 'Request Time-out';
					break;
				case 409:
					$text = 'Conflict';
					break;
				case 410:
					$text = 'Gone';
					break;
				case 411:
					$text = 'Length Required';
					break;
				case 412:
					$text = 'Precondition Failed';
					break;
				case 413:
					$text = 'Request Entity Too Large';
					break;
				case 414:
					$text = 'Request-URI Too Large';
					break;
				case 415:
					$text = 'Unsupported Media Type';
					break;
				case 500:
					$text = 'Internal Server Error';
					break;
				case 501:
					$text = 'Not Implemented';
					break;
				case 502:
					$text = 'Bad Gateway';
					break;
				case 503:
					$text = 'Service Unavailable';
					break;
				case 504:
					$text = 'Gateway Time-out';
					break;
				case 505:
					$text = 'HTTP Version not supported';
					break;
				default:
					$text = 'Unknown http status code "' . htmlentities($code) . '"';
					break;
			}

			return $text;

		} else {
			return $text = 'Unknown http status code NULL';
		}
	}

	public static function parseHeaders( $headers )
	{
		$head = array();
		if(!is_array($headers)){
			return $head;
		}

		foreach( $headers as $k=>$v )
		{
			$t = explode( ':', $v, 2 );
			if( isset( $t[1] ) )
				$head[ trim($t[0]) ] = trim( $t[1] );
			else
			{
				$head[] = $v;
				if( preg_match( "#HTTP/[0-9\.]+\s+([0-9]+)#",$v, $out ) )
					$head['reponse_code'] = intval($out[1]);
			}
		}
		return $head;
	}

	public function getJsonData($url){
		$this->data = false;
		$res = @file_get_contents($url);
		$this->http_response_header = $http_response_header;
		$this->TResponseHeader = self::parseHeaders($http_response_header);
		if($res !== false){
			$pos = strpos($res, '{');
			if($pos > 0){
				// cela signifie qu'il y a une erreur ou que la sortie n'est pas propre
				$res = substr($res, $pos);
			}

			$this->data = json_decode($res);
		}

		return $this->data;
	}

	/**
	 * @param $url
	 * @return bool
	 */
	public function getContentData($url){
		$this->data = false;
		$res = @file_get_contents($url);
		$this->http_response_header = $http_response_header;
		$this->TResponseHeader = self::parseHeaders($http_response_header);
		if($res !== false){
			$this->data = $res;
			return true;
		}
		else{
			return false;
		}
	}

	/**
	 * Permet gérer les retours d'erreur avec message
	 *
	 * @param string $err
	 */
	public function setError($err){
		if(!empty($err)){
			$this->error = $err;
			$this->errors[] = $this->error;
		}
	}

	/**
	 * @return bool|string $url
	 */
	public function getWebHostTargetUrl(){

		if(empty($conf->global->WEBOBSERVER_WEBHOST_URL)) return false;

		$url = $conf->global->WEBOBSERVER_WEBHOST_URL;

		if(filter_var($url, FILTER_VALIDATE_URL)){
			return $url;
		}
		else{
			return false;
		}
	}
}
