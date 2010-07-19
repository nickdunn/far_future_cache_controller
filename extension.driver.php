<?php

Class Extension_Far_Future_Cache_Controller extends Extension{
	
	public function about() {
		return array('name' => 'Far Futures Cache Controller',
					 'version' => '0.1',
					 'release-date' => '2010-07-19',
					 'author' => array('name' => 'Nick Dunn',
									   'website' => 'http://nick-dunn.co.uk',
									   'email' => ''),
						'description'   => 'Expire browser far-future headers when deploying a new revision from git'
			 		);
	}

	public function install() {
		
		$htaccess = @file_get_contents(DOCROOT . '/.htaccess');
		if($htaccess === false) return false;

		## Cannot use $n in a preg_replace replacement string, so using a token instead
		$token = md5(time());

		$rule = "
	### FAR-FUTURES CACHEBUSTER	
	RewriteRule ^workspace\/([0-9a-f]{5,40})\/(.*)$ workspace/{$token}\n\n";
	
		$htaccess = self::removeRewriteRules($htaccess);
	
		if(preg_match('/### FAR-FUTURES CACHE CONTROLLER/', $htaccess)){
			$htaccess = preg_replace('/### FAR-FUTURES CACHE CONTROLLER/', $rule, $htaccess);
		} else{
			$htaccess = preg_replace('/RewriteRule .\* - \[S=14\]\s*/i', "RewriteRule .* - [S=14]\n{$rule}\t", $htaccess);
		}

		## Replace the token with the real value
		$htaccess = str_replace($token, '$2', $htaccess);

		return @file_put_contents(DOCROOT . '/.htaccess', $htaccess);
	}

    public function uninstall() {
		
		$htaccess = @file_get_contents(DOCROOT . '/.htaccess');		
		if($htaccess === false) return false;
		
		$htaccess = self::removeRewriteRules($htaccess);
		$htaccess = preg_replace('/### FAR-FUTURES CACHE CONTROLLER/', NULL, $htaccess);
		
		return @file_put_contents(DOCROOT . '/.htaccess', $htaccess);	
		
    }

	private static function removeRewriteRules($string){
		return preg_replace('/RewriteRule \^workspace[^\r\n]+[\r\n]?/i', NULL, $string);	
	}

	
	public function getSubscribedDelegates() {
		return array(
			array(
				'page'		=> '/frontend/',
				'delegate'	=> 'FrontendParamsResolve',
				'callback'	=> 'add_param'
			),
		);
	}
	
	public function add_param($context) {
		
		$git = DOCROOT . '/.git/FETCH_HEAD';
		
		if (file_exists($git)) {
			$git_head = preg_match('/([0-9a-f]{5,40})/', file_get_contents($git), $matches);
			$rev = $matches[0];
			$context['params']['git-head'] = $rev;
		}
		
	}

}