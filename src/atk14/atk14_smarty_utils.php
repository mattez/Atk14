<?php
/**
* In a template in an ATK14 project one can escape curly bracets:
*
* <code>
*		function js_alert(message)\{ alert($message); return false; \}
* </code>
* 
* one can define a data-* attribute in a link
*
* <code>
*		{a_remote action=detail id=$product _class=detail _data-type=json}Detail{/a_remote}
* </code>
*
* Such things are not possible in Smarty template engine.
* 
* This function changes a template source in some ways to make this possible.
* Smarty calls it automatically before a template compilation.
*/
function atk14_smarty_prefilter($tpl_source, &$smarty = null){
	// \{HELLO\} -> {literal}{{/literal}HELLO{literal}}{/literal}
	$tpl_source = strtr($tpl_source,array(
		"\\{" => "{literal}{{/literal}",
		"\\}" => "{literal}}{/literal}",
	));
	
	// {a _data-focus=1}xx{/a} -> {a _data__focus=1}xx{/a}
	$tpl_source = preg_replace("/({(a|a_remote|a_destroy|form|form_remote)\\s+[^}]*\\b_data)-/","\\1___",$tpl_source);

	// {!$title} -> {$title nofilter}
	$tpl_source = preg_replace('/\{!([^}]+)\}/','{\1 nofilter}',$tpl_source);

	return $tpl_source;
}

/**
 * Handles some of Smarty errors.
 */
function atk14_error_handler($errno, $errstr, $errfile, $errline){
	global $HTTP_RESPONSE,$HTTP_REQUEST;

	if(($errno==E_USER_ERROR || $errno==512) && preg_match("/^Smarty/",$errstr)){
		if(DEVELOPMENT){
			echo "$errstr";
		}

		// catching Smarty syntax error
		if($errno==E_USER_ERROR){
			if(!DEVELOPMENT){
				$HTTP_RESPONSE->internalServerError();
				$HTTP_RESPONSE->flushAll();
			}
		}

		// catching Smarty template missing error
		//if($errno==512){
			// ...
		//}

		if($HTTP_REQUEST){
			$errstr .= " (url: ".$HTTP_REQUEST->getUrl().")";
		}
		error_log($errstr);
	}

	return false;
}
set_error_handler("atk14_error_handler");

/**
 * Smarty2 <-> Smarty3 compatibility hack
 *
 * This is required in block helpers.
 */
function atk14_get_smarty_from_template($template){
	return isset($template->smarty) ? $template->smarty : $template;
}

function _smarty_addAtk14Content(&$smarty,&$atk14_contents,$key,$content,$options){
	$options += array(
		"strategy" => "append", // "append", "prepend", "replace",
			// "_place_initial_content_" // special private purpose
	);
	if(!isset($atk14_contents[$key])){ $atk14_contents[$key] = '<%atk14_initial_content%>'; }

	switch($options["strategy"]){
		case 'prepend':
			$atk14_contents[$key] = $content.$atk14_contents[$key];
			break;
		case 'replace':
			$atk14_contents[$key] = $content;
			break;
		case '_place_initial_content_':
			$atk14_contents[$key] = str_replace('<%atk14_initial_content%>',$content,$atk14_contents[$key]);
			break;
		default: // "append"	
			$atk14_contents[$key] .= $content;
	}
}
