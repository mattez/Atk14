<?php
class tc_base extends tc_super_base{

	function _init_FILES(){
		global $_FILES;

		Files::CopyFile("hlava.jpg",TEMP."/temp_hlava.jpg");
		Files::CopyFile("dousi.pdf",TEMP."/temp_dousi.pdf");

		$_FILES = array(
			"hlava" => array(
				"tmp_name" => TEMP."/temp_hlava.jpg",
				"name" => "Hlava.jpg"
			),
			"dousi" => array(
				"tmp_name" => TEMP."/temp_dousi.pdf",
				"name" => "Dousi.pdf"
			), 
		);
	}
}
