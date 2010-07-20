<?php

Class Extension_Far_Future_Cache_Controller extends Extension{
	
	private $allowed_extensions = array('css', 'js', 'gif', 'jpg', 'png', 'swf');
	
	public function about() {
		return array('name' => 'Far Futures Cache Controller',
					 'version' => '0.1',
					 'release-date' => '2010-07-19',
					 'author' => array('name' => 'Nick Dunn',
									   'website' => 'http://nick-dunn.co.uk',
									   'email' => ''),
						'description'   => 'Expire browser far-future headers after deploying your website.'
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
		$static = MANIFEST . '/cache_controller';
		
		if (file_exists($git)) {
			
			$git_head = preg_match('/([0-9a-f]{5,40})/', file_get_contents($git), $matches);
			$rev = $matches[0];
			$context['params']['cache-controller'] = $rev;
		
		} elseif (file_exists($static)) {

			$context['params']['cache-controller'] = sha1(file_get_contents($static));
			
		} else {
			
			$last_modified = $this->get_last_modified(WORKSPACE);
			$context['params']['cache-controller'] = sha1($last_modified);
			
		}
		
	}

	function get_last_modified($path) {

		if (!file_exists($path)) return false;

		$extension = end(explode(".", $path));
		
		if (is_file($path) && in_array($extension, $this->allowed_extensions)) return filemtime($path);

		$last_modified = 0;

		foreach (glob($path . '/*') as $file) {
			if ($this->get_last_modified($file) > $last_modified) $last_modified = $this->get_last_modified($file);
		}
		
		return $last_modified;
	}

}