<?php
/**
 * Class for managing database migrations.
 *
 *
 * @package Atk14
 * @subpackage Core
 * @author Jaromir Tomek
 * @filesource
 */

/**
 * Class for managing database migrations.
 *
 * Common migration scripts are written in PHP.
 * If you want to use an SQL in a migration step look at the {@link Atk14MigrationBySqlFile} class.
 *
 * Example of migration file(db/migrations/0001_content_for_creatures.php):
 * <code>
 * class ContentForCreatures extends Atk14Migration{
 * 	function up(){
 * 		$data_ar = array(
 * 			array(
 * 				"name" => "Second creature",
 * 				"description" => "Normal creature. No picture is needed."
 * 			),
 * 			array(
 * 				"name" => "Third creature",
 * 				"description" => "Yet another creature."
 * 			)
 * 		);
 * 		foreach($data_ar as $data){
 * 			Creature::CreateNewRecord($data);
 * 		}
 * 	}
 * }
 * </code>
 *
 * @package Atk14
 * @subpackage Core
 * @author Jaromir Tomek
 * @filesource
 */
class Atk14Migration{

	/**
	 * Constructor.
	 * 
	 * @param string $version Migration file
	 */
	function Atk14Migration($version){
		$this->version = $version;
		$this->dbmole = &$GLOBALS["dbmole"];
		$this->_failed = false;

		$this->logger = &Atk14Migration::GetLogger();
	}

	/**
	 * Gets logger instance
	 *
	 * @return logger
	 */
	static function &GetLogger(){
		static $logger;
		if(!isset($logger)){
			$logger = new logger("migration",array(
				"log_to_stdout" => true,
			));
		}
		return $logger;
	}

	/**
	 * Process prepared migrations.
	 */
	function migrateUp(){
		$this->dbmole->begin();
		$this->up();
		if($this->_failed){ return; }
		$this->dbmole->commit();

		$this->dbmole->begin();
		// when we are forcing some migration, the given record in schema_migrations already exists
		if(0==$this->dbmole->selectInt("SELECT COUNT(*) FROM schema_migrations WHERE version=:version",array(":version" => $this->version))){
			$this->dbmole->insertIntoTable("schema_migrations",array("version" => $this->version));
		}
		$this->dbmole->commit();

		return true;
	}

	/**
	 * Abstract method to be overridden in subclass to execute the migration.
	 *
	 * @abstract
	 */
	function up(){
		// must be covered by the descendent...
	}

	// TODO: to be implemented: migrateDown() and down(), unless it is not needed

	/**
	 * @ignore
	 * @access private
	 */
	function _fail($message){
		$this->logger->error($message);
		$this->logger->flush();
		$this->_failed = true;
	}

	static function SchemaMigrationsTableExists($dbmole){
		return 1==$dbmole->selectInt("SELECT COUNT(*) FROM pg_tables WHERE LOWER(tablename)='schema_migrations'");
	}

	static function CreateSchemaMigrationsTable($dbmole){
		$dbmole->doQuery("CREATE TABLE schema_migrations(
			version VARCHAR(255) PRIMARY KEY,
			created_at TIMESTAMP NOT NULL DEFAULT NOW()
		)");
	}
}

/**
 * Allows using sql in migration scripts.
 *
 * <code>
 * $migration = Atk14MigrationBySqlScript("0000_sessions.sql");
 * $migration->migrateUp();
 * </code>
 *
 * @package Atk14
 * @subpackage Core
 */
class Atk14MigrationBySqlFile extends Atk14Migration{
	/**
	 * Executes migration script containing plain sql.
	 */
	function up(){
		global $ATK14_GLOBAL;
		$filename = $ATK14_GLOBAL->getMigrationsPath().$this->version;

		$content = Files::GetFileContent($filename,$err,$err_str);

		if($err){
			return $this->_fail("can't read $filename: $err_str");
		}
		
		if($this->dbmole->getDatabaseType()=='oracle'){

			// This is sick.
			// Oracle is unable to execute script with several sql commands at once.
			// So... look at the very provisional workaround.

			foreach(explode(";",$content) as $q){
				$q = trim($q); if(!$q){ continue; }
				$this->dbmole->doQuery($q);
			}

		}else{
			$this->dbmole->doQuery($content);
		}
	}
}
