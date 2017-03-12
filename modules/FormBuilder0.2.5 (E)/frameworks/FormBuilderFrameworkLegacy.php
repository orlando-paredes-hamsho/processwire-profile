<?php

/**
 * FormBuilder Legacy framework initialization file
 * 
 */

class FormBuilderFrameworkLegacy extends FormBuilderFramework {
	
	public function load() {
		
		$config = $this->wire('config');
		$adminTemplates = $config->urls->adminTemplates;
		
		$config->inputfieldColumnWidthSpacing = 1;
		
		$config->styles->prepend($adminTemplates . 'styles/reset.css');
		$config->styles->append($adminTemplates . "styles/inputfields.css");
		$config->styles->append($config->urls->FormBuilder . 'form-builder.css');

		if(!$this->form->theme) $this->form->theme = 'default';

		// legacy framework uses markup defined in FormBuilder module settings

		$markup = array();
		$markupKeys = InputfieldWrapper::getMarkup();
		if(!isset($markupKeys['success'])) $markupKeys['success'] = '';
		if(!isset($markupKeys['error'])) $markupKeys['error'] = '';
		foreach($markupKeys as $key => $value) {
			$k = 'markup_' . $key;
			$value = wire('forms')->$k;
			if(!empty($value)) $markup[$key] = $value;
		}
		InputfieldWrapper::setMarkup($markup);

		$classes = array();
		foreach(InputfieldWrapper::getClasses() as $key => $value) {
			$k = 'classes_' . $key;
			$value = wire('forms')->$k;
			if(!empty($value)) $classes[$key] = $value;
		}
		InputfieldWrapper::setClasses($classes);

		if($this->wire('forms')->classes_form) {
			$this->addHookBefore('FormBuilderProcessor::renderReady', $this, 'addFormClass'); 
		}


	}

	/**
	 * Hook that adds a module configured form class (classes_form) to the InputfieldForm
	 * 
	 * @param $event
	 * 
	 */
	public function addFormClass($event) {
		$class = $this->wire('forms')->classes_form;
		if(!empty($class)) {
			$inputfieldForm = $event->arguments(0);
			$inputfieldForm->addClass($class);
		}
	}

	/**
	 * Return Inputfields for configuration of framework
	 *
	 * @return InputfieldWrapper
	 *
	 */
	public function getConfigInputfields() {
		$inputfields = parent::getConfigInputfields();
		$f = $inputfields->getChildByName('noLoad');
		$f->removeOption('framework');
		return $inputfields;
	}
	
	public function getFrameworkURL() {
		return $this->wire('config')->urls->adminTemplates;
	}

}

