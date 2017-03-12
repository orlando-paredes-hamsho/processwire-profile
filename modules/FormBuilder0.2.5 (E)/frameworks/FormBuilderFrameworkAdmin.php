<?php

/**
 * FormBuilder Admin framework initialization file
 * 
 */

class FormBuilderFrameworkAdmin extends FormBuilderFramework {
	
	public function load() {
		
		$config = $this->wire('config');
		$config->inputfieldColumnWidthSpacing = 0; // percent spacing between columns

		$markup = InputfieldWrapper::getMarkup();
		$markup['item_label'] = "\n\t\t<label class='InputfieldHeader' for='{for}'>{out}</label>";
		$markup['item_label_hidden'] = "\n\t\t<label class='InputfieldHeader InputfieldHeaderHidden'><span>{out}</span></label>";
		$markup['item_content'] = "\n\t\t<div class='InputfieldContent'>\n{out}\n\t\t</div>";
		$markup['success'] = "<p class='NoticeMessage'>&nbsp;{out}</p>";
		$markup['error'] = "<p class='NoticeError'>&nbsp;{out}</p>";
		InputfieldWrapper::setMarkup($markup);

		$classes = InputfieldWrapper::getClasses();
		$classes['item'] = "Inputfield {class} Inputfield_{name}";
		$classes['item_error'] = "InputfieldStateError";
		InputfieldWrapper::setClasses($classes);
		
		// $config->styles->append($config->urls->FormBuilder . 'FormBuilder.css');
		$config->styles->append($config->urls->modules . 'AdminTheme/' . $this->styleSet);

		$this->form->theme = '';

	}

	/**
	 * Return Inputfields for configuration of framework
	 *
	 * @return InputfieldWrapper
	 *
	 */
	public function getConfigInputfields() {
		$inputfields = parent::getConfigInputfields();
	
		$inputfields->description = $this->_('This theme uses stylesheets from ProcessWire admin theme modules. Because it includes stylesheets that go beyond the scope of the form, it is not recommended when using embed mode C.'); 
		$f = $this->wire('modules')->get('InputfieldRadios');
		$f->attr('name', 'styleSet'); 
		$f->label = $this->_('Style Set'); 

		$path = $this->wire('config')->paths->modules . 'AdminTheme/';
		foreach(new DirectoryIterator($path) as $dir) {
			if($dir->isDot() || !$dir->isDir()) continue; 
			$_path = $path . $dir->getBasename() . "/styles/";
			if(is_dir($_path)) foreach(new DirectoryIterator($_path) as $file) {
				if($file->isDir() || $file->isDot() || $file->getExtension() != 'css') continue;
				if($file->getBasename() == 'install.css') continue; 
				$value = $dir->getBasename() . "/styles/" . $file->getBasename();
				$label = str_replace('AdminTheme', '', $dir->getBasename()) . "/" . $file->getBasename(); 
				$f->addOption($value, $label);
			}
		}
		$f->attr('value', $this->styleSet); 
		$inputfields->add($f); 
		
		$f = $inputfields->getChildByName('noLoad'); 
		$f->removeOption('framework'); 
		
		return $inputfields;
	}
	
	public function getConfigDefaults() {
		return array(
			'styleSet' => 'AdminThemeDefault/styles/main-classic.css',
		);
	}

	public function getFrameworkURL() {
		return $this->wire('config')->urls->modules . 'AdminTheme/';
	}

}

