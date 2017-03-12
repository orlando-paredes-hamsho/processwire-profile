<?php

/**
 * FormBuilder uikit framework definition file
 *
 */

class FormBuilderFrameworkUikit extends FormBuilderFramework {
	
	public function load() {
		
		$markup = array(
			'list' => "<div {attrs}>{out}</div>",
			'item' => "<div {attrs}>{out}</div>",
			'item_label' => "<label class='InputfieldHeader uk-form-label' for='{for}'>{out}</label>",
			'item_label_hidden' => "<label class='InputfieldHeader InputfieldHeaderHidden'><span>{out}</span></label>",
			'item_content' => "<div class='InputfieldContent uk-form-controls {class}'>{out}</div>",
			'item_error' => "<p class='uk-text-danger uk-margin-bottom-remove uk-margin-small'>{out}</p>",
			'item_description' => "<p class='uk-form-help-block uk-text-muted uk-margin-small'>{out}</p>",
			'item_notes' => "<p class='uk-form-help-block uk-text-small uk-text-muted uk-margin-small'>{out}</p>",
			'success' => "<p class='uk-alert uk-alert-success'>{out}</p>",
			'error' => "<p class='uk-alert uk-alert-danger'>{out}</p>",
			'item_icon' => "",
			'item_toggle' => "",
			'InputfieldFieldset' => array(
				'item' => "<fieldset {attrs}>{out}</fieldset>",
				'item_label' => "<legend>{out}</legend>",
				'item_label_hidden' => "<legend>{out}</legend>",
				'item_content' => "<div class='InputfieldContent'>{out}</div>",
				'item_description' => "<p class='uk-text-muted'>{out}</p>",
			)
		);

		$classes = array(
			'form' => 'uk-form InputfieldFormNoHeights',
			'list' => 'Inputfields',
			'list_clearfix' => 'uk-clearfix',
			'item' => 'uk-form-row Inputfield Inputfield_{name} {class}',
			'item_required' => 'InputfieldStateRequired',
			'item_error' => 'InputfieldStateError',
			'item_collapsed' => 'InputfieldStateCollapsed',
			'item_column_width' => 'InputfieldColumnWidth',
			'item_column_width_first' => 'InputfieldColumnWidthFirst',
			'InputfieldCheckboxes' => array('item_content' => 'uk-form-controls-text'),
			'InputfieldCheckbox' => array('item_content' => 'uk-form-controls-text'),
			'InputfieldRadios' => array('item_content' => 'uk-form-controls-text'),
			'InputfieldFieldset' => array(
				'item' => 'Inputfield_{name} {class}',
			)
		);

		if((int) $this->horizontal) {
			// for uk-form-horizontal
			$classes['form'] .= " uk-form-horizontal InputfieldFormNoWidths";
			$markup['item_content'] = "<div class='InputfieldContent uk-form-controls {class}'>{out}{description}{notes}</div>";
			
			// the following duplicates the styles from uikit.css, but uses our custom widths
			// this is necessary because uikit only supports horizontal forms at 960px and above
			// and the width (200px) is fixed, when we need variable/user definable width.
			$mobilePx = $this->form->mobilePx; 
			if(ctype_digit("$mobilePx")) $mobilePx = ((int) $mobilePx) . "px";
			if($mobilePx != '1px') $this->addInlineStyles("
				@media (min-width: $mobilePx) {
					.InputfieldForm.uk-form-horizontal .uk-form-label {
						display: block;
						margin-bottom: 5px;
						font-weight: bold;
					}
				}
				@media (min-width: $mobilePx) {
					.InputfieldForm.uk-form-horizontal .uk-form-label {
						width: {$this->horizHeaderWidth}%; 
						margin-top: 5px;
						float: left; 

					}
					.InputfieldForm.uk-form-horizontal .uk-form-controls {
						margin-left: {$this->horizHeaderWidth}%; 
						padding-left: 1em;
					}
					.InputfieldForm.uk-form-horizontal .uk-form-controls-text {
						padding-top: 5px;
					}
				}
				");
		} else {
			$classes['form'] .= " uk-form-stacked";
		}

		InputfieldWrapper::setMarkup($markup);
		InputfieldWrapper::setClasses($classes);

		$ukURL = $this->ukURL;
		if(strpos($ukURL, '//') !== false) {
			$ukURL = rtrim($ukURL, '/');
		} else {
			$ukURL = $this->wire('config')->urls->root . trim($ukURL, '/');
		}

		$config = $this->wire('config');
		$css = $this->css;
		if(!$css) $css = 'uikit.gradient.min.css';
		$ukTheme = str_replace('uikit.', '', $css); 
		
		if($this->allowLoad('framework')) {
			$config->styles->prepend("$ukURL/css/$css");
			$config->scripts->append("$ukURL/js/uikit.min.js");
			if($this->allowLoad('uk-form-advanced', false)) $config->styles->append("$ukURL/css/components/form-advanced.$ukTheme");
			if($this->allowLoad('uk-form-select', false)) {
				$config->styles->append("$ukURL/css/components/form-select.$ukTheme");
				$config->scripts->append("$ukURL/js/components/form-select.min.js");
			}
		}
		$config->styles->append($config->urls->FormBuilder . 'FormBuilder.css');
		$config->inputfieldColumnWidthSpacing = 0;

		// load custom theme stylesheets, where found
		if(!$this->form->theme) $this->form->theme = 'delta';

		// chanage markup of submit button
		$this->addHookBefore('InputfieldSubmit::render', $this, 'hookInputfieldSubmitRender'); 
		
		if($this->inputSize) $this->addHookBefore('FormBuilderProcessor::renderReady', $this, 'hookBeforeRenderReady'); 
		// $this->addHookAfter('FormBuilderProcessor::renderReady', $this, 'hookAfterRenderReady'); 
	}
	
	public function hookBeforeRenderReady($event) {
		$inputfields = $event->arguments(0); 
		$class = $this->inputSize; 
		if(!$class) return;
		foreach($inputfields->getAll() as $in) {
			$in->addClass("uk-form-" . $this->inputSize); 
		}
	}

	/*
	public function hookAfterRenderReady($event) {
		if(strpos($event->return, ' fa-') !== false) {
			$event->return = str_replace(array('fa fa-fw fa-', 'fa fa-'), 'uk-icon-', $event->return); 
		}
	}
	*/
	
	public function hookInputfieldSubmitRender($event) {
		$in = $event->object;
		$event->replace = true;
		$classes = array('uk-button'); 
		if($this->buttonSize) $classes[] = "uk-button-$this->buttonSize";
		if($this->buttonType) $classes[] = "uk-button-$this->buttonType";
		if($this->buttonFull) $classes[] = "uk-width-1-1";
		$class = implode(' ', $classes); 
		$event->return = "<button type='submit' name='$in->name' value='$in->value' class='$class'>$in->value</button>";
	}

	/**
	 * Return Inputfields for configuration of framework
	 *
	 * @return InputfieldWrapper
	 *
	 */
	public function getConfigInputfields() {
		$inputfields = parent::getConfigInputfields();
		$defaults = self::getConfigDefaults();
		$defaultLabel = $this->_('Default value:') . ' ';
		
		$f = $inputfields->getChildByName('noLoad'); 
		$f->addOption('uk-form-advanced', $this->_('Do not load Uikit form advanced customizations'));
		$f->addOption('uk-form-select', $this->_('Do not load Uikit form select customizations'));

		$f = $this->wire('modules')->get('InputfieldURL');
		$f->attr('name', 'ukURL');
		$f->label = $this->_('URL to Uikit framework');
		$f->description = $this->_('Specify a URL/path relative to root of ProcessWire installation.');
		$f->attr('value', $this->ukURL);
		if($this->ukURL != $defaults['ukURL']) $f->notes = $defaultLabel . $defaults['ukURL'];
		$inputfields->add($f);

		$f = $this->wire('modules')->get('InputfieldRadios');
		$f->attr('name', 'css');
		$f->label = $this->_('Uikit CSS theme file'); 
		
		$_ukPath = $this->wire('forms')->frameworksPath() . 'uikit/css/';
		if(strpos($this->ukURL, '//') !== false) {
			// http URL, we can't identify CSS files there, so use our default 
			$ukPath = $_ukPath; 
		} else {
			$ukPath = $this->wire('config')->paths->root . trim($this->ukURL, '/') . '/css/';
			if(!is_dir($ukPath)) {
				$f->error("Unable to locate path: $ukPath"); 			
				$ukPath = $_ukPath; 
			}
		}

		foreach(new DirectoryIterator($ukPath) as $file) {
			if($file->isDir() || $file->isDot() || $file->getExtension() != 'css') continue;
			$f->addOption($file->getBasename());
		}
		$f->attr('value', $this->css);
		$f->columnWidth = 33;
		$inputfields->add($f);
		
		$f = $this->wire('modules')->get('InputfieldRadios');
		$f->attr('name', 'horizontal');
		$f->label = $this->_('Form style');
		$f->addOption(0, $this->_('Stacked (default)'));
		$f->addOption(1, $this->_('Horizontal (2-column)'));
		$f->attr('value', $this->horizontal);
		$f->optionColumns = 1; 
		$f->description = $this->_('Please note that individual field column widths (if used) are not applicable when using the *Horizontal* style.'); 
		$inputfields->add($f);
		
		$f = $this->wire('modules')->get('InputfieldInteger'); 
		$f->attr('name', 'horizHeaderWidth');
		$f->label = $this->_('Percent width for label columns (horizontal style only)'); 
		$f->description = $this->_('Specify a value between 5% and 90% percent to determine the width of the label column. The input column will have the remaining percent, i.e. if you specify 30% here, the label column will have 30% width and the input column will have 70% width.');
		$f->min = 5;
		$f->max = 90; 
		$f->attr('value', $this->horizHeaderWidth); 
		$f->showIf = 'frUikit_horizontal=1';
		$inputfields->add($f);
		
		$f = $this->wire('modules')->get('InputfieldRadios');
		$f->attr('name', 'inputSize');
		$f->label = $this->_('Input size');
		$f->addOption('small', $this->_('Small'));
		$f->addOption('', $this->_x('Normal', 'sizeType'));
		$f->addOption('large', $this->_('Large'));
		$f->attr('value', $this->inputSize);
		$f->columnWidth = 34;
		$inputfields->add($f);

		$f = $this->wire('modules')->get('InputfieldRadios'); 
		$f->attr('name', 'buttonType'); 
		$f->label = $this->_('Submit button type'); 
		$f->addOption('', $this->_x('Default', 'buttonType'));
		$f->addOption('primary', 'Primary');
		$f->addOption('success', $this->_('Success'));
		$f->addOption('danger', $this->_('Danger'));
		$f->attr('value', $this->buttonType); 
		$f->columnWidth = 33; 
		$inputfields->add($f); 

		$f = $this->wire('modules')->get('InputfieldRadios');
		$f->attr('name', 'buttonSize');
		$f->label = $this->_('Submit button size'); 
		$f->addOption('mini', $this->_('Mini'));
		$f->addOption('small', $this->_('Small'));
		$f->addOption('', $this->_('Medium')); 
		$f->addOption('large', $this->_('Large'));
		$f->attr('value', $this->buttonSize);
		$f->columnWidth = 33; 
		$inputfields->add($f); 
	
		$f = $this->wire('modules')->get('InputfieldCheckbox'); 
		$f->attr('name', 'buttonFull'); 
		$f->label = $this->_('Full width button?'); 
		if($this->buttonFull) $f->attr('checked', 'checked');
		$f->columnWidth = 34; 
		$inputfields->add($f); 
		
		return $inputfields;
	}
	
	public function getConfigDefaults() {
		$urls = $this->wire('config')->urls;
		$ukURL = str_replace($urls->root, '/', $urls->FormBuilder . 'frameworks/uikit/');
		return array_merge(parent::getConfigDefaults(), array(
			'ukURL' => $ukURL, 
			'horizontal' => 0,
			'horizHeaderWidth' => 30, 
			'css' => 'uikit.gradient.min.css', 
			'inputSize' => '', 
			'buttonType' => '', 
			'buttonSize' => '', 
			'buttonFull' => 0, 
		));
	}
	
	public function getFrameworkURL() {
		return $this->ukURL;
	}

}
