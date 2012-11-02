<?php
/***************************************************************************
 *   Copyright (C) 2011 by Alexey Denisov                                  *
 *   alexeydsov@gmail.com                                                  *
 *                                                                         *
 *   This program is free software; you can redistribute it and/or modify  *
 *   it under the terms of the GNU Lesser General Public License as        *
 *   published by the Free Software Foundation; either version 3 of the    *
 *   License, or (at your option) any later version.                       *
 *                                                                         *
 ***************************************************************************/

	namespace Onphp\Utils;

	class DatabaseGenerator
	{
		/**
		 * @var \Onphp\DBSchema
		 */
		private $schema = null;
		/**
		 * @var \Onphp\DB
		 */
		private $db = null;

		/**
		 * @return \Onphp\Utils\DatabaseGenerator
		 */
		public static function create()
		{
			return new self;
		}
		
		/**
		 *
		 * @param string $schemaPath
		 * @return \Onphp\Utils\DatabaseGenerator 
		 */
		public function setSchemaPath($schemaPath)
		{
			include $schemaPath;
			\Onphp\Assert::isTrue(isset($schema), 'wrong schemaPath');
			\Onphp\Assert::isInstance($schema, '\Onphp\DBSchema', 'wrong schemaPath');

			$this->schema = $schema;

			return $this;
		}

		/**
		 * @param string $dbName
		 * @return \Onphp\Utils\DatabaseGenerator 
		 */
		public function setDBName($dbName)
		{
			$this->db = \Onphp\DBPool::me()->getLink($dbName);
			return $this;
		}

		/**
		 * @return \Onphp\Utils\DatabaseGenerator 
		 */
		public function run()
		{
			\Onphp\Assert::isInstance($this->db, '\Onphp\DB', 'call setDBName first');
			\Onphp\Assert::isInstance($this->schema, '\Onphp\DBSchema', 'call setSchemaPath first');

			$this->dropAllTables();
			$this->createAllTables();
		}
		
		public function generateSqlFile($file)
		{
			$sql = $this->schema->toDialectString($this->db->getDialect());
			file_put_contents($file, $sql);
		}

		/**
		 * @return \Onphp\Utils\DatabaseGenerator 
		 */
		private function dropAllTables()
		{
			foreach ($this->schema->getTables() as $name => $table) {
				/* @var $table \Onphp\DBTable */
				try {
					$this->db->queryRaw(
						\Onphp\OSQL::dropTable($name, true)->toDialectString(
							$this->db->getDialect()
						)
					);
				} catch (\Onphp\DatabaseException $e) {
					if (
						mb_strpos($e->getMessage(), 'does not exist') === false
						&& mb_strpos($e->getMessage(), 'missing database') === false
						&& mb_strpos($e->getMessage(), 'no such table') === false
					) {
						throw $e;
					}
				}

				try {
					if ($this->db->hasSequences()) {
						foreach (
							$this->schema->getTableByName($name)->getColumns()
								as $columnName => $column
						) {
							if ($column->isAutoincrement()) {
								$this->db->queryRaw("DROP SEQUENCE {$name}_id;");
							}
						}
					}
				} catch (\Onphp\DatabaseException $e) {
					if (mb_strpos($e->getMessage(), 'does not exist') === false) {
						throw $e;
					}
				}
			}

			return $this;
		}

		/**
		 * @return \Onphp\Utils\DatabaseGenerator 
		 */
		private function createAllTables()
		{
			$this->db->begin();
			foreach ($this->schema->getTables() as $tableName => $table) {
				/* @var $table \Onphp\DBTable */
				$this->db->queryRaw($table->toDialectString($this->db->getDialect()));
			}
			$this->db->commit();

			return $this;
		}
	}
