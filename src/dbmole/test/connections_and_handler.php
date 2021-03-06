<?php

function oracle_run($conn, $stm)
{
    oci_execute(oci_parse($conn, $stm));
}

function &dbmole_connection(&$dbmole){
	$out = null;

	switch($dbmole->getDatabaseType()){
		case "mysql":
			$out = mysql_connect("localhost","test","test");
			mysql_select_db("test",$out);
			break;

		case "postgresql":
			$out = pg_connect("dbname=test host=localhost user=test password=test");
			break;

		case "oracle":
			$out = OCILogon("test","test","test"); // user, password, sid
      oracle_run($out, "ALTER SESSION SET NLS_DATE_FORMAT = 'YYYY-MM-DD HH24:MI:SS'");
      oracle_run($out, "ALTER SESSION SET NLS_SORT = 'Czech'");
			break;
	}

	return $out;
}
 
function dbmole_error_handler(&$dbmole){
	$dbmole->_ErrorRaised = false;

	throw new Exception("DbMole error: ".$dbmole->getErrorMessage());
}

DbMole::RegisterErrorHandler("dbmole_error_handler");
