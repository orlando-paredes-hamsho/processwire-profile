<?php

/**
 * Implementation class for FormBuilder::getModuleConfigInputfields
 *
 * Provides the module configuration fields. This is delegated to it's own class 
 * since there is a lot here and didn't see any reason for it to take up space
 * in the FormBuilder class when it's only needed during config time. 
 *
 * Copyright (C) 2012 by Ryan Cramer Design, LLC
 * 
 * PLEASE DO NOT DISTRIBUTE
 * 
 */

class FormBuilderConfig extends Wire {

	/**
	 * Minimum ProcessWire version required to run Form Builder
	 *
	 */
	const requiredVersion = '2.5.3';

	protected $inputfields;
	protected $data = array();

	/**
	 * Markup used for form rendering
	 *
	 */
	protected $markup = array(
		'list' => "\n<div {attrs}>{out}\n</div>\n",
		'item' => "\n\t<div {attrs}>{out}\n\t</div>",   
		'item_label' => "\n\t\t<label class='ui-widget-header' for='{for}'>{out}</label>",   
		'item_content' => "\n\t\t<div class='ui-widget-content'>{out}</div>",   
		'item_error' => "\n<p><span class='ui-state-error'>{out}</span></p>",
		'item_description' => "\n<p class='description'>{out}</p>",   
		'success' => "\n<p class='ui-state-highlight'>{out}</p>",
		'error' => "\n<p class='ui-state-error'>{out}</p>", 
		//'item_head' => "\n<h2>{out}</h2>",   
		//'item_notes' => "\n<p class='notes'>{out}</p>",
		);

	/**
	 * Classes that are populated in markup (in some cases with others)
	 *
	 */
	protected $classes = array(
		'form' => '', 
		'list' => 'Inputfields',
		'list_clearfix' => 'ui-helper-clearfix',
		'item' => 'Inputfield Inputfield_{name} ui-widget {class}',
		'item_required' => 'InputfieldStateRequired',
		'item_error' => 'InputfieldStateError ui-state-error',
		'item_collapsed' => 'InputfieldStateCollapsed',
		'item_column_width' => 'InputfieldColumnWidth',
		'item_column_width_first' => 'InputfieldColumnWidthFirst',
		); 


	public function __construct(array $data) {

		// check for existance of required form-builder template
		if(!is_file($this->config->paths->templates . FormBuilderMain::name . '.php')) {
			$out = "Please copy " . $this->config->urls->FormBuilder . FormBuilderMain::name . ".php to " . 
				wire('config')->urls->templates . FormBuilderMain::name . '.php'; 
			$this->error($out);
		}

		$this->data = $data; 
		$this->inputfields = new InputfieldWrapper();
		$this->upgradeChecks();
	}

	public function getConfig() {

		// check that they have the required PW version
		if(version_compare(wire('config')->version, self::requiredVersion, '<')) {
			$this->error("Form Builder requires ProcessWire " . self::requiredVersion . " or newer. You need to update your ProcessWire version before using Form Builder."); 
		}

		$this->configLicense();
		$this->configInputfieldClasses();
		$this->configEmbedFields();
		$this->configEmbedTag();
		$this->configMarkup(); 
		$this->configAkismet();
		$this->configCsv();
		$this->configFilesPath();
		$this->configAccess();

		return $this->inputfields;
	}

	protected function configFilesPath() {

		$value = !empty($this->data['filesPath']) ? $this->data['filesPath'] : '{config.paths.cache}' . FormBuilderMain::name . '/'; 

		$f = wire('modules')->get('InputfieldText');
		$f->attr('name', 'filesPath');
		$f->attr('value', $value);
		$f->label = "Files Path";
		$f->description = "This is the directory where form builder will store files for forms that have file/upload fields. This directory must be writable by Apache. For security, this directory must NOT be web accessible!";
		$f->collapsed = Inputfield::collapsedYes; 
		$f->required = true; 

		$value = FormBuilderMain::parseFilesPath($value);

		$f->notes = 
			"The default location: /site/assets/cache/form-builder/, is safe if your htaccess file is working correctly. Some may still prefer to specify a path outside of your web root. You may optionally use {config.paths.something} tags to specify paths relative to ProcessWire. " . 
			"The current path translates to this at runtime: $value";

		if(!is_dir($value)) wireMkdir($value); 
		if(!is_dir($value)) $f->error("Specified path does not exist, please create it and make it writable."); 
		if(!is_writable($value)) $f->error("Specified path is not writable, please correct this.");

		$this->inputfields->add($f);
	}

	protected function configAkismet() {

		$akismetKey = isset($this->data['akismetKey']) ? $this->data['akismetKey'] : '';

		$f = wire('modules')->get('InputfieldText');
		$f->attr('name', 'akismetKey');
		$f->attr('value', $akismetKey);
		$f->label = 'Akismet API Key'; 
		$f->description = 'If you want to use the Akismet service to prevent spam, enter your API key here.';
		$f->notes = 'Get an [Akismet API key](https://akismet.com/signup/).';
		$f->collapsed = Inputfield::collapsedBlank;

		if(strlen($akismetKey)) {
			require_once(dirname(__FILE__) . '/FormBuilderAkismet.php');
			$akismet = new FormBuilderAkismet($akismetKey);
			if(!$akismet->verifyKey()) {
				$f->error('Akismet key does not verify'); 
			} else {
				$f->notes = 'Akismet key verified!';
				$f->label .= " (verified)";
				$f->collapsed = Inputfield::collapsedYes; 
			}
			
		}

		$this->inputfields->add($f);
	}

	protected function configInputfieldClasses() {

		$defaultInputfieldClasses = array(
			'AsmSelect',
			'Checkbox',
			'Checkboxes',
			'Datetime',
			'Email',
			'Fieldset',
			'Float',
			'FormBuilderFile',
			'Integer',
			'Hidden',
			'Page',
			'Radios',
			'Select',
			'SelectMultiple',
			'Text',
			'Textarea',
			'URL', 
			);

		// Inputfields that we already know are not FormBuilder compatible
		$excludeInputfieldClasses = array(
			'Name',
			'File',
			'Image',
			'Form',
			'Button',
			'Submit',
			'TinyMCE',
			'Repeater',
			);

		$f = wire('modules')->get('InputfieldAsmSelect'); 
		$f->label = 'Inputfield types to use with FormBuilder';
		$f->attr('name', 'inputfieldClasses'); 

		foreach(wire('modules')->find('className^=Inputfield') as $module) {
			$className = str_replace('Inputfield', '', $module->className());
			if(in_array($className, $excludeInputfieldClasses)) continue; 
			if($className != 'Page' && substr($className, 0, 4) == 'Page') continue; 
			$f->addOption($className);
		}

		$f->attr('value', empty($this->data['inputfieldClasses']) ? $defaultInputfieldClasses : $this->data['inputfieldClasses']); 
		$this->inputfields->add($f);
	}

	protected function configEmbedFields() {

		$f = wire('modules')->get('InputfieldCheckboxes'); 
		$f->attr('name', 'embedFields');
		$f->label = "Where can forms be embedded?";
		$f->description = "Check all fields that are allowed to have forms embedded in them using the easy-embedding method. Easy embedding enables you to type a special \"embed tag\" on it's own line and have the form automatically inserted there when the page is viewed. We highly recommend that you use easy embedding, however if you won't be using it, you may leave all unchecked.";
		$f->notes = "Tip: Choose your main 'body copy' field for easy embedding.";

		foreach(wire('fields') as $field) {
			if($field->type instanceof FieldtypeTextarea) $f->addOption($field->id, $field->name); 
		}

		$f->attr('value', isset($this->data['embedFields']) ? $this->data['embedFields'] : array()); 
		$this->inputfields->add($f);
	}

	protected function configEmbedTag() {

		$f = wire('modules')->get('InputfieldName');
		$f->attr('name', 'embedTag');
		$f->attr('value', !empty($this->data['embedTag']) ? $this->data['embedTag'] : FormBuilderMain::name); 
		$f->label = "Easy Embed Tag";
		$f->description = "A short tag or word (a-z 0-9) that Form Builder should look for in your text when determining when and where to embed a form. This should be something reasonably unique, not likely to appear in other contexts.";
		$f->notes = "Form Builder will look for this tag combined with your form name when performing embeds.";
		$f->collapsed = Inputfield::collapsedYes; 
		$this->inputfields->add($f);
	}

	protected function configCsv() {

		$f = wire('modules')->get('InputfieldText');
		$f->attr('name', 'csvDelimiter');
		$f->attr('value', !empty($this->data['csvDelimiter']) ? $this->data['csvDelimiter'] : ','); 
		$f->label = "CSV Delimiter";
		$f->description = "The delimiter to use when exporting CSV/spreadsheet files. Typically a comma, semicolon or tab.";
		$f->notes = "To use [tab] as a delimiter, just enter the letter: T"; 
		$f->attr('size', 3); 
		$f->attr('maxlength', 3);
		$f->collapsed = Inputfield::collapsedYes; 
		$this->inputfields->add($f);
	}

	protected function configAccess() {
		$f = wire('modules')->get('InputfieldCheckbox'); 
		$f->attr('name', 'useRoles');
		$f->attr('value', 1);
		$f->attr('checked', empty($this->data['useRoles']) ? '' : 'checked'); 
		$f->label = "Access Control";
		$f->description = "When checked, several form-level permissions will be provided to control access for submission, administration and entry management. These permissions can be managed from the 'access' tab of each form.";
		if(empty($this->data['useRoles'])) $f->collapsed = Inputfield::collapsedYes;
		$this->inputfields->add($f);
	}

	protected function configLicense() {

		$f = wire('modules')->get('InputfieldText');
		$f->attr('id+name', 'licenseKey');

		$licenseKey = isset($this->data['licenseKey']) ? $this->data['licenseKey'] : '';

		if(wire('input')->post->licenseKey && wire('input')->post->licenseKey != wire('session')->FormBuilderLicenseKey) {
			// validate 
			$http = new WireHttp();
			$license = wire('sanitizer')->text(wire('input')->post->licenseKey);
			$result = $http->post('http://processwire.com/FormBuilder/license.php', 
				array(
					'action' => 'validate', 
					'license' => $license,
					'host' => wire('config')->httpHost
					));

			if($result === 'valid') {
				$licenseKey = $license; 
				$f->notes = "Validated!";
				$this->message("License key has been validated!");

			} else if($result === 'invalid') {
				$licenseKey = '';
				$f->error("Invalid license key");

			} else {
				$licenseKey = '';
				$f->error("Unable to validate license key"); 
			}
		}

		if(empty($licenseKey)) {
			wire('input')->post->__unset('licenseKey'); 
			wire('input')->post->__unset('licenseKeyPrev'); 
		}

		$f->attr('value', $licenseKey);
		$f->required = true; 
		$f->label = "License Key";
		$f->attr('value', $licenseKey);
		$f->description = "Paste in your Form Builder license key.";
		$f->notes = "If you did not purchase Form Builder for this site, please [purchase a license here](http://processwire.com/FormBuilder/).";
		$this->inputfields->add($f);

		wire('session')->set('FormBuilderLicenseKey', $licenseKey);
	}

	protected function configMarkup() {

		$fieldsets = array(
			'markup' => 'Form Markup', 
			'classes' => 'Form Classes'
			); 

		foreach($fieldsets as $fieldsetName => $fieldsetLabel) {

			$fieldset = wire('modules')->get('InputfieldFieldset'); 
			$fieldset->label = $fieldsetLabel;
			$fieldset->description = $this->_('Please note: these configuration settings apply only to forms using the "Legacy" framework. We suggest leaving these settings as is.');

			$values = $this->$fieldsetName; 

			foreach($values as $key => $value) {

				$originalValue = $value; 
				$label = ucwords(str_replace('_', ' ', $key));
				$key2 = $fieldsetName . '_' . $key;

				preg_match_all('/(\{[^}]+\})/', $value, $matches); 
				$notes = '';
				foreach($matches[1] as $varName) $notes .= " $varName";
				if($notes) $notes = __('Possible variables:') . $notes; 

				if(strpos($value, "\n") !== false) $f = wire('modules')->get('InputfieldTextarea'); 
					else $f = wire('modules')->get('InputfieldText'); 

				if(array_key_exists($key2, $this->data)) $value = $this->data[$key2]; 
				if(empty($value)) $value = $originalValue; 
				

				$f->attr('name', $key2);
				$f->label = $label;
				$f->notes = $notes; 
				$value = str_replace("\t", '', $value); 
				$f->attr('value', trim($value)); 
				$fieldset->add($f); 
			}

			$fieldset->collapsed = Inputfield::collapsedYes; 
			$fieldset->description = $this->_('Avoid changing these unless you know what you are doing, because it may break the Form Builder output'); 

			$this->inputfields->add($fieldset);
		}

	}

	/**
	 * Install upgrades as needed
	 *
	 */
	protected function upgradeChecks() {
		// 0.1.8 check if we need to perform upgrade to install permissions
		$permission = $this->permissions->get('form-builder-add');
		if(!$permission || !$permission->id) {
			$this->message("Installing Form Builder permissions upgrade. Your forms are now access protected. Please check the new 'access' tab for each of your forms and confirm the settings are how you want them."); 
			include_once(dirname(__FILE__) . '/FormBuilderInstall.php'); 
			$install = new FormBuilderInstall();
			$install->installPermissions();
		}
	}

}
