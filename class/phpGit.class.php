<?php

namespace webObserver;

class PhpGit{

	public $isGitNanaged = false;

    public $branchName;
    public $branch;
    public $hash;
    public $dateLastChange;

    private $folder;
    private $gitBasePath;

    private $errors;


    function __construct($folder)
    {
		$this->isGitNanaged = $this->init($folder);
    }

	public function init($folder){

		 if (!$this->useGit()){
			 return false;
		 }

        $this->gitBasePath = $folder.'/.git'; // e.g in laravel: base_path().'/.git';
        $this->folder = $folder;
		$this->loadGitInfos();
        return true;
	}


	public function status(){
		if (!$this->useGit()){
			return false;
		}

		$this->resetPath();

		$cmd = 'cd ' . $this->folder . ' && git status';
		return shell_exec($cmd);
	}



	public function loadGitInfos(){

		if (!$this->useGit()){
			return false;
		}

		$gitStr = file_get_contents($this->gitBasePath.'/HEAD');
		$this->branchName = rtrim(preg_replace("/(.*?\/){2}/", '', $gitStr));
		$this->branch = $this->gitBasePath.'/refs/heads/'.$this->branchName;
//        $this->hash   = file_get_contents($this->branch);
		$this->dateLastChange   = filemtime($this->branch);

		return true;
	}

    /**
     * Place le shell dans le dossier git correspondant
     * @return void
     */
    private function resetPath(){
        // Place shell in curent dir
        chdir($this->folder);
    }

    /**
     * Return true if use submodule
     * @return bool
     */
    public function useSubModules(){
        return file_exists($this->folder.'/.gitmodules');
    }

    /**
     * update submodules
     * @return false|int false on fail , 0 on idle 1 on updated
     */
    public function updateSubModules(){

        if(!$this->useSubModules()){
            return false;
        }

        $this->resetPath();

        shell_exec('git submodule update');
        shell_exec('git submodule update --recursive --remote');

        $gitStatus = shell_exec('git status --porcelain');
        if(strlen($gitStatus)>0){
            shell_exec("git commit -a -m 'Update submodules from remote'");
            shell_exec("git push");
            return 1;
        }

        return 0;
    }


    /**
     * @return bool
     */
    public function useGit(){

		if (!is_dir (  $this->folder ) ){
			$this->setError('Folder '. $this->folder.' does not exist');
			return false;
		}

        if (!is_dir (  $this->folder .'/.git') ){
			$this->setError('Folder '.$this->folder .'/.git'.' does not exist');
            return false;
        }

        return true;
    }

    /**
     *
     * @param timestamp $fromDate
     * @return false|string|null
     */
    public function getLastLog($fromDate){
        $this->resetPath();
        return shell_exec('git log --pretty=format:"%s" --since='.escapeshellarg(date('Y-m-d', $fromDate)));
    }


    /**
     * get submodules lasts logs strings
     * @param timestamp $fromDate
     * @return string log
     */
    public function getLastSubModulesLog($fromDate = 0){

        if(!$this->useSubModules()){
            return '';
        }

        if(empty($fromDate)){
            $fromDate = time();
        }

        $this->resetPath();
        return shell_exec('git submodule foreach git log --pretty=format:"%s" --since='.escapeshellarg(date('Y-m-d', $fromDate)));
    }

    /**
     * @param $msg
     * @return void
     */
    function setError($msg){
        if (!empty($msg)){
            $this->errors[] = $msg;
        }
    }


    /**
     * @return false|mixed|string
     */
    public function getLastError(){
        if (!empty($this->errors)){
            return end($this->errors);
        }
        return '';
    }

    public function getErrors(){
        return $this->errors;
    }
}
