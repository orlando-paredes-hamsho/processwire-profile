<?php

/**
 * Form Builder Data
 *
 * Base container for Form Builder classes
 * 
 * Copyright (C) 2012 by Ryan Cramer Design, LLC
 * 
 * PLEASE DO NOT DISTRIBUTE
 * 
 * This file is commercially licensed.
 * 
 */

class FormBuilderData {

	/**
	 * Array where get/set properties are stored
	 *
	 */
	protected $data = array(); 

	/**
	 * Set a value 
	 *
	 * @param string $key
	 * @param mixed $value
	 * @return this
	 *
	 */
	public function set($key, $value) {
		$this->data[$key] = $value; 
		return $this; 
	}

	/**
	 * Set an array of key=value pairs
	 *
	 * @param array $data
	 * @return this
	 * @see set()
	 *
	 */
	public function setArray(array $data) {
		foreach($data as $key => $value) $this->set($key, $value); 
		return $this; 
	}

	/**
	 * Provides direct reference access to set values in the $data array
	 *
	 */
	public function __set($key, $value) {
		$this->set($key, $value); 
	}

	/**
	 * Provides direct reference access to retrieve values in the $data array
	 *
 	 * @param string $key
	 * @return mixed|null Returns null if the key was not found. 
	 *
	 */
	public function get($key) {
		if(array_key_exists($key, $this->data)) return $this->data[$key]; 
			else return null;
	}

	/**
	 * Returns the full $data array
	 *
	 */
	public function getArray() {
		return $this->data; 
	}

	/**
	 * Provides direct reference access to variables in the $data array
	 *
	 * Otherwise the same as get()
	 *
	 * @param string $key
	 * @return mixed
	 *
	 */
	public function __get($key) {
		return $this->get($key); 
	}

	/**
	 * Remove a given $key from the $data array
	 *
	 * @param string $key
	 * @return this
	 *
	 */
	public function remove($key) {
		unset($this->data[$key]); 
		return $this;
	}

	/**
	 * Ensures that isset() and empty() work for this classes properties. 
	 *
	 */
	public function __isset($key) {
		return isset($this->data[$key]);
	}

	/**
	 * Ensures that unset() works for this classes data. 
	 *
	 */
	public function __unset($key) {
		$this->remove($key); 
	}

}

