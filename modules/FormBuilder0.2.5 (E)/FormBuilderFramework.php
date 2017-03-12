<?php

abstract class FormBuilderFramework extends WireData {
	
	protected $form;
	protected $styles = array();
	protected $scripts = array();
	protected $inlineStyles = '';
	
	public function __construct(FormBuilderForm $form) {
		$this->form = $form; 
		$this->set('noLoad', array());
		foreach($this->getConfigDefaults() as $key => $value) {
			$this->set($key, $value); 
		}
	}
	
	public function ready() {
		if($this->allowLoad('jquery')) {
			$this->wire('modules')->get('JqueryCore')->use('latest');
		}
	}
	
	abstract public function load();
	
	public function getName() {
		return str_replace('FormBuilderFramework', '', $this->className());
	}

	public function getPrefix() {
		$prefix = "fr" . $this->getName() . '_';
		return $prefix; 
	}
	
	public function getForm() {
		return $this->form; 
	}
	
	public function getInlineStyles() {
		return $this->inlineStyles;
	}
	
	public function addInlineStyles($str) {
		$this->inlineStyles .= $str;
	}
	
	/**
	 * Get the URL where the actual 3rd party framework files exist
	 * 
	 * @return string
	 * 
	 */
	abstract public function getFrameworkURL();

	/**
	 * Return Inputfields for configuration of framework
	 * 
	 * @return InputfieldFieldset
	 * 
	 */
	public function getConfigInputfields() {
		
		$inputfields = $this->wire('modules')->get('InputfieldFieldset'); 
		$label = str_replace('FormBuilderFramework', '', $this->className()); 
		$inputfields->label = sprintf($this->_('Framework: %s'), $label); 
		$inputfields->description = sprintf($this->_('Configuration specific to the %s framework.'), $label); 
		
		$f = $this->wire('modules')->get('InputfieldCheckboxes'); 
		$f->attr('name', 'noLoad'); 
		$f->label = $this->_('Bypass automatic file loading'); 
		$f->description = $this->_('If your site already loads any of these assets, you can tell this form not to load them automatically by checking the appropriate boxes below.');
		$f->addOption('framework', sprintf($this->_('Do not load %s framework files*'), $this->getName()));
		$f->addOption('jquery', $this->_('Do not load jQuery*'));
		$f->addOption('jqueryui', $this->_('Do not load jQuery UI when requested by input field*'));
		$f->notes = $this->_('*This option is only applicable to embed method C.'); 
		$f->attr('value', $this->noLoad); 
		$f->collapsed = Inputfield::collapsedBlank;
		$inputfields->add($f); 
		
		return $inputfields; 
	}

	/**
	 * Allow loading of files for: jquery, jqueryui, or framework
	 * 
	 * @param $type
	 * @param bool $alwaysAllowAB Whether to always allowLoad for embed mode A and B (which use form-builder.php template file)
	 * @return bool
	 * 
	 */
	public function allowLoad($type, $alwaysAllowAB = true) {
		if(!in_array($type, $this->noLoad)) return true; 
		if($alwaysAllowAB && $this->wire('page')->template == 'form-builder') return true; // always required
		return false;
	}

	/**
	 * Return array of property => value representing defaults for each config property
	 * 
	 * @return array
	 * 
	 */
	public function getConfigDefaults() {
		return array(
			'noLoad' => array()
		);
	}
	
}