<?php
function smarty_function_to_json($params,$template){
	// TODO: if $params["var"] is an object, the method toJson() should be called (if such method exists)
	$out = json_encode($params["var"]);
	return $out;
}
