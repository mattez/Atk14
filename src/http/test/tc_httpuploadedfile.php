<?php
class tc_httpuploadedfile extends tc_base{
	function test(){
		global $_FILES,$HTTP_RAW_POST_DATA,$HTTP_REQUEST;

		$_FILES = null;
		$this->assertEquals(array(),HTTPUploadedFile::GetInstances());

		$this->assertNull($HTTP_REQUEST->getUploadedFile("file"));
	}

	function test_testing_mode(){
		$this->_init_FILES();

		$files = HTTPUploadedFile::GetInstances();
		$this->assertEquals(0,sizeof($files)); // zde se musi poznat, ze "podvrzene" soubory nejsou ve skutecnosti uploadnuty

		$files = HTTPUploadedFile::GetInstances(array("testing_mode" => true));
		$this->assertEquals(2,sizeof($files));

		$hlava = $files[0];
		$this->assertTrue($hlava->isImage());
		$this->assertFalse($hlava->chunkedUpload());
	}

	function test_image_processing(){
		$this->_init_FILES();

		$files = HTTPUploadedFile::GetInstances(array("testing_mode" => true));

		$hlava = $files[0];
		$this->assertTrue($hlava->isImage());
		$this->assertEquals("image/jpeg",$hlava->getMimeType());
		$this->assertEquals("Hlava.jpg",$hlava->getFileName());

		$this->assertEquals(325,$hlava->getImageWidth());
		$this->assertEquals(448,$hlava->getImageHeight());

		$pdf = $files[1];
		$this->assertEquals("application/pdf",$pdf->getMimeType());
		$this->assertFalse($pdf->isImage());

		$this->assertNull($pdf->getImageWidth());
		$this->assertNull($pdf->getImageHeight());
	}

	function test_move_to_temp(){
		$this->_init_FILES();

		$files = HTTPUploadedFile::GetInstances(array("testing_mode" => true));
		$hlava = $files[0];

		$tmp_orig = $hlava->getTmpFilename();

		$this->assertTrue(file_exists($tmp_orig));
		
		$hlava->moveToTemp();
		$tmp_new = $hlava->getTmpFilename();

		$this->assertTrue($tmp_orig!=$tmp_new);

		$this->assertTrue(file_exists($tmp_new));
		$this->assertFalse(file_exists($tmp_orig));

		$brand_new_tmp_file = "tempicek";
		$hlava->moveToTemp($brand_new_tmp_file);

		$this->assertTrue(file_exists(TEMP."/$brand_new_tmp_file"));
		$this->assertFalse(file_exists($tmp_new));

		$hlava->cleanUp();
		$this->assertFalse(file_exists(TEMP."/$brand_new_tmp_file"));
	}

	function test__sanitizeFileName(){
		$f = new HTTPUploadedFile();

		$this->assertEquals("MyPhoto.jpg",$f->_sanitizeFileName("MyPhoto.jpg"));
		$this->assertEquals("me myself.jpg",$f->_sanitizeFileName("C:\\Document and Settings\\SillyBoy\\ me myself.jpg "));
		$this->assertEquals("MyPhoto.jpg",$f->_sanitizeFileName("MyPhoto.jpg"));

		$this->assertEquals("none",$f->_sanitizeFileName(" "));
		$this->assertEquals("none",$f->_sanitizeFileName("\\"));
		$this->assertEquals("Mala hneda listicka.pdf",$f->_sanitizeFileName("C:/Document and Settings/SillyBoy/ Malá hnědá lištička.pdf"));
	}
}
