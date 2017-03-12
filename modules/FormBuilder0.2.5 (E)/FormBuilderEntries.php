<?php

/**
 * ProcessWire Form Builder Entries/Results
 *
 * Copyright (C) 2012 by Ryan Cramer Design, LLC
 * 
 * PLEASE DO NOT DISTRIBUTE
 * 
 * This file is commercially licensed.
 * 
 */

class FormBuilderEntries extends Wire {

	/**
	 * Name of DB table created/used by this class
	 *
	 */
	const entriesTable = 'forms_entries';

	/**
	 * ID of form these entries are for
	 *
	 */
	protected $forms_id = 0;

	/**
	 * Total entries found (sans pagination) from the last find() call, for getLastTotal() method.
	 *
	 */
	protected $lastTotal = 0;

	/**
	 * Default date format used for entry dates
	 *
	 * 
	 */
	protected $dateFormat = '';

	/**
	 * Reference to $mysqli database
	 *
	 */
	protected $database; 

	/**
	 * Construct the Form entries
	 *
	 */
	public function __construct($forms_id, $database) {
		$this->forms_id = (int) $forms_id; 
		$this->database = $database; 
		$this->dateFormat = $this->_('Y/m/d G:i'); // date format for entries
	}

	/**
	 * Convert a string containing selectors to an array of field, opreator, value
	 *
	 * @param str
	 * @return array
	 *
	 */
	protected function selectorStringToArray($str) {
		$a = array();
		$items = explode(',', $str);
		foreach($items as $key => $value) {
			if(preg_match('/^([-a-z0-9]+)([=<>]+)(.*)$/i', trim($value, ' \'"'), $matches)) {
				$s = new stdClass(); 
				$s->field = $matches[1];
				$s->operator = $matches[2];
				$s->value = $matches[3]; 
				$a[] = $s; 
			}
		}
		return $a; 	
	}

	/**
	 * Build an SQL query to find entries by translating a selector string
	 *
	 * @param string $selectorString
	 * @param bool $countTotal Whether we should include an SQL_CALC_FOUND_ROWS
	 * @return string SQL query ready to use
	 * @throws FormBuilderException
	 *
	 */
	protected function buildFindQuery($selectorString, $countTotal = true) {

		$selectors = $this->selectorStringToArray($selectorString);
		$where = '';
		$limit = 0; 
		$start = 0;
		$orderby = 'created DESC';

		foreach($selectors as $selector) {	

			$field = $selector->field;
			$operator = $selector->operator;
			$value = $selector->value; 

			if(!$this->database->isOperator($operator)) {
				throw new FormBuilderException("Operator '$operator' is not valid for querying '$field'"); 
			}

			switch($field) { 

				case 'id': 
					$value = (int) $value; 
					$where .= "AND id$operator$value ";
					break;

				case 'created':
					if(!ctype_digit($value)) $value = strtotime($value);
					$value = date('Y-m-d H:i:s', $value); 
					$where .= "AND created$operator'$value' ";
					break;

				case 'sort': 
					$value = $this->database->escapeStr($value);
					if(substr($value, 0, 1) == '-') $orderby = "`" . trim($value, '-') . "` DESC";
						else $orderby = "`$value`";
					break;

				case 'start': 
					$start = (int) $value; 
					break;

				case 'limit':
					$limit = (int) $value; 
					break;
			}

		}

		$forms_id = (int) $this->forms_id; 
		$sql = 	
			"SELECT " . ($countTotal ? "SQL_CALC_FOUND_ROWS " : '') . 
			"id, data, created FROM " . self::entriesTable . " " . 
			"WHERE forms_id=$forms_id $where " . 
			"ORDER BY $orderby ";

		if($limit) $sql .= "LIMIT $start,$limit ";

		return $sql;
	}

	/**
	 * Get an array of form entries
	 *
	 * @param int|string $selectorString
	 * @return array
	 *
	 */
	public function find($selectorString) {

		$entries = array();
		$sql = $this->buildFindQuery($selectorString);
		$query = $this->database->prepare($sql); 
		$query->execute();

		while($row = $query->fetch(PDO::FETCH_ASSOC)) { 
			$data = json_decode($row['data'], true); 	
			$data['id'] = (int) $row['id']; 
			$data['created'] = date($this->dateFormat, strtotime($row['created']));
			$entries[] = $data; 
		}
		// $query->closeCursor();

		$query = $this->database->prepare("SELECT FOUND_ROWS()"); 
		$query->execute();
		list($this->lastTotal) = $query->fetch(PDO::FETCH_NUM);

		return $entries; 
	}

	/**
	 * Get a single form result array, or null if not found
	 *
	 * @param int|string $selectorString
	 * @return null|array
	 *
	 */
	public function get($selectorString) {
		if(ctype_digit("$selectorString")) $selectorString = "id=$selectorString"; 
		$selectorString .= ", limit=1";
		$results = $this->find($selectorString);
		if(empty($results)) return null;
		return reset($results);
	}

	/**
	 * Works like find() except that it exports a CSV file
	 *
	 * This function also halts execution of the request after the CSV has been delivered
	 *
	 * @param FormBuilderForm $form
	 * @param string $selectorString 
	 * @param string $filename 
	 * @param string $delimiter
	 * 
	 */
	public function exportCSV(FormBuilderForm $form, $selectorString, $filename = 'export.csv', $delimiter = ',') {
		
		if(strtoupper($delimiter) === 'T') $delimiter = "\t";
			else if(empty($delimiter)) $delimiter = ',';

		header("Content-type: application/force-download");
		header("Content-Transfer-Encoding: Binary");
		header("Content-disposition: attachment; filename=$filename");

		$fp = fopen('php://output', 'w');
		// fwrite($fp, "\xEF\xBB\xBF"); // UTF-8 BOM: needed for some software to recognize UTF-8

		$sql = $this->buildFindQuery($selectorString, false);
		$query = $this->database->prepare($sql); 
		$query->execute();
		$fields = array();
		$formBuilderFields = array(); 
		
		while($row = $query->fetch(PDO::FETCH_ASSOC)) { 

			$data = json_decode($row['data'], true); 	
			$data['id'] = (int) $row['id']; 
			$data['created'] = date('Y-m-d H:i:s', strtotime($row['created']));

			if(empty($fields)) {
				// write out the first row with column names
				$fields = array_keys($data);
				@fputcsv($fp, $fields, $delimiter);
			}

			// this makes sure that all the data is in the same order 
			// as the CSV fields from the first row, in case format ever changed
			$a = array();
			foreach($fields as $name) {

				$value = array_key_exists($name, $data) ? $data[$name] : '';

				if(isset($formBuilderFields[$name])) {
					$formBuilderField = $formBuilderFields[$name]; 
				} else {
					$formBuilderField = $form->find($name); 
					$formBuilderFields[$name] = $formBuilderField; 
				}

				if($formBuilderField) $value = $this->exportCSV_formatValue($formBuilderField, $value); 

				if(is_array($value)) $value = implode("\n", $value); 

				$a[$name] = $value; 
			}

			// send the row
			@fputcsv($fp, $a, $delimiter);
		}

		$query->closeCursor();
		fclose($fp); 
		exit();
	}

	protected function exportCSV_formatValue(FormBuilderField $field, $value) {
		if($field->type == 'Datetime' && $value) {
			$value = date('Y-m-d H:i:s', $value);	
		}
		return $value; 
	}

	/**
	 * Save the given entry 
	 *
	 * If it is an existing entry that should be updated, then it should have a populated 'id' property
	 * otherwise it will be inserted as a new entry. 
	 *
	 * @param array $data
	 * @return int|bool entry ID on success, false if not
	 * @throws Exception on failure
	 *
	 */
	public function ___save(array $data) {

		$id = 0;
		if(isset($data['id'])) $id = (int) $data['id'];

		$created = date('Y-m-d H:i:s', time());

		unset($data['id'], $data['created']); 

		$sql = ($id ? "UPDATE " : "INSERT INTO "); 
		$sql .=	self::entriesTable . " SET forms_id=:forms_id, data=:data ";
		
		if($id) $sql .= "WHERE id=:id";
			else $sql .= ", created=:created ";
		
		$query = $this->database->prepare($sql); 
		$query->bindValue(':forms_id', $this->forms_id, PDO::PARAM_INT); 
		$query->bindValue(':data', json_encode($data), PDO::PARAM_STR); 
		if($id) $query->bindValue(':id', $id, PDO::PARAM_INT); 
				else $query->bindValue(':created', $created); 
		$query->execute();
		if(!$id) $id = $this->database->lastInsertId();

		return $id; 
	}

	/**
	 * Delete a form entry
	 *
	 * @param int $id
	 * @return int|bool ID of entry that was deleted or false on failure
	 *
	 */
	public function ___delete($id) {
		$id = (int) $id; 
		$path = $this->getFilesPath($id);
		$query = $this->database->prepare("DELETE FROM " . self::entriesTable . " WHERE forms_id=:forms_id AND id=:id LIMIT 1"); 
		$query->bindValue(':forms_id', $this->forms_id, PDO::PARAM_INT); 
		$query->bindValue(':id', $id, PDO::PARAM_INT); 
		$result = $query->execute();
		if($result) wireRmdir($path, true);
		return $result ? $id : false;
	}

	/**
	 * Delete all entries for this form
	 *
	 * @return bool
	 *
	 */
	public function ___deleteAll() {
		$query = $this->database->prepare("DELETE FROM " . self::entriesTable . " WHERE forms_id=:forms_id"); 
		$query->bindValue(':forms_id', $this->forms_id, PDO::PARAM_INT); 
		$result = $query->execute();
		wireRmdir($this->getFilesPath(), true); 
		return $result ? true : false;
	}

	/**
	 * Return total number of entries
	 *
	 * @return int
	 *
	 */
	public function getTotal() {
	
		$query = $this->database->prepare("SELECT COUNT(*) FROM " . self::entriesTable . " WHERE forms_id=:forms_id"); 
		$query->bindValue(':forms_id', $this->forms_id, PDO::PARAM_INT); 
		$query->execute();
		list($count) = $query->fetch(PDO::FETCH_NUM); 
		return $count; 
	}

	/**
	 * Return the total known from the last find() query
	 *
	 */
	public function getLastTotal() {
		return $this->lastTotal;
	}

	/**
	 * Return the path that may be used by entries
	 *
	 * @param int $entryID When specified, will return the path for a specific entry's files
	 * @return string
	 *
	 */
	public function getFilesPath($entryID = 0) { 
		$path = wire('forms')->getFilesPath() . 'form-' . $this->forms_id . '/';
		if(!is_dir($path)) wireMkdir($path);
		$entryID = (int) $entryID;
		if($entryID) {
			$path .= "entry-$entryID/";
			if(!is_dir($path)) wireMkdir($path);
		}
		return $path;
	}

	public function getFormID() {
		return $this->forms_id; 
	}

	/**
	 * Install the forms_entries table
	 *
	 */
	public static function _install($database) {

		$sql =  
			"CREATE TABLE " . self::entriesTable . " (" . 
			"id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, " . 
			"forms_id INT UNSIGNED NOT NULL, " . 
			"data TEXT NOT NULL, " .
			"created TIMESTAMP NOT NULL, " . 
			"INDEX forms_id (forms_id)" . 
			")";

		try {
			$query = $database->prepare($sql);
			$query->execute();
		} catch(Exception $e) {
			wire('modules')->error($e->getMessage());
		}
	}

	/**
	 * Uninstall the forms_entries table
	 *
	 */
	public static function _uninstall($database) {
		$database->exec("DROP TABLE " . self::entriesTable);
	}


}
