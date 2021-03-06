<?php
/**
 * Class containing several useful methods
 *
 * @filesource
 */

/**
 *
 * Class containing several useful methods
 *
 * @package Atk14
 * @subpackage Core
 *
 */
class Atk14Utils{

	/**
	 * Determines environment.
	 *
	 * Determines environment and depending on that defines some constants.
	 *
	 * It first checks whether some of constants TEST,DEVELOPMENT or PRODUCTION is defined.
	 *
	 * If none of them is defined it checks the system environment variable ATK14_ENV and when found it defines constants TEST,DEVELOPMENT and PRODUCTION depending on the value of ATK14_ENV
	 *
	 * When even ATK14_ENV is not defined it defines these constants depending on REMOTE_ADDRESS.
	 * For localhost or addresses in 192.168.0.0 and 172.16.0.0 or no IP(script is run from console) it defines environment as DEVELOPMENT, otherwise PRODUCTION.
	 *
	 */
	static function DetermineEnvironment(){
		global $HTTP_REQUEST;
		// Determining environment constants (i.e. DEVELOPMENT, TEST, PRODUCTION).

		// An existing constant has the strongest importance.
		if(defined("TEST") && TEST){
			define("DEVELOPMENT",false);
			define("PRODUCTION",false);
		}elseif(defined("DEVELOPMENT") && DEVELOPMENT){
			define("TEST",false);
			define("PRODUCTION",false);	
		}elseif(defined("PRODUCTION") && PRODUCTION){
			define("DEVELOPMENT",false);
			define("TEST",false);

		// No environment constant was defined? Check out the ATK14_ENV environment variable...
		}elseif(($atk14_env = strtoupper(getenv("ATK14_ENV")))=="TEST"){
			define("TEST",true);
			define("DEVELOPMENT",false);
			define("PRODUCTION",false);
		}elseif($atk14_env=="DEVELOPMENT"){
			define("TEST",false);
			define("DEVELOPMENT",true);
			define("PRODUCTION",false);
		}elseif($atk14_env=="PRODUCTION"){
			define("TEST",false);
			define("DEVELOPMENT",false);
			define("PRODUCTION",true);

		// At last there is an auto detection.
		// If there is an internal remote address or there is no remote address at all (i.e. a script is running from a console),
		// environment is treat as DEVELOPMENT.
		}else{
			define("DEVELOPMENT",in_array($HTTP_REQUEST->getRemoteAddr(),array("127.0.0.1","::1")) || preg_match("/^(192\\.168\\.|10\\.|172\\.16\\.)/",$HTTP_REQUEST->getRemoteAddr()) || $HTTP_REQUEST->getRemoteAddr()=="");
			define("PRODUCTION",!DEVELOPMENT);
			define("TEST",false);
		}
	}

	/**
	 * Load all config files.
	 *
	 * Loads all config files (*.inc) in directory $ATK14_GLOBAL->getApplicationPath()/../config/
	 * Also tries to use formerly prefered directory $ATK14_GLOBAL->getApplicationPath()/conf
	 *
	 */
	static function LoadConfig(){
		global $ATK14_GLOBAL;
		if(!file_exists($path = $ATK14_GLOBAL->getApplicationPath()."../config/")){
			$path = $ATK14_GLOBAL->getApplicationPath()."conf/";
		}

		if(file_exists("$path/routers/")){
			class_autoload("$path/routers/");
		}

		$dir = opendir($path);
		while($file = readdir($dir)){
			if(preg_match('/^(local_|)settings\.(inc|php)$/',$file)){ continue; } // this is ugly hack :( i need to delay loading of ./config/settings.php
			if(preg_match('/\.(inc|php)$/',$file) && is_file($path.$file)){
				require_once($path.$file);
			}
		}
		closedir($dir);
	}

	/**
	 * Loads resources for a controller and also the controller.
	 *
	 * Load HelpController
	 * 	Atk14Utils::LoadControllers("help_controller");
	 *
	 * This code loads all resources needed by HelpController and in the end loads the HelpController
	 *
	 * @param string $controller_name name of controller
	 *
	 */
	static function LoadControllers($controller_name){
		global $ATK14_GLOBAL;

		$namespace = $ATK14_GLOBAL->getValue("namespace");

		$_requires = array("$namespace/application.php");
		if($namespace!=""){
			$_requires[] = "$namespace/$namespace.php";
		}
		foreach($_requires as $_f_){
			if($_f_ = atk14_find_file(ATK14_DOCUMENT_ROOT."/app/controllers/$_f_")){
				require_once($_f_);
			}
		}

		Atk14Require::Controller("_*");
		Atk14Require::Controller($controller_name);

		// loading base form class
		foreach(array(
			"$namespace/application_form.php",
			"application_form.php",
		) as $_f_){
			if($_f_ = atk14_find_file(ATK14_DOCUMENT_ROOT."/app/forms/".$_f_)){
				require_once($_f_);
				break;
			}
		}

		// Form:legacy name for base form class
		foreach(array(
			ATK14_DOCUMENT_ROOT."/app/forms/$namespace/form.php",
			ATK14_DOCUMENT_ROOT."/app/forms/form.php",
		) as $_f_){
			if($_f_ = atk14_find_file($_f_)){
				require_once($_f_);
				break;
			}
		}


	}

	/**
	 * Escapes string for use in javascript.
	 *
	 * @param string $content string to be escaped
	 * @return string escaped string
	 */
	static function EscapeForJavascript($content){
		return EasyReplace($content,array("\\" => "\\\\", "\n" => "\\n","\r" => "\\r","\t" => "\\t","\"" => "\\\"", "<script" => '<scr" + "ipt', "</script>" => '</scr" + "ipt>'));
	}

	/**
	 * Build a link for Smarty helpers.
	 *
	 * !Changes $params (clears values)
	 *
	 *		$params["_connector"]
	 *		$params["_anchor"]
	 * 		$params["_with_hostname"]
	 *		$params["_ssl"]
	 *
	 * When building a link parameters beginning with underscore are used as parameters of the &lt;a&gt; tag.
	 *
	 *
	 * @param array $params
	 * - action
	 * - controller
	 * - lang
	 * @param Smarty $smarty Smarty specific
	 * @param array $options
	 * - connector - character joining parameters in url
	 * - anchor - 
	 * - with_hostname - boolean - build url even with hostname
	 * - ssl
	 */
	static function BuildLink(&$params,&$smarty,$options = array()){
		$options = array_merge(array(
			"connector" => "&",
			"anchor" => null,
			"with_hostname" => false,
			"ssl" => null,
		),$options);
		reset($options);
		while(list($_key,$_value) = each($options)){
			if(isset($params["_$_key"])){
				$options[$_key] = $params["_$_key"];
			}
			unset($params["_$_key"]);
		}

		$_params = $params;

		reset($_params);
		while(list($key,) = each($_params)){
			if(preg_match("/^_/",$key)){ unset($_params[$key]); }
		}

		if(!isset($_params["action"]) && !isset($_params["controller"])){ $_params["action"] = $smarty->getTemplateVars("action"); }
		if(!isset($_params["controller"])){ $_params["controller"] = $smarty->getTemplateVars("controller"); }
		if(!isset($_params["action"])){ $_params["action"] = "index"; }
		if(!isset($_params["lang"])){ $_params["lang"] = $smarty->getTemplateVars("lang"); }

		Atk14Utils::_CorrectActionForUrl($params);

		return Atk14Url::BuildLink($_params,$options);
	}

	/**
	 * Extracts attributes from $params beginning with underscore.
	 *
	 * In this example $params will contain array("id" => "20"), $attrs will contain array("class" => "red","id" => "red_link").
	 * 	$params = array("id" => "20", "_class" => "red", "_id" => "red_link");
	 * 	$attrs = Atk14Utils::ExtractAttributes($params);
	 *
	 * or
	 * 	$attrs = array("data-message" => "Hello guys!");
	 * 	Atk14Utils::ExtractAttributes($params,$attrs);
	 * the attribute data-message will be preserved
	 *
	 *
	 *
	 * @param array $params
	 * @param array $attributes
	 * @return array
	 */
	static function ExtractAttributes(&$params,&$attributes = array()){
		reset($params);
		while(list($_key,$_value) = each($params)){
			if(preg_match("/^_(.+)/",$_key,$matches)){
				$_attr = $matches[1];
				$_attr = str_replace("___","-",$_attr); // this is a hack: "data___type" -> "data-type" (see atk14_smarty_prefilter() function)
				$attributes[$_attr] = $_value;
				unset($params[$_key]);
			}
		}
		return $attributes;
	}

	/**
	 * Joins attributes to a string.
	 *
	 * Example
	 * 	$attrs -> array("href" => "http://www.link.cz/", "class" => "red");
	 * 	$attrs = Atk14Utils::JoinAttributes($attrs);
	 * 	echo "<a$attrs>text linku</a>"
	 *
	 * @param array $attributes
	 * @return string joined attributes
	 */
	static function JoinAttributes($attributes){
		$out = array();
		foreach($attributes as $key => $value){	
			$out[] = " ".h($key)."=\"".h($value)."\"";
		}
		return join("",$out);
	}

	/**
	 * Returns instance of Smarty object.
	 *
	 *
	 * @param string $template_dir
	 * @param array $options
	 * - <b>controller_name</b>
	 * - namespace
	 * - compile_id_salt
	 *
	 * @return Smarty instance of Smarty
	 */
	static function GetSmarty($template_dir = null, $options = array()){
		global $ATK14_GLOBAL;

		$options = array_merge(array(
			"controller_name" => "",
			"namespace" => "",
			"compile_id_salt" => "",
		),$options);

		$PATH_SMARTY = "/tmp/smarty/";
		if(defined("TEMP")){ $PATH_SMARTY = TEMP."/smarty/"; }
		if(defined("PATH_SMARTY")){ $PATH_SMARTY = PATH_SMARTY; }

		if(function_exists("atk14_get_smarty")){

			$smarty = atk14_get_smarty($template_dir);

		}else{

			$smarty = new Atk14Smarty();

			if(!isset($template_dir)){ $template_dir = "./templates/"; }

			if(is_string($template_dir) && !file_exists($template_dir) && file_exists("./templates/$template_dir")){
				$template_dir = "./templates/$template_dir";
			}

			$_template_dir = array();
			if(is_array($template_dir)){
				$_template_dir = $template_dir;
			}else{
				$_template_dir[] = $template_dir;
			}

			$userid = posix_getuid();

			$smarty->setTemplateDir($_template_dir);
			$smarty->setCompileDir($_compile_dir = "$PATH_SMARTY/$userid/templates_c/"); // the uid of the user owning the current process is involved in the compile_dir, thus some file permission issues are solved
			$smarty->setConfigDir($PATH_SMARTY."/config/");
			$smarty->setCacheDir($PATH_SMARTY."/cache/");

			if(!file_exists($_compile_dir)){ Files::Mkdir($_compile_dir); }

			if(!Files::IsReadableAndWritable($_compile_dir)){
				//die("$smarty->compile_dir is not writable!!!");
				// this should by handled by atk14_error_handler()
			}
		}

		// do compile_id zahrneme jmeno controlleru, aby nedochazelo ke kolizim se sablonama z ruznych controlleru, ktere se jmenuji stejne
		$smarty_version_salt = ATK14_USE_SMARTY3 ? "smarty3" : "smarty2"; 
		$smarty->compile_id = $smarty->compile_id."atk14{$options["compile_id_salt"]}_{$smarty_version_salt}_{$options["namespace"]}_{$options["controller_name"]}_";

		$plugins = $smarty->getPluginsDir();
	
		$smarty->setPluginsDir(array_merge(array(
			$ATK14_GLOBAL->getApplicationPath()."helpers/$options[namespace]/$options[controller_name]/",
			$ATK14_GLOBAL->getApplicationPath()."helpers/$options[namespace]/",
			$ATK14_GLOBAL->getApplicationPath()."helpers/",
			dirname(__FILE__)."/helpers/",
			$PATH_SMARTY."/plugins/",
		),$plugins));

		$smarty->registerFilter('pre','atk14_smarty_prefilter');

		if(defined("ATK14_SMARTY_DEFAULT_MODIFIER") && ATK14_SMARTY_DEFAULT_MODIFIER){
			$smarty->default_modifiers[] = ATK14_SMARTY_DEFAULT_MODIFIER;
		}

		return $smarty;
	}

	/**
	 * Writes a message to error log and to the output defined by HTTPResponse
	 *
	 * Example
	 * 	Atk14Utils::ErrorLog("chybi sablona _item.tpl",$http_response);
	 *
	 * @param string $message
	 * @param HTTPResponse $response
	 */
	static function ErrorLog($message,&$response){
		$message = "AK14 error: $message";
		error_log($message);
		$response->setStatusCode(500);
		if(defined("DEVELOPMENT") && DEVELOPMENT){
			$response->write($message);
		}else{
			$response->write("AK14 error");
		}
	}

	/**
	 * Tests if controller produced any output.
	 *
	 * Is used for testing in _before_filters
	 *
	 * @param Atk14Controller $controller
	 * @return boolean true - output produced, false - nothing produced
	 */
	static function ResponseProduced(&$controller){
		return !(
			strlen($controller->response->getLocation())==0 &&
			!$controller->action_executed &&
			$controller->response->buffer->getLength()==0 &&
			$controller->response->getStatusCode()==200
		);
	}

	/**
	 * Joins arrays
	 *
	 * Result of this will be array("a","b","c","d")
	 * 	Atk14Utils::JoinArrays(array("a","b"),array("c"),array("d"));
	 *
	 * @return array joined arrays
	 */
	static function JoinArrays(){
		$out = array();
		$arguments = func_get_args();
		foreach($arguments as $arg){
			if(!isset($arg)){ continue; }
			if(!is_array($arg)){ $arg = array($arg); }
			foreach($arg as $item){
				$out[] = $item;
			}
		}
		return $out;
	}

	/**
	 * Normalizes a URI, removes unnecessary path elements.
	 *
	 * '/public/stylesheets/../dist/css/app.css?1384766775' => /public/dist/css/app.css?1384766775
	 * 	echo Atk14Utils::NormalizeUri('/public/stylesheets/../dist/css/app.css?1384766775');
	 *
	 * @param string $uri uri to normalize
	 * @return string normalized uri
	 */
	static function NormalizeUri($uri){
		$ar = explode('?',$uri);
		$uri = array_shift($ar);

		$uri = preg_replace('#/{2,}#','/',$uri);

		do{
			$orig = $uri;
			$uri = preg_replace('#/[^/]+/\.\./#','/',$uri); // /public/stylesheets/../dist/style.css -> /public/dist/stylesheets.css
		}while($orig!=$uri);

		do{
			$orig = $uri;
			$uri = preg_replace('#/\./#','/',$uri); // /public/./dist/style.css -> /public/dist/stylesheets.css
		}while($orig!=$uri);


		array_unshift($ar,$uri);
		return join('?',$ar);
	}

	/**
	 * @ignore
	 */ 
	static function _CorrectActionForUrl(&$params){
		// shortcut to define both controller and action through the action only
		// action="books/detail" -> controller="books", action="detail"
		if(preg_match('/(.+)\/(.+)/',$params["action"],$matches)){
			$params["controller"] = $matches[1];
			$params["action"] = $matches[2];
		}
	}
}

/**
 * Atk14s' variant of require_once
 *
 * When some/path/file.php is given,
 * it loads some/path/file.php or some/path/file.inc
 *
 * @param string $file
 */ 
function atk14_require_once($file){
	($_file = atk14_find_file($file)) || ($_file = $file);
	return require_once($_file);
}

/**
 * When some/path/file.php is given,
 * finds out whether there is some/path/file.php or some/path/file.inc
 *
 * @param string $file
 */
function atk14_find_file($file){
	preg_match('/^(.*\.)(inc|php)$/',$file,$matches);
	$fs = array();
	$fs[] = $file;
	$fs[] = $matches[1]."inc";
	$fs[] = $matches[1]."php";
	foreach($fs as $file){
		if(file_exists($file)){ return $file; }
	}
}

/**
 * Atk14s' way of including required file.
 *
 * @param string $file
 */
function atk14_require_once_if_exists($file){
	if($file = atk14_find_file($file)){
		return atk14_require_once($file);
	}
	return false;
}
