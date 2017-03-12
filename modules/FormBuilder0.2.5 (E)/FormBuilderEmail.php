<?php

/**
 * ProcessWire Form Builder Email
 *
 * Handles the emailing of Inputfields 
 *
 * Copyright (C) 2012 by Ryan Cramer Design, LLC
 *
 * PLEASE DO NOT DISTRIBUTE
 *
 * @todo: add option for text-based email
 * @todo: add reply-to support
 *
 */

class FormBuilderEmail extends FormBuilderData {

	/**
	 * Instance of InputfieldForm
	 *
	 */
	protected $form = null;

	/**
	 * List of field names that should not be included in the email
	 *
	 */
	protected $skipFieldNames = array();

	/**
	 * List of field types that should not be included in the email
	 *
	 */
	protected $skipFieldTypes = array(
		'InputfieldSubmit',
		);

	/**
	 * Raw processed form data
	 * 
	 * @var array
	 * 
	 */
	protected $rawFormData = array();

	/**
	 * Construct the FormBuilderEmail
	 *
	 * @param InputfieldForm $form
	 *
	 */
	public function __construct(InputfieldForm $form) {
		$this->form = $form;
		$this->set('to', ''); // required, may contain multiple lines/emails or conditions
		$this->set('from', ''); // optional, email address or field name to pull it from
		$this->set('from2', ''); // optional, backup email address 
		$this->set('subject', ''); // optional
		$this->set('body', ''); // optional, appears above form data
	}
	
	public function setRawFormData(array $data) {
		$this->rawFormData = $data; 
	}

	protected function matches($value1, $operator, $value2) {
		$matches = false;

		$values = $value1; 
		if(!is_array($values)) $values = array($values); 

		foreach($values as $value1) {
			switch($operator) {
				case '==':
				case '=': if($value1 == $value2) $matches = true; break;
				case '>': if($value1 > $value2) $matches = true; break;
				case '<': if($value1 < $value2) $matches = true; break;
				case '>=': if($value1 >= $value2) $matches = true; break;
				case '<=': if($value1 <= $value2) $matches = true; break;
				case '*=': if(strpos($value2, $value1) !== false) $matches = true; break;
				case '!=': if($value1 != $value2) $matches = true; break;
			}
			if($matches) break;
		}
		return $matches; 
	}

	/**
	 * Takes a list of email addresses, one per line and optionally including conditions, and converts them to an array of email addresses
	 *
	 * Conditional example:
	 * 	frontdesk@company.com (always gets emailed)
	 * 	inquiry_type=Sales? sales@company.com (gets emailed only when inquiry_type is 'Sales')
	 * 	inquiry_type=Support? help@company.com (gets emailed only when inquiry_type is 'Support')
	 *
	 * @param string $str Email addresses in a line separated string
	 * @return array
	 *
	 */
	public function emailsToArray($str) {

		$emails = array();

		foreach(explode("\n", $str) as $line) {

			$line = trim($line);

			if(strpos($line, '?') !== false) {
				// conditional address
				// VARIABLES:     1:field name        2:operator         3:value    4:email
				if(!preg_match('/^([-_.a-zA-Z0-9]+)\s*(=|==|>|<|>=|<=|\*=|!=)([^\?]*)\?\s*(.*)$/', $line, $matches)) continue; 

				$field = $matches[1];
				$subfield = '';
				if(strpos($field, '.') !== false) list($field, $subfield) = explode('.', $field);
				$operator = $matches[2];
				$requireValue = $matches[3];
				$addrs = explode(',', $matches[4]); // one email or optional multiple CSV string of emails
				if(!count($addrs)) continue; // invalid email address
				$inputfield = $this->form->get($field);
				if(!$inputfield) continue; // inputfield does not exist
				$inputValue = $inputfield->attr('value'); 

				// pull subfield value from an object, typically a $page
				if(is_object($inputValue) && $subfield) $inputValue = $inputValue->$subfield;

				if(!$this->matches($inputValue, $operator, $requireValue)) continue; // condition does not match

				// condition matches
				foreach($addrs as $email) $emails[] = $email;

			} else if(strpos($line, ',') !== false) {
				// multiple addresses on 1 line
				foreach(explode(',', $line) as $email) $emails[] = $email;

			} else {
				// just an email address
				$emails[] = $line;
			}
		}

		// sanitize and validate all found emails
		foreach($emails as $key => $email) {
			$email = wire('sanitizer')->email($email);
			if(!strlen($email)) unset($emails[$key]); 
				else $emails[$key] = $email;
		}

		return $emails; 
	}

	/**
	 * Send the given $form to the email address
	 *
	 * @param string $template Name of email template to use
	 * @return bool
	 *
	 */
	public function send($template = 'email') {

		$to = $this->emailsToArray($this->to);

		// no addresses to send to
		if(!count($to)) return false; 

		$from = $this->getFromEmail();
		$subject = wire('sanitizer')->text($this->subject);

		// autogenerate an email subject if not provided
		if(!$subject) $subject = sprintf($this->_('%s form submission'), $this->form->name); 

		$body = $this->renderBody($template);

		if(function_exists('wireMail')) {
			$result = wireMail($to, $from, $subject, array('body' => strip_tags($body), 'bodyHTML' => $body));
		} else {
			$headers = array('MIME-Version: 1.0', 'Content-Type: text/html; charset=utf-8'); 
			if(strlen($from)) $headers[] = "From: $from";
			$params = wire('config')->phpMailAdditionalParameters; 
			if(!$params) $params = '';

			foreach($to as $email) {
				$result = @mail($email, $subject, $body, implode("\r\n", $headers), $params); 
			}

		}

		return $result;	
	}

	/**
	 * Get the email 'from' address, which may be pulled from a field name
	 *
	 * @return string
	 *
	 */
	protected function getFromEmail() {

		if(!strlen($this->from)) return $this->from2;

		if(strpos($this->from, '@')) {
			// email address
			$from = wire('sanitizer')->email($this->from);
		} else {
			// field name
			$field = $this->form->getChildByName(wire('sanitizer')->fieldName($this->from)); 	
			if($field) $from = wire('sanitizer')->email($field->attr('value')); 
				else $from = '';
		}

		if(!strlen($from)) $from = $this->from2;

		return $from;
	}

	/**
	 * Render the body/message portion of an email with the form results
	 *
	 * Note: inline styles are used since many email clients (like gmail) won't work without them.
	 *
	 * @param string $template Name of email template to use
	 * @return string
	 *
	 */
	protected function renderBody($template) {

		$values = array();
		$labels = array();

		foreach($this->form->getAll() as $f) {

			$skip = false;
			foreach($this->skipFieldTypes as $type) if($f instanceof $type) $skip = true; 
			if($skip) continue; 
			if(in_array($f->name, $this->skipFieldNames)) continue; 

			$value = $f->renderValue();

			// now we convert lists to newlines if the value changes when we do a replacement
			$len = strlen($value);
			$value = str_replace(array('<ul>', '<li>', '</ul>', '</li>'), array('', '', '', "\n"), $value);
			if($len !== strlen($value)) $value = nl2br($value);

			$values[$f->name] = trim($value); 
			$labels[$f->name] = htmlentities($f->label, ENT_QUOTES, 'UTF-8');
		}

		// 1. first try /site/templates/FormBuilder/[template]-[form].php
		$filename = wire('config')->paths->templates . "FormBuilder/$template-{$this->form->name}.php"; 

		// 2. next try /site/templates/FormBuilder/[template].php
		if(!is_file($filename)) $filename = wire('config')->paths->templates . "FormBuilder/$template.php"; 

		// 3. otherwise, use the predefined one in /site/modules/FormBuilder/[template].php
		if(!is_file($filename)) $filename = dirname(__FILE__) . "/$template.php"; 

		$t = new TemplateFile($filename); 
		$t->set('values', $values); 
		$t->set('labels', $labels); 
		$t->set('body', $this->populateTags($this->body)); 
		$t->set('subject', $this->subject); 
		$t->set('form', $this->form);
		$t->set('formData', $this->rawFormData); 
		
		return $t->render();
	}

	/**
	 * Convert form field [field_name] tags to values in body
	 *
	 */
	protected function populateTags($body) {
		if(strpos($body, '[') === false) return $body;
		if(!preg_match_all('/\[([_.a-zA-Z0-9]+)\]/', $body, $matches)) return $body;
		foreach($matches[1] as $key => $fieldName) {
			$field = $this->form->get($fieldName); 	
			if(!$field || !$field instanceof Inputfield) continue; 
			$value = $field->renderValue();
			$value = str_replace("</li>", ", ", $value); 
			$value = trim(strip_tags($value), ", "); 
			$body = str_replace($matches[0][$key], $value, $body); 
		}
		return $body; 
	}

	/**
	 * Set a field name that should be skipped
	 *
	 */
	public function setSkipFieldName($fieldName) {
		$this->skipFieldNames[] = $fieldName;
		return $this;
	}

	/**
	 * Set a field type that should be skipped
	 *
 	 */
	public function setSkipFieldType($fieldType) {
		$this->skipFieldTypes[] = $fieldType;
		return $this;
	}

}
