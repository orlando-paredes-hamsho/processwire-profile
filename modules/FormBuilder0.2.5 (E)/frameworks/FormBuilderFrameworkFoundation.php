<?php

/**
 * FormBuilder Foundation framework initialization file
 *
 */

class FormBuilderFrameworkFoundation extends FormBuilderFramework {
	
	public function load() {
		
		$markup = array(
			'list' => "<div {attrs}>{out}</div>",
			'item' => "<div {attrs}>{out}</div>",
			'item_label' => "<label class='InputfieldHeader' for='{for}'><strong>{out}</strong></label>",
			'item_label_hidden' => "<label class='InputfieldHeader InputfieldHeaderHidden'><span>{out}</span></label>",
			'item_content' => "<div class='InputfieldContent {class}'>{description}{out}{error}{notes}</div>",
			'item_error' => "<small class='error'>{out}</small>",
			'item_description' => "<p class='description'><label>{out}</label></p>",
			'item_notes' => "<p class='notes'><label><small>{out}</small></label></p>",
			'success' => "<div data-alert class='alert-box success'>{out}</div>",
			'error' => "<div data-alert class='alert-box alert'>{out}</div>",
			'item_icon' => "",
			'item_toggle' => "",
			'InputfieldFieldset' => array(
				'item' => "<fieldset {attrs}>{out}</fieldset>",
				'item_label' => "<legend>{out}</legend>",
				'item_label_hidden' => "<legend class='hide'>{out}</legend>",
				'item_content' => "<div class='InputfieldContent'>{out}</div>",
				'item_description' => "<p class='fieldset-description'><label>{out}</label></p>",
				'item_notes' => "<p class='notes'><small>{out}</small></p>",
			)
		);

		$classes = array(
			'form' => 'InputfieldFormNoHeights',
			'list' => 'Inputfields',
			'list_clearfix' => 'clearfix',
			'item' => 'Inputfield Inputfield_{name} {class}',
			'item_required' => 'InputfieldStateRequired',
			'item_error' => 'InputfieldStateError',
			'item_collapsed' => 'InputfieldStateCollapsed',
			'item_column_width' => 'InputfieldColumnWidth',
			'item_column_width_first' => 'InputfieldColumnWidthFirst',
			'InputfieldFieldset' => array(
				'item' => 'Inputfield_{name} {class}',
			)
		);

		if((int) $this->horizontal) {
			$headerClass = $this->wire('sanitizer')->entities($this->horizHeaderClass) . ' columns';
			$contentClass = $this->wire('sanitizer')->entities($this->horizContentClass) . ' columns';
			$markup['item_label'] = "<label class='InputfieldHeader $headerClass'><strong>{out}</strong></label>";
			$markup['item_label_hidden'] = "<label class='InputfieldHeader InputfieldHeaderHidden $headerClass'><span>{out}</span></label>";
			$markup['item_content'] = "<div class='InputfieldContent $contentClass{class}'>{out}{error}{description}{notes}</div>";
			$markup['InputfieldSubmit'] = array(
				'item_content' => "<div class='$headerClass'></div><div class='InputfieldContent $contentClass {class}'>{out}</div>",
			);
			$classes['item'] .= " row";
			$classes['form'] .= " InputfieldFormNoWidths";
		}

		InputfieldWrapper::setMarkup($markup);
		InputfieldWrapper::setClasses($classes);

		$foundationURL = $this->foundationURL;
		if(strpos($foundationURL, '//') !== false) {
			$foundationURL = rtrim($foundationURL, '/');
		} else {
			$foundationURL = $this->wire('config')->urls->root . trim($foundationURL, '/');
		}
		

		$config = $this->wire('config');
		if($this->allowLoad('framework')) {
			$config->styles->prepend("$foundationURL/css/foundation.min.css");
			$config->scripts->append("$foundationURL/js/vendor/modernizr.js");
			$config->scripts->append("$foundationURL/js/foundation.min.js");
		}
		$config->styles->append($config->urls->FormBuilder . 'FormBuilder.css');
		$config->styles->append($config->urls->FormBuilder . 'frameworks/FormBuilderFrameworkFoundation.css');
		$config->inputfieldColumnWidthSpacing = 0;

		if(!$this->form->theme) $this->form->theme = 'delta';
		
		// change markup of submit button
		$this->addHookBefore('InputfieldSubmit::render', $this, 'hookInputfieldSubmitRender'); 
		if($this->allowLoad('foundation-init')) {
			$this->addHookAfter('FormBuilderProcessor::renderReady', $this, 'hookRender');
		}
	}
	
	public function hookRender($event) {
		$event->return .= "<script>$(document).foundation();</script>";
	}

	public function hookInputfieldSubmitRender($event) {
		$in = $event->object;
		$event->replace = true;
		$classes = array('button'); 
		if($this->buttonSize) $classes[] = $this->buttonSize; // tiny, small, large, expand
		if($this->buttonType) $classes[] = $this->buttonType; // success, secondary, alert, info
		if($this->buttonStyle) $classes[] = $this->buttonStyle; // round, radius
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
		$f->addOption('foundation-init', $this->_('Do not initialize Foundation: $(document).foundation();'));

		$f = $this->wire('modules')->get('InputfieldURL');
		$f->attr('name', 'foundationURL');
		$f->label = $this->_('URL to Foundation framework');
		$f->description = $this->_('Specify a URL/path relative to root of ProcessWire installation.');
		$f->attr('value', $this->foundationURL);
		if($this->foundationURL != $defaults['foundationURL']) $f->notes = $defaultLabel . $defaults['foundationURL'];
		$inputfields->add($f);
		
		$f = $this->wire('modules')->get('InputfieldRadios');
		$f->attr('name', 'horizontal');
		$f->label = $this->_('Form style');
		$f->addOption(0, $this->_('Stacked (default)'));
		$f->addOption(1, $this->_('Horizontal (2-column)'));
		$f->attr('value', $this->horizontal);
		$f->columnWidth = 100;
		$f->description= $this->_('Please note that individual field column widths (if used) are not applicable when using the *Horizontal* style.'); 
		$inputfields->add($f);
		
		$f = $this->wire('modules')->get('InputfieldText'); 
		$f->attr('name', 'horizHeaderClass'); 
		$f->label = $this->_('Horizontal label column (1)'); 
		$f->description = $this->_('Specify Foundation framework classes to apply to the label column.'); 
		$f->attr('value', $this->horizHeaderClass); 
		$f->columnWidth = 50; 
		$f->showIf = 'frFoundation_horizontal=1'; 
		if($this->horizHeaderClass != $defaults['horizHeaderClass']) $f->notes = $defaultLabel . $defaults['horizHeaderClass'];
		$inputfields->add($f); 
		
		$f = $this->wire('modules')->get('InputfieldText');
		$f->attr('name', 'horizContentClass');
		$f->label = $this->_('Horizontal input column (2)');
		$f->description = $this->_('Specify Foundation framework classes to apply to the input column.');
		$f->attr('value', $this->horizContentClass);
		$f->columnWidth = 50;
		$f->showIf = 'frFoundation_horizontal=1'; 
		if($this->horizContentClass != $defaults['horizContentClass']) $f->notes = $defaultLabel . $defaults['horizContentClass'];
		$inputfields->add($f); 
		
		$f = $this->wire('modules')->get('InputfieldRadios'); 
		$f->attr('name', 'buttonType'); 
		$f->label = $this->_('Submit button type'); 
		$f->addOption('', $this->_x('Default', 'buttonType'));
		$f->addOption('secondary', $this->_('Secondary'));
		$f->addOption('success', $this->_('Success'));
		$f->addOption('alert', $this->_('Alert'));
		$f->addOption('info', $this->_('Info'));
		$f->attr('value', $this->buttonType); 
		$f->columnWidth = 50; 
		$inputfields->add($f); 

		$f = $this->wire('modules')->get('InputfieldRadios');
		$f->attr('name', 'buttonSize');
		$f->label = $this->_('Submit button size'); 
		$f->addOption('tiny', $this->_('Tiny'));
		$f->addOption('small', $this->_('Small'));
		$f->addOption('', $this->_('Medium (default)')); 
		$f->addOption('large', $this->_('Large'));
		$f->addOption('expand', $this->_('Expand (full-width)'));
		$f->attr('value', $this->buttonSize);
		$f->columnWidth = 50; 
		$inputfields->add($f); 
	
		$f = $this->wire('modules')->get('InputfieldRadios'); 
		$f->attr('name', 'buttonStyle'); 
		$f->label = $this->_('Submit button style');
		$f->addOption('', $this->_('Normal'));
		$f->addOption('round', $this->_('Round'));
		$f->addOption('radius', $this->_('Radius'));
		$f->optionColumns = 1; 
		$f->attr('value', $this->buttonStyle); 
		$inputfields->add($f); 
	
		/*
		$f = $this->wire('modules')->get('InputfieldCheckbox'); 
		$f->attr('name', 'noInit'); 
		$f->label = $this->_('Do not initialize Foundation'); 
		$f->description = $this->_('The Foundation framework requires a `$(document).foundation();` call
		*/
		
		return $inputfields;
	}
	
	public function getConfigDefaults() {
		$urls = $this->wire('config')->urls;
		$foundationURL = str_replace($urls->root, '/', $urls->FormBuilder . 'frameworks/foundation/');
		return array_merge(parent::getConfigDefaults(), array(
			'foundationURL' => $foundationURL,
			'horizontal' => 0,
			'horizHeaderClass' => 'small-5 medium-3 right inline',
			'horizContentClass' => 'small-7 medium-9', 
			'buttonType' => '', 
			'buttonSize' => '', 
			'buttonStyle' => '', 
		));
	}

	public function getFrameworkURL() {
		return $this->foundationURL;
	}

}
