<?php

/**
 * ProcessWire Form Builder
 *
 * Provides the capability to build, edit and embed forms 
 * on your ProcessWire-powered web site. 
 *
 * Copyright (C) 2012 by Ryan Cramer Design, LLC
 * 
 * PLEASE DO NOT DISTRIBUTE
 * 
 * This file is commercially licensed.
 * 
 */

class FormBuilderMain implements IteratorAggregate, Countable {

	/**
	 * Name used by our tables, pages and templates
	 *
	 * Also serves as the default embed tag (though user may change that)
	 *
	 */
	const name = 'form-builder';

	/**
	 * Name of table used for form schema
	 *
	 */
	const formsTable = 'forms';

	/**
	 * Embed code used by the iframe embed options
	 *
	 * Tag {url} is replaced with the form's URL
	 *
	 */
	const embedCode = "<iframe frameborder='0' id='FormBuilderViewport_{name}' class='FormBuilderViewport' data-form='{name}' allowTransparency='true' style='width: 100%; height: 900px;' src='{url}'></iframe>"; 

	/**
	 * Copyright line, do not change or remove
	 *
	 */
	const RCD = 'ProcessWire Form Builder - Copyright 2014 by Ryan Cramer Design, LLC';

	/**
	 * User agent when submitting http requests
	 *
	 */
	const userAgent = 'ProcessWire/2 | FormBuilder/1';

	/**
	 * An array that holds just id (key) and name (value) for each form in the system
	 *
	 * We use this to quickly determine if a given id/name is used by a form.
	 *
	 */
	protected $formNames = array();

	/**
	 * Reference to PDO or WireDatabasePDO database instance
	 *
	 */
	protected $database;

	/**
	 * Writable path where Form Builder files may be stored
	 *
	 */
	protected $filesPath;

	/**
	 * @var Config
	 * 
	 */
	protected $config; 

	/**
	 * Names that may not be used for forms or fields
	 *
	 * Only necessary to include lowercase, as form field names can't contain uppercase
	 *
	 */
	protected $reservedNames = array(
		'name',
		'value',
		'field',
		'key',
		'id', 
		'class',
		'style',
		'processor',
		'entries',
		'form',
		'input',
		'forms',
		'action',
		'action2',
		'action2_add',
		'action2_remove',
		'action2_rename',
		'method',
		'get',
		'post',
		'target',
		'honeypot',
		'type',
		'label',
		'description',
		'notes',
		'head',
		'parent',
		'required',
		'level',
		'children',
		'processor',
		'entries',
		'akismet',
		'created',
		'modified',
		'data',
		);

	/**
	 * Construct FormBuilderMain and include related files
	 *
	 * @param PDO $database
	 * @param string $filesPath Path where form builder files can be stored
	 *
	 */
	public function __construct($database, $filesPath, $config) {

		$this->database = $database; 
		$this->filesPath = self::parseFilesPath($filesPath);
		$this->config = $config; 

		$dirname = dirname(__FILE__) . '/';
		require_once($dirname . 'FormBuilderException.php'); 
		require_once($dirname . 'FormBuilderData.php'); 
		require_once($dirname . 'FormBuilderField.php'); 
		require_once($dirname . 'FormBuilderForm.php'); 
		require_once($dirname . 'FormBuilderEntries.php');
		require_once($dirname . 'FormBuilderFramework.php');
		require_once($dirname . 'FormBuilderRender.php');
		require_once($dirname . 'InputfieldFormBuilder.php'); 
	}

	/**
	 * Given a path with a potential {config.paths.$key} in it, parse it to an actual runtime path
	 *
	 */
	static public function parseFilesPath($path) {

		if(strpos($path, '{') !== false && preg_match('/\{config\.paths\.([a-z]+)\}/', $path, $matches)) {
			$key = $matches[1]; 
			$path = str_replace($matches[0], wire('config')->paths->$key, $path); 
		}

		// normalize trailing slash
		$path = rtrim($path, '/') . '/'; 

		// go to a default path if the specified one doesn't exist
		if(!is_dir($path)) $path = wire('config')->paths->cache . self::name . '/'; 

		return $path;
	}

	/**
	 * Populate the $forms summary variable
	 *
	 */
	public function getFormNames() {
		if(count($this->formNames)) return $this->formNames;
		$query = $this->database->prepare("SELECT id, name FROM " . self::formsTable . " ORDER BY name"); 
		$query->execute();
		if($query->rowCount()) {
			while($row = $query->fetch(PDO::FETCH_NUM)) {
				list($id, $name) = $row; 
				$this->formNames[$id] = $name;
			}
		}
		return $this->formNames; 
	}

	/**
	 * Given a form ID or name, returns true if is used by a form, false if not
	 *
	 * @param int|string $id May be form ID or form name
	 * @return bool
	 *
	 */
	public function isForm($id) {
		$formNames = $this->getFormNames();
		if(ctype_digit("$id")) return isset($formNames[$id]); 
		if(strlen($id)) return in_array($id, $formNames); 
		return false;
	}

	/**
	 * Retrieve a form by $id or $name
	 *
	 * @param int|string May be form ID or form name
	 * @return FormBuilderForm|null Returns $form on success or NULL on failure to load
	 *
	 */
	public function load($id) {
		static $cache = array();

		if(!$this->isForm($id)) return null;
		if(isset($cache[$id])) return $cache[$id];
		$bindValues = array();

		$sql = "SELECT id, name, data FROM " . self::formsTable . ' ';
	
		if(ctype_digit("$id")) { 	
			$id = (int) $id; 
			if(!$id) return null;
			$bindValues['id'] = $id;
			$sql .= "WHERE id=:id";

		} else if(strlen($id)) {
			$name = preg_replace('/[^-_.a-z0-9]/i', '-', $id); // sanitize name
			$sql .= "WHERE name=:name";
			$bindValues['name'] = $name;

		} else {
			// no form specified
			return null;
		}
		
		$query = $this->database->prepare($sql);
		foreach($bindValues as $key => $value) {
			$query->bindValue(":$key", $value); 
		}
		
		$query->execute();
		if(!$query->rowCount()) return null;
		
		list($id, $name, $data) = $query->fetch(PDO::FETCH_NUM); 
		$data = json_decode($data, true);
		if(!is_array($data)) $data = array();

		if(!isset($data['children'])) $data['children'] = array();

		$form = new FormBuilderForm($this);
		$form->set('id', $id); 
		$form->set('name', $name); 
		$form->setArray($data); 

		$cache[$id] = $form;

		return $form;
	}

	/**
	 * Save the given $form 
	 *
	 * @param FormBuilderForm $form
	 * @return bool Returns true on success, false on failure
	 * @throws WireException
	 *
	 */
	public function save(FormBuilderForm $form) {
		if(!preg_match('/[-_a-z]/i', $form->name)) throw new WireException($this->_('Form name must have at least one a-z letter in it.')); 
		$id = (int) $form->id; 
		$data = $form->getArray();
		unset($data['name'], $data['type'], $data['id']); 
		$sql = ($id ? "UPDATE" : "INSERT INTO") . " " . self::formsTable . " SET name=:name, data=:data " . ($id ? "WHERE id=:id" : ''); 
		$query = $this->database->prepare($sql); 
		$query->bindValue(':name', $form->name, PDO::PARAM_STR); 
		$query->bindValue(':data', json_encode($data), PDO::PARAM_STR); 
		if($id) $query->bindValue(':id', $id, PDO::PARAM_INT); 
		$query->execute();
		if(!$id) $form->id = $this->database->lastInsertId();
		$this->getFormNames();
		$this->formNames[$form->id] = $form->name;
		return true; 
	}

	/**
	 * Add a new form with the given name
	 *
	 * @param string $formName Using characters: -_a-z0-9
	 * @return FormBuilderForm
	 * @throws WireException
	 *
	 */
	public function addNew($formName) {
		$formName = preg_replace('/[^-_.a-z0-9]/i', '-', $formName); // sanitize name
		if(!$formName) throw new WireException("No form name specified"); 
		$query = $this->database->prepare("INSERT INTO " . self::formsTable . " SET name=:name, data=''");
		$query->bindValue(':name', $formName, PDO::PARAM_STR); 
		$query->execute();
		$form = new FormBuilderForm($this);
		$form->id = $this->database->lastInsertId();
		$form->name = $formName; 
		return $form; 
	}

	/**
	 * Delete the given $form 
	 *
	 * @param int|string|FormBuilderForm $id May be a $form instance, an ID or a name
	 * @return bool Returns true on success
	 * @throws PDOException on failure
	 *
	 */
	public function delete($id) {
		if(is_object($id) && $id instanceof FormBuilderForm) $id = $id->id;
		if(!ctype_digit("$id")) $id = array_search($id, $this->getFormNames());
		$id = (int) $id; 
		if(!$id) return false;
		$query = $this->database->prepare("DELETE FROM " . self::formsTable . " WHERE id=:id LIMIT 1");
		$query->bindValue(':id', $id, PDO::PARAM_INT); 
		$query->execute(); 

		$entries = new FormBuilderEntries($id, $this->database);
		$entries->deleteAll(); 

		$path = $entries->getFilesPath();
		if($path) wireRmdir($path, true);

		$this->getFormNames();
		unset($this->formNames[$id]); 
		return true; 
	}

	/**
	 * Retrieve a form (alias of load)
	 *
	 */
	public function get($key) {
		if($this->isForm($key)) $value = $this->load($key);
			else $value = null;
		return $value;
	}

	/**
	 * Make this module iterable, as required by the IteratorAggregate interface
	 *
	 */
	public function getIterator() {
		return new ArrayObject($this->getFormNames()); 
	}

	/**
	 * Return number of forms here, per Countable interface
	 *
	 */
	public function count() {
		return count($this->getFormNames()); 
	}

	/**
	 * Return the number of entries for the given form ID
	 *
	 */
	public function countEntries($id) {
		$entries = new FormBuilderEntries($id, $this->database); 	
		return $entries->getTotal();
	}

	/**
	 * Return the JSON schema for the given form ID
	 *
	 */
	public function exportJSON($id) {
		$id = (int) $id; 
		if(!$id) return '';
		$query = $this->database->prepare("SELECT data FROM " . self::formsTable . " WHERE id=:id");
		$query->bindValue(':id', $id, PDO::PARAM_INT); 
		$query->execute();
		if(!$query->rowCount()) return '';
		list($data) = $query->fetch(PDO::FETCH_NUM); 
		// @todo this prevents export from PHP 5.4 => import to PHP 5.3
		if(defined("JSON_PRETTY_PRINT") && wire('config')->debug) {
			$data = json_decode($data, true); 
			$data = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); 
		}
		return $data; 
	}

	/**
	 * Import the given JSON schema for the given form ID
	 *
	 */
	public function importJSON($id, $json) {
		$id = (int) $id; 
		$data = json_decode($json, true); 
		$result = false;
		if($data && array_key_exists('children', $data)) {
			// JSON is valid
			$query = $this->database->prepare("UPDATE " . self::formsTable . " SET data=:json WHERE id=:id"); 
			$query->bindValue(':json', $json, PDO::PARAM_STR); 
			$query->bindValue(':id', $id, PDO::PARAM_INT);
			$result = $query->execute();
		}
		return $result;
	}

	/**
	 * Returns whether the given license key is valid for the domain its running on
	 *
	 * @param string $license
	 * @return bool
	 *
	 */
	public function isValidLicense($license) {
		return true; 
		/*
		$host = $_SERVER['HTTP_HOST'];
		while(substr_count($host, '.') > 1) $host = substr($host, strpos($host, '.')+1);
		return in_array(str_rot13(strtolower($host)), explode("+", base64_decode($license)));
		*/
	}

	/**
	 * Returns whether or not the given $name may be used or a form or field name
	 *
	 * @param string $name
	 * @return bool
	 *
	 */
	public function isReservedName($name) {
		return in_array($name, $this->reservedNames);
	}

	/**
	 * Return path that FormBuilder uses for storing files
	 *
	 * @param bool $tmp Whether to return the tmp path (default=false)
	 * @return string 
	 * @throws FormBuilderException
	 *
	 */
	public function getFilesPath($tmp = false) {

		$path = $this->filesPath; 
		if(!is_dir($path)) wireMkdir($path);
		if($tmp) { 
			$path .= 'tmp/';
			if(!is_dir($path)) wireMkdir($path);
		}

		if(!is_dir($path)) throw new FormBuilderException("Unable to create: $path"); 
		if(!is_writable($path)) throw new FormBuilderException("Unable to write to: $path");

		return $path;
	}

	/**
	 * Generate a transportable key for the given filename within a form and entry
	 *
	 * @param int $formID
	 * @param int $entryID Or specify 0 if not yet an active entry
	 * @param string $filename
	 * @return bool|string Returns false on failure or string on success
	 *
	 */
	public function getFileKey($formID, $entryID, $filename) {
		if(!is_file($filename)) return false;
		$key = "$formID,$entryID," . basename($filename) . "," . sha1_file($filename);
		return $key; 
	}

	/**
	 * Retrieve a filename from a transportable key
	 *
	 * @param string $key Must be in the format given by getFileKey
	 * @return bool|string Returns boolean false on failure or full path+filename on success
	 *
	 */
	public function getFileFromKey($key) {
		if(!preg_match('/^(\d+),(\d+),([-_.a-zA-Z0-9]+),(.+)$/', trim($key), $matches)) return false;
		$formID = (int) $matches[1];
		if(!$formID) return false;
		$entryID = (int) $matches[2];
		$basename = $matches[3]; 
		$hash = $matches[4]; 
		$form = $this->load((int) $formID);
		if(!$form) return false;
		if($entryID) $path = $form->entries()->getFilesPath($entryID);
			// else $path = $this->getTempFilesPath();
		$filename = $path . $basename; 
		if(!is_file($filename)) return false;
		if(sha1_file($filename) !== $hash) return false;
		return $filename;	
	}

	/**
	 * Return a URL where the given file can be viewed
	 *
	 * @param int $formID
	 * @param int $entryID Or specify 0 if not yet an active entry
	 * @param string $filename
	 * @return bool|string Returns false on failure or URL on success
	 *
	 */
	public function getFileURL($formID, $entryID, $filename) {
		$key = $this->getFileKey($formID, $entryID, $filename);
		if(!$key) return false;
		$page = wire('pages')->get("template=" . self::name);		
		if(!$page->id) return false;
		return $page->httpUrl() . "?view_file=$key";
	}

	/**
	 * Outputs the given file, must be located under getFilesPath()
	 *
	 * @param string $key Key representing the file to view (generated by getFileKey) 
	 * @return bool Returns false on failure. On success, it exists program execution.
	 *
	 */
	public function viewFile($key) {

		$filename = $this->getFileFromKey($key);
		if(!$filename || !is_file($filename)) return false;

		$filesize = filesize($filename);	
		$info = pathinfo($filename);
		$ext = $info['extension']; 

		$contentTypes = array(
			'pdf' => 'application/pdf',
			'doc' => 'application/msword',
			'docx' => 'application/msword',
			'xls' => 'application/excel',
			'xlsx' => 'application/excel',
			'rtf' => 'application/rtf',
			'gif' => 'image/gif', 
			'jpg' => 'image/jpeg',
			'jpeg' => 'image/jpeg',
			'png' => 'image/x-png', 
			);

		// types that won't trigger a 'save' dialog and instead will just display
		$nonDownloadTypes = array('gif', 'jpg', 'jpeg', 'png');
		
		if(isset($contentTypes[$ext])) $contentType = $contentTypes[$ext];
			else $contentType = 'application/octet-stream';

		if(ini_get('zlib.output_compression')) ini_set('zlib.output_compression', 'Off');

		header("Pragma: public");
		header("Expires: 0");
		header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
		header("Content-Type: $contentType");
		header("Content-Transfer-Encoding: binary");
		header("Content-Length: $filesize");
		if(!in_array($ext, $nonDownloadTypes)) header("Content-Disposition: attachment; filename=\"$info[basename]\"");
		ob_clean();
		flush();
		readfile($filename);
		exit;
	}

	public function helperPath($for, $item = '') {
		// attempt to locate in /site/templates/FormBuilder/themes/
		$path = $this->config->paths->templates . "FormBuilder/$for/";
		if($item) $path .= "$item/";
		if(is_dir($path)) return $path;

		// attempt to locate in /site/modules/FormBuilder/themes/	
		$path = $this->config->paths->FormBuilder . "$for/";
		$_path = $path;
		if($item) $path .= "$item/";
		if(is_dir($path)) return $path;

		if($item) {
			// directory for theme was not found, substitute default
			$this->error("Unable to locate directory for theme '$item'");
			return $_path . "$item/";
		}

		return $_path;
	}

	public function helperURL($for, $item = '') {
		// attempt to locate in /site/templates/FormBuilder/themes/
		$path = $this->config->paths->templates . "FormBuilder/$for/";
		if($item) $path .= "$item/";
		if(is_dir($path)) {
			$url = $this->config->urls->templates . "FormBuilder/$for/";
			if($item) $url .= "$item/";
			return $url;
		}

		// attempt to locate in /site/modules/FormBuilder/themes/
		if($item) {
			$path = $this->config->paths->FormBuilder . "$for/$item/";
			if(is_dir($path)) {
				return $this->config->urls->FormBuilder . "$for/$item/";
			} else {
				// if theme can't be found even here, substitute default theme
				$this->error("Unable to locate directory for '$item'");
				return $this->config->urls->FormBuilder . "$for/$item/";
			}
		}

		return $this->config->urls->FormBuilder . "$for/";
	}

	/**
	 * Get all 'themes' or 'frameworks' files
	 * 
	 * @param $for Specify themes or frameworks
	 * @param bool $getDirs Specify true to return directories, or false to return files
	 *
	 * @return array
	 * 
	 */

	public function getHelpers($for, $getDirs = true) {
		$dir = new DirectoryIterator($this->helperPath($for));
		$files = array();
		foreach($dir as $file) {
			$basename = $file->getBasename();
			if($file->isDot()) continue;
			if($getDirs && !$file->isDir()) continue;
			if(!$getDirs && $file->isDir()) continue;
			if(substr($basename, 0, 1) !== '.') {
				$files[] = $basename;
			}
		}
		sort($files);
		return $files;
	}

	/**
	 * Return the path where themes are stored
	 *
	 * If the dir /site/templates/FormBuilder/themes/ exists, it will use that.
	 * Otherwise it uses /site/modules/FormBuilder/themes/
	 *
	 * @param string $theme Optionally specify the theme and it will be included in the path
	 * @return string
	 *
	 */
	public function themesPath($theme = '') {
		return $this->helperPath('themes', $theme);
	}

	/**
	 * Return the path where frameworks are stored
	 *
	 * If the dir /site/templates/FormBuilder/frameworks/ exists, it will use that.
	 * Otherwise it uses /site/modules/FormBuilder/frameworks/
	 *
	 * @return string
	 * @throws WireException
	 *
	 */
	public function frameworksPath() {
		return $this->helperPath('frameworks');
	}

	/**
	 * Return the URL where themes are stored
	 *
	 * If the dir /site/templates/FormBuilder/themes/ exists, it will use that.
	 * Otherwise it uses /site/modules/FormBuilder/themes/
	 *
	 * @param string $theme Optionally specify the theme and it will be included in the url
	 * @return string
	 *
	 */
	public function themesURL($theme = '') {
		return $this->helperURL('themes', $theme);
	}

	/**
	 * Return the URL where frameworks are stored
	 *
	 * If the dir /site/templates/FormBuilder/frameworks/ exists, it will use that.
	 * Otherwise it uses /site/modules/FormBuilder/frameworks/
	 *
	 * @return string
	 *
	 */
	public function frameworksURL() {
		return $this->helperURL('frameworks');
	}

	/**
	 * Get the framework used by the given $form
	 *
	 * Also prepares the framework with it's config values populated
	 *
	 * @param FormBuilderForm $form
	 * @return FormBuilderFramework|null
	 *
	 */
	public function getFramework(FormBuilderForm $form) {
		static $frameworks = array();
		if(!$form->framework) $form->framework = 'Legacy';
		$name = $form->name ? $form->name : $form->framework;
		if(isset($frameworks[$name])) return $frameworks[$name];
		$class = "FormBuilderFramework$form->framework";
		$file = $this->frameworksPath() . "$class.php";
		if(!file_exists($file)) return null;
		include_once($file);
		$framework = new $class($form);
		$prefix = $framework->getPrefix();
		foreach($framework->getConfigDefaults() as $key => $unused) {
			$property = $prefix . $key;
			$value = $form->$property;
			if($value !== null) $framework->set($key, $value);
		}
		$frameworks[$name] = $framework;
		return $framework;
	}


	/**
	 * Direct access properties
	 *
	 */
	public function __get($key) {
		if($key == 'database') return $this->database; 
		return null;
	}

	/**
	 * Install the tables
	 *
	 */
	public function _install() {

		$sql =  
			"CREATE TABLE " . self::formsTable . " (" . 
			"id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, " . 
			"name VARCHAR(128) NOT NULL, " .  
			"data TEXT NOT NULL, " . 
			"UNIQUE name (name)" . 
			")";

		try {
			$this->database->exec($sql); 
		} catch(Exception $e) { 
			wire('modules')->error($e->getMessage());
		}
		
		FormBuilderEntries::_install($this->database);
	}

	/**
	 * Uninstall the tables
	 *
	 */
	public function _uninstall() {
		try {
			$this->database->exec("DROP TABLE " . self::formsTable);
			FormBuilderEntries::_uninstall(wire('database'));
		} catch(Exception $e) {
			// just catch, no need to do anything else
		}
	}


}
