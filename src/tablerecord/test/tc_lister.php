<?php
class TcLister extends TcBase{
	function test(){
		$this->article = Article::CreateNewRecord(array(
			"title" => "Christ's Sheep",
			"body" => "'My sheep hear My voice'\nChrist did say, 'and I know them\nand they follow Me'",
		));

		$this->article2 = Article::CreateNewRecord(array(
			"title" => "John 8:36",
			"body" => "If the Son therefore shall make you free, ye shall be free indeed.",
		));

		$john = Author::CreateNewRecord(array(
			"name" => "John",
		));

		$peter = Author::CreateNewRecord(array(
			"name" => "Peter",
		));

		$paul = Author::CreateNewRecord(array(
			"name" => "Paul",
		));

		$tony = Author::CreateNewRecord(array(
			"name" => "Tony",
		));

		$lister2 = $this->article2->getAuthorsLister();
		$lister2->append($john);

		# test ArrayAccess behavior of the lister
		$lister2[] = $tony;
		$this->assertTrue($lister2->contains($john));
		$this->assertTrue($lister2->contains($tony));
		$this->assertEquals(2, sizeof($lister2));

		$lister = $this->article->getAuthorsLister();

		$this->_test_authors(array());

		$lister->append($john);
		$this->_test_authors(array($john));
		$this->assertTrue($lister->contains($john));
		$this->assertTrue($lister->contains($john->getId()));
		$this->assertFalse($lister->contains($peter));
		$this->assertFalse($lister->contains(null));

		$lister->append($peter);
		$this->_test_authors(array($john,$peter));
		$this->assertTrue($lister->contains($john));
		$this->assertTrue($lister->contains($peter));

		$lister->prepend($paul);
		$this->_test_authors(array($paul,$john,$peter));


		$items = $lister->getItems();
		// move John to the begin
		$items[1]->setRank(0);
		$this->_test_authors(array($john,$paul,$peter));

		$lister->setRecordRank($john,1);
		$this->_test_authors(array($paul,$john,$peter));

		$lister->setRecordRank($john,1);
		$this->_test_authors(array($paul,$john,$peter));

		$lister->setRecordRank($john,2);
		$this->_test_authors(array($paul,$peter,$john));

		$lister->setRecordRank($john,0);
		$this->_test_authors(array($john,$paul,$peter));

		$lister->remove($john);
		$this->_test_authors(array($paul,$peter));

		// non-unique behaviour
		$lister->append($john);
		$lister->append($john);

		$this->_test_authors(array($paul,$peter,$john,$john));

		// setRecords
		$lister->setRecords(array($john,$peter));
		$this->_test_authors(array($john,$peter));

		$lister->setRecords(array());
		$this->_test_authors(array());

		$lister->setRecords(array($paul,$john));
		$this->_test_authors(array($paul,$john));

		$lister->setRecords(array($peter,$paul));
		$this->_test_authors(array($peter,$paul));

		$lister->setRecords(array($john,$peter,$paul));
		$this->_test_authors(array($john,$peter,$paul));

		# test ArrayAccess behavior of the Lister
		$lister[1] = $tony;
		$this->_test_authors(array($john, $tony, $paul));
		$lister[2] = $peter;
		$this->_test_authors(array($john, $tony, $peter));
		$lister[3] = $paul;
		$this->_test_authors(array($john, $tony, $peter, $paul));
		# test offsetUnset
		unset($lister[2]);
		$this->_test_authors(array($john, $tony, $paul));
	}

	function _test_authors($expected_authors){
		$authors = $this->article->getAuthors();

		$this->assertEquals(sizeof($expected_authors),sizeof($authors));
		for($i=0;$i<sizeof($authors);$i++){
			$this->assertEquals($expected_authors[$i]->getId(),$authors[$i]->getId());
		}

		$lister = $this->article->getAuthorsLister();
		$items = $lister->getItems();
		# test lister behaves as countable
		$this->assertEquals(sizeof($items), sizeof($lister));

		for($i=0;$i<sizeof($authors);$i++){
			$this->assertEquals($i,$items[$i]->getRank());
			$this->assertEquals($expected_authors[$i]->getId(),$items[$i]->getRecordId());

			# test that lister behaves the same as array
			$this->assertType("Author", $lister[$i]);
			$this->assertEquals($expected_authors[$i]->getId(),$lister[$i]->getId());
		}

		foreach($lister as $key => $record) {
			$this->assertType("Author", $record);
			$this->assertEquals($expected_authors[$key]->getId(), $record->getId());

		}

		// getRecords(), getRecordIds()
		$records = $lister->getRecords();
		$record_ids = $lister->getRecordIds();

		$this->assertEquals(sizeof($records),sizeof($record_ids));

		for($i=0;$i<sizeof($expected_authors);$i++){
			$this->assertEquals($expected_authors[$i]->getId(),$records[$i]->getId());
			$this->assertEquals($expected_authors[$i]->getId(),$record_ids[$i]);
		}

		//
		 
		$authors2 = $this->article2->getAuthors();
		$this->assertEquals(2,sizeof($authors2));
		$this->assertEquals("John",$authors2[0]->getName());
	}
}
