<?php

/**
 * ProcessWire Form Builder Field
 *
 * Serves as an individual field in a Form Builder form.
 * It is an intermediary between the JSON/array form and Inputfields.
 *
 * Copyright (C) 2012 by Ryan Cramer Design, LLC
 * 
 * PLEASE DO NOT DISTRIBUTE
 * 
 * This file is commercially licensed.
 * 
 */

class FormBuilderField extends FormBuilderData {

	/**
	 * Used when getting a flat representation of all fields
	 *
	 */
	static protected $allFields = array();

	/**
	 * Children of this field
	 *
	 */
	protected $children = array();

	/**
	 * Set default/starting values
	 *
	 */
	public function __construct() {
		$this->set('name', ''); 
		$this->set('type', ''); 
		$this->set('label', ''); 
		$this->set('description', '');
		$this->set('notes', '');
		$this->set('head', '');
		$this->set('parent', null);	// containing parent
		$this->set('required', false); 
		$this->set('columnWidth', 0);
		$this->set('defaultValue', ''); 
		$this->set('level', 0); 
	}

	/**
	 * Set a value to the field
	 *
	 */
	public function set($key, $value) {
		if($key == 'name') {
			$value = preg_replace('/[^-_.a-zA-Z0-9]/', '_', $value); 
			if(substr($value, -4) !== '_END') $value = strtolower($value); 
		}
		return parent::set($key, $value); 
	}

	/**
	 * Get a value from this form field
 	 *
	 */
	public function get($key) {

		if($key == 'form') {
			// return the root parent (form)
			if($this->type == 'Form') return $this; 
			if($this->parent) return $this->parent->form; 
			return null;

		} else if($key == 'children') {
			return $this->children;

		} else if($key == 'numChildren') {
			return count($this->children); 

		} else if(isset($this->children[$key])) {
			return $this->children[$key];
		}

		return parent::get($key); 
	}

	/**
	 * Given an array of data, populate the data to this form field
	 *
	 * Recursively populate 'children' field when present
	 *
	 */
	public function setArray(array $data) {

		foreach($data as $key => $value) {

			if($key == 'children' && is_array($value)) {

				foreach($value as $name => $childData) {
					// convert each child in $value from array to object
					$child = new FormBuilderField();	
					$child->name = $name; 
					$child->setArray($childData); 
					$this->add($child); 
				}

			} else {
				$this->set($key, $value); 
			}
		}		
	}

	/**
	 * Return an array representing this field and children (when present)
	 *
	 * @return array
	 *
	 */	
	public function getArray() {

		// get $data from WireData
		$data = parent::getArray();

		// we don't need a 'name' in the return array
		// because the field name is the key
		if($data['parent']) unset($data['name']); 

		// remove fields that aren't needed in returns array
		// because they are already represented by the array structure
		unset($data['parent'], $data['form'], $data['level'], $data['id']); 

		// check if this field is a container for other fields (children)
		if(count($this->children)) {

			$children = array();

			foreach($this->children as $name => $child) {

				// use name defined with object, rather than key, in case it has changed
				$name = $child->name; 

				$children[$name] = $child->getArray(); 
			}
			$data['children'] = $children;
		}

		// remove any empty values for reduced storage requirements
		foreach($data as $key => $value) if(empty($value)) unset($data[$key]); 

		return $data; 	
	}

	/**
	 * Add a new child to this form/field
	 *
	 * @param FormBuilderField $child
	 * @return $this
	 *
	 */
	public function add(FormBuilderField $child) {

		// remove from old parent if it has one
		if($child->parent && $child->parent !== $this) $child->parent->remove($child); 

		// set new parent and level
		$child->parent = $this; 
		$child->level = $this->level+1; 

		// unset it first in case it's already set, so that it gets appended to the end
		unset($this->children[$child->name]); 
		unset(self::$allFields[$child->name]); 

		// now add it
		$this->children[$child->name] = $child; 
		self::$allFields[$child->name] = $child;

		return $this; 
	}

	/**
	 * Remove the given child from this form/field
	 *
	 * @param FormBuilderField|string The actual field or it's name
	 * @return $this
	 *
	 */
	public function remove($key) {
		if(is_string($key) && array_key_exists($key, $this->data)) return parent::remove($key);

		if($key instanceof FormBuilderField) $child = $key;
			else $child = $this->child($key);

		if($child) {

			// unset the child's parent
			$child->parent = null;

			// remove from our children array
			unset($this->children[$child->name]); 
			unset(self::$allFields[$child->name]); 
		}

		return $this; 
	}

	/**
	 * Return array of all children
	 *
	 * @return array
	 *
	 */
	public function children() {
		return $this->children; 
	}

	/**
	 * Return a flattened (non structured) array of all children
	 *
	 * Fieldset structure is instead represented by an opening fieldset which is 
	 * closed with a field of the same name with '_END' appended to it. 
	 *
	 * This function also sets a 'level' (integer) and 'parent' (FormBuilderField)
	 * to each child, for convenience. 
	 *
	 * @param array $children Optional children to flatten (internal recursion use)
	 * @param int $level Recursion level (internal recursion use)
	 * @return array
	 *
	 */
	public function getChildrenFlat($level = 0) {

		$children = array();

		foreach($this->children as $key => $child) {

			$child->level = $level;
			$child->parent = $this;  

			$children[$child->name] = $child;
			$numChildren = $child->numChildren;

			// check if there are children 
			// we also check for Fieldset in case it's an empty Fieldset
			if($numChildren || $child->type == 'Fieldset') {

				// append the children
				if($numChildren) foreach($child->getChildrenFlat($level+1) as $c) {
					$children[$c->name] = $c; 
				}

				// close the fieldset
				$end = new FormBuilderField();
				$name = $child->name . '_END';
				$end->name = $name; 
				$end->type = '';
				$end->level = $level;
				$children[$name] = $end; 
			}

		}

		return $children;
	}

	/**
	 * Return the direct child given by $name
	 *
	 * @param string $name
	 * @return FormBuilderField|null
	 *
	 */
	public function child($name) {
		if(isset($this->children[$name])) return $this->children[$name];
		return null;
	}

	/**
	 * Recursively find the field named $name
	 *
	 * @param string $name
	 * @return FormBuilderField|null
	 *
	 */
	public function find($name) {
		if(isset(self::$allFields[$name])) return self::$allFields[$name]; 	

		if(ctype_digit("$name")) {
			// name is ID
			foreach(self::$allFields as $child) {
				if($child->id == $name) return $child;
			}
		}

		return null;
	}

	public function __toString() {
		return $this->name; 
	}

}
