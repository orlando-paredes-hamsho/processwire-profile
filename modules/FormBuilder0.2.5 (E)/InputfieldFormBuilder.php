<?php

/**
 * Interface for an Inputfield module specific to use with Form Builder
 *
 * Inputfields that implement this interface will have the following values 
 * set to their set() method: 
 *
 * formID - ID of the FormBuilderForm
 * entryID - ID of the associated entry, if applicable
 * processor - Instance of FormBuilderProcessor that is processing the form.
 *
 */

interface InputfieldFormBuilderInterface {
	public function __construct();
}

/**
 * Optional starter class that implements the above interface
 *
 */
abstract class InputfieldFormBuilder extends Inputfield implements InputfieldFormBuilderInterface {
	public function __construct() {
		parent::__construct();
		$this->set('formID', 0);
		$this->set('entryID', 0);
		$this->set('processor', null);
	}
}
