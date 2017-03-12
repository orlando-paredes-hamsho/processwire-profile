<?php

/**
 * Lister Pro: Configuration (ListerProConfig)
 *     __    _      __           
 *    / /   (_)____/ /____  _____
 *   / /   / / ___/ __/ _ \/ ___/
 *  / /___/ (__  ) /_/  __/ /    
 * /_____/_/____/\__/\___/_/ PRO
 *
 * This is a commercial module, please do not distribute. 
 *
 * ListerPro for ProcessWire
 * Copyright 2014 by Ryan Cramer
 * http://processwire.com/ListerPro/
 *
 */

class ListerProConfig extends Wire {

	protected $lister = null;

	public function __construct(ProcessPageListerPro $lister) {
		$this->lister = $lister; 
	}

	public function __get($key) {
		$value = $this->lister->$key; 
		if(is_null($value)) $value = parent::__get($key); 
		return $value; 
	}

	/**
	 * Build the individual lister config form
	 *
	 */
	public function ___buildForm() { 

		$form = $this->modules->get('InputfieldForm'); 
		$form->method = 'post';
		$form->action = './';
		$form->description = $this->_('Configure This Lister') . ' (' . $this->wire('page')->title . ')';
		$form->attr('id', 'ListerProConfigForm'); 
		$addLabel = $this->_('Add Filter'); 

		$f = $this->modules->get('InputfieldHidden'); 
		$f->attr('name', 'pagename'); 
		$f->attr('value', $this->wire('page')->name); 
		$form->add($f); 

		$f = $this->modules->get('InputfieldSelector'); 
		$f->attr('name', 'initSelector'); 
		$f->attr('value', $this->initSelector); 
		$f->label = $this->_('What PAGES should the Lister show?'); 
		$f->description = $this->_('All pages displayed by this Lister will be found within the properties you select here (user will not be able to override them). We recommend specifying at least a "template" here, but usage is optional. If you do not select anything, the Lister will be able to display all pages in the site.'); // forced filters description
		$f->notes = $this->_('If you make a template selection above, we recommend clicking "save" before completing the fields below. This will ensure the options are reduced to only those applicable.'); 
		$f->icon = 'sitemap';
		$f->addLabel = $addLabel;
		$f->counter = true; 
		$f->allowSystemCustomFields = true;
		$f->allowSystemTemplates = true; 
		if(!ProcessPageListerPro::debug) $f->preview = false;
		$form->add($f); 

		// -----------------------------------

		$f = $this->modules->get('InputfieldSelect'); 
		$f->attr('name', 'defaultSort'); 
		$f->label = $this->_('What ORDER should the pages appear in?'); 
		$f->description = $this->_('Which field should be used as the default sort? We recommend choosing a field that is also used as one of the default columns above (like "title"). Note that the user can change the sort at any time by clicking on the column headings.'); // columns description
		$options = array();
		$fastColumns = array('id', 'name', 'modified', 'created', 'sort'); 
		$sortColumns = array_merge($fastColumns, $this->columns); 
		sort($sortColumns); 
		foreach($sortColumns as $name) {
			$label = $name; 
			if(in_array($name, $fastColumns)) $label .= ' *';
			$options[$name] = $label; 
		}
		$f->addOption($this->_('Ascending'), $options); 
		$labels = $options; 
		foreach($options as $name => $label) {
			$options["-$name"] = $label;
			unset($options[$name]); 
		}
		$f->addOption($this->_('Descending'), $options); 
		if(!$this->defaultSort) $this->defaultSort = 'name';
		$f->attr('value', $this->defaultSort); 
		$f->icon = 'sort-alpha-asc';
		if(!count($this->columns)) {
			$f->collapsed = Inputfield::collapsedYes; 
			$f->notes = $this->_('More options will be visible here after you populate the Default columns field above and SAVE.'); 
		} else {
			$f->notes = $this->_('The fields shown here reflect system fields and those selected in the "columns" field above (though you will need to SAVE before you see your column selections here).'); 
		}
		$f->notes .= ' ' . $this->_('**Sort fields indicated with an asterisk may provide significantly increased performance**, especially with large result sets. We recommend choosing one of these fields as your default.'); 
		$form->add($f); 

		// -----------------------------------

		$templatesStr = $this->lister->getSelectorTemplates($this->initSelector, false); 
		$f = $this->modules->get('InputfieldSelector'); 
		$f->attr('name', 'defaultSelector'); 
		$f->attr('value', $this->defaultSelector); 
		$f->label = $this->_('What FILTERS should the Lister show?'); 
		$f->description = $this->_('The filters you select here will be visible when a user first views the Lister. These are the default filters only, as the user can optionally add, change or remove them. We recommend that you select the fields to be used as filters, but leave the values in each row blank/unselected (unless you wish to provide a default value).'); // default filters description
		$f->addLabel = $addLabel;
		$f->allowBlankValues = true; 
		$f->allowSystemCustomFields = true;
		$f->allowSystemTemplates = true; 
		$f->counter = false;
		$f->icon = 'search-plus';
		if(count($templatesStr)) $f->initValue = "template=$templatesStr";
		if(!ProcessPageListerPro::debug) $f->preview = false;
		$form->add($f); 

		/*
		$f = $this->modules->get('InputfieldCheckboxes'); 
		$f->attr('name', 'ops');
		$f->label = $this->_('Options and Behaviors'); 
		$f->addOption(ProcessLister::opsNoRemoveFilters, $this->_('Prevent user from removing default filters')); 
		$f->addOption(ProcessLister::opsNoAddFilters, $this->_('Prevent user from adding new filters')); 
		$f->attr('value', $this->ops); 
		$form->add($f); 
		*/

		$f = $this->lister->buildColumnsField(); 
		$f->label = $this->_('What COLUMNS should the results table show?'); 
		$f->description .= ' ' . $f->notes; 
		$f->notes = $this->_('Too many columns to choose from? Select one or more "template" fields for "What pages will the Lister show?" above, and then click save. Following that, this field will only show the applicable columns rather than all columns.'); 
		$form->add($f); 

		$f = $this->modules->get('InputfieldPageListSelect'); 
		$f->attr('id+name', 'lister_parent');
		$f->label = $this->_('What PARENT should new pages be added to?'); 
		$f->description = $this->_('This optional parent setting makes an "Add New" button appear in your Lister. This setting tells Lister where new pages should go.');
		if($this->parent && $this->parent->id) $f->attr('value', $this->parent->id); 
		$f->collapsed = Inputfield::collapsedBlank;
		$f->icon = 'copy';
		$form->add($f); 

		$f = $this->modules->get('InputfieldCheckboxes'); 
		$f->attr('name', 'allowActions'); 
		$f->label = $this->_('What ACTIONS do you want to allow?'); 
		$f->description = $this->_('Check each of the actions that you want to be allowed with this Lister.');
		$f->notes = $this->_('If an action has a required permission listed, then the user must also have a role with that permission before the action will be available to the user.'); 
		$f->notes .= "\n" . $this->_('To add more actions, you may install additional PageAction modules from your Modules screen.');
		$f->table = true; 
		$f->icon = 'tasks';
		$f->collapsed = Inputfield::collapsedBlank;
		$options = array();
		foreach($this->wire('modules') as $module) {
			$className = $module->className();
			$parents = class_parents($className); 
			if(!in_array('PageAction', $parents)) continue; 
			$info = $this->wire('modules')->getModuleInfo($module); 
			$label = "$info[title]|$info[name]|";
			if($info['permission']) $label .= $info['permission'];
			$options[$module->className()] = $label;

		}
		ksort($options); 	
		$f->addOptions($options); 
		$f->thead = $this->_('Title') . '|' . 
			$this->_('Module Name') . '|' . 
			$this->_('Required Permission');
		$f->attr('value', $this->allowActions); 
		$form->add($f); 

		// --------------------

		$fieldset = $this->wire('modules')->get('InputfieldFieldset'); 
		$fieldset->label = $this->_('How should IMAGES appear?'); 
		$fieldset->icon = 'camera';
		$fieldset->collapsed = Inputfield::collapsedYes; 
		$form->add($fieldset); 

		$f = $this->modules->get('InputfieldInteger'); 
		$f->attr('name', 'imageWidth'); 
		if($this->imageWidth) $f->attr('value', $this->imageWidth); 
		$f->label = $this->_('Image Width'); 
		$f->description = $this->_('Width of thumbnail images displayed in Lister.'); 
		$f->notes = $this->_('Leave blank to make proportional to height.'); 
		$f->columnWidth = 50; 
		$fieldset->add($f); 

		$f = $this->modules->get('InputfieldInteger'); 
		$f->attr('name', 'imageHeight'); 
		if($this->imageHeight) $f->attr('value', $this->imageHeight); 
		$f->label = $this->_('Image Height'); 
		$f->description = $this->_('Height of thumbnail images displayed in Lister.'); 
		$f->notes = $this->_('Leave blank to make proportional to width.'); 
		$f->columnWidth = 50; 
		$fieldset->add($f); 

		$f = $this->modules->get('InputfieldCheckbox'); 
		$f->attr('name', 'imageFirst'); 
		$f->attr('value', 1); 
		if($this->imageFirst) $f->attr('checked', 'checked'); 
		$f->label = $this->_('Only display first image when there is more than one?'); 
		$fieldset->add($f); 

		// --------------------

		$fieldset = $this->wire('modules')->get('InputfieldFieldset'); 
		$fieldset->label = $this->_('How should the VIEW and EDIT links work?'); 
		$fieldset->description = $this->_('View or edit links only appear on a page when the user has access to view or edit access to it.');
		$fieldset->collapsed = Inputfield::collapsedYes; 
		$fieldset->icon = 'random';
		$form->add($fieldset); 

		$modeOptions = array(
			ProcessPageLister::windowModeNone => $this->_('Same window'),
			ProcessPageLister::windowModeModal => $this->_('Modal window'),
			ProcessPageLister::windowModeBlank => $this->_('New window'),
			); 

		$f = $this->modules->get('InputfieldRadios'); 
		$f->attr('name', 'viewMode'); 
		$f->label = $this->_('View links open in ...');
		$f->addOptions($modeOptions); 
		$f->addOption(ProcessPageLister::windowModeHide, $this->_('Do not show view links')); 
		$f->attr('value', $this->viewMode); 
		$f->columnWidth = 50; 
		$fieldset->add($f); 

		$f = $this->modules->get('InputfieldRadios'); 
		$f->attr('name', 'editMode'); 
		$f->label = $this->_('Edit links open in ...');
		$f->addOptions($modeOptions); 
		$f->addOption(ProcessPageLister::windowModeHide, $this->_('Do not show edit links')); 
		$f->attr('value', $this->editMode); 
		$f->columnWidth = 50; 
		$fieldset->add($f);

		// --------------------

		if($this->page->name != 'lister') { 

			$f = $this->modules->get('InputfieldMarkup');
			$f->label = $this->_('Who can ACCESS this Lister?'); 
			$f->icon = 'lock';
			$f->collapsed = Inputfield::collapsedYes; 

			$permissionName = "page-lister-" . $this->page->name; 

			if($this->input->post('createPermission')) {
				$permission = $this->wire('permissions')->add($permissionName); 
				$permission->title = "Use Lister: {$this->page->title}";
				$permission->save();
				$this->message(sprintf($this->_('Created permission: %s'), $permission->name)); 
				$f->collapsed = Inputfield::collapsedNo; 
			} else {
				$permission = $this->wire('permissions')->get($permissionName); 
			}
			if($permission->id) { 
				$roleNames = array();
				foreach($this->wire('roles') as $role) {
					if($role->hasPermission($permission)) $roleNames[] = $role->name; 
				}
				$f->value = '<p>' . sprintf($this->_('Found permission %s.'), "<b>$permission->name</b>") . ' ';
				if(count($roleNames)) {
					$f->value .= $this->_('This Lister is currently available to the following roles:') .  ' <b>' . implode('</b>, <b>', $roleNames) . '</b>. ';
				} else {
					$f->value .= $this->_('This permission is not yet assigned to any roles.') . ' ';
				}
				$f->value .= sprintf($this->_('To add or remove roles, go to Access > Roles, and check or uncheck the %s permission.'), "<b>$permission->name</b>") . '</p>'; 
			
			} else {
				$f->value = 
					'<p>' . 
					sprintf($this->_('By default, this Lister is available to all roles that have %s permission.'), "<b>page-lister</b>") . ' ' . 
					sprintf($this->_('If you want to limit access further, create a new permission called %s.'), "<b>$permissionName</b>") . ' ' . 
					$this->_('Assign this permission to one or more roles under Access > Roles.') . 
					'</p>' . 
					'<p><label>' . 
					'<input type="checkbox" name="createPermission" value="1" /> ' . 
					sprintf($this->_('Create %s permission?'), "<b>$permissionName</b>") . 
					'</label></p>'; 
					
			}
			$form->add($f); 
		}
		
		// --------------------

		$fieldset = $this->wire('modules')->get('InputfieldFieldset');
		$fieldset->label = $this->_('Advanced');
		$fieldset->collapsed = Inputfield::collapsedYes;
		$fieldset->icon = 'suitcase';
		$form->add($fieldset); 

		$f = $this->modules->get('InputfieldTextarea'); 
		$f->attr('name', 'delimiters'); 
		$f->label = $this->_('How should the values in multi-value fields/columns be separated?'); 
		$f->description = $this->_('Multi-value fields/columns selected above (like multi-page references) are delimited by a line break. If you would like to separate the values for any of the selected columns above by something else, then enter one per line of: field=delimiter.'); // delimiters description
		$f->notes = $this->_('Example:') . "\ntags=, \ncategories=; "; 
		$f->attr('value', ''); 
		$f->collapsed = Inputfield::collapsedBlank;
		foreach($this->delimiters as $fieldName => $delimiter) {
			$f->value .= "$fieldName=$delimiter\n";
		}
		$fieldset->add($f); 

		$f = $this->modules->get('InputfieldTextarea'); 
		$f->attr('name', 'InputfieldSelectorSettings');
		$f->attr('value', $this->lister->InputfieldSelectorSettings);
		$f->label = $this->_('Inputfield Selector Settings'); 
		$f->description = $this->_('Use one-per-line of key=value for each custom setting.'); 
		$s = $this->lister->getInputfieldSelector();
		$f->notes = sprintf($this->_('Potential settings and current values are listed below. Descriptions of each setting can be found in [InputfieldSelector](%s).'), 'https://github.com/ryancramerdesign/ProcessWire/blob/dev/wire/modules/Inputfield/InputfieldSelector/InputfieldSelector.module');
		$f->collapsed = Inputfield::collapsedBlank;
		foreach($s->getSettings() as $key => $value) $f->notes .= "\n$key=$value";
		$fieldset->add($f); 

		// --------------------

		$f = wire('modules')->get('InputfieldText'); 
		$f->attr('name', '_new_lister_title'); 
		$f->label = $this->_('Add a NEW Lister'); 
		$f->collapsed = Inputfield::collapsedYes; 
		$f->icon = 'plus-circle';
		$f->description = $this->_('Enter the title for the lister you want to create. You can configure your new Lister separately from this one. A new page containing your Lister will be created in your admin Pages navigation. You may move the page elsewhere if you prefer.'); // Add new Lister description
		$form->add($f); 

		// --------------------

		$f = $this->modules->get('InputfieldSubmit'); 
		$f->attr('name', 'submit_save_config'); 
		$f->attr('value', $this->_x('Save', 'button'));
		$f->class .= ' head_button_clone';
		$form->add($f); 

		$f = $this->modules->get('InputfieldSubmit'); 
		$f->attr('name', 'submit_view'); 
		$f->attr('value', $this->_x('Save + View', 'button'));
		$f->class .= ' ui-priority-secondary head_button_clone';
		$form->add($f); 

		if($this->wire('input')->post->submit_save_config || $this->wire('input')->post->submit_view) {
			$this->processInput($form); 
		}

		return $form; 
	}

	/**
	 * Process and save lister configuration
	 *
	 * Redirect to config screen or lister depending on what button was clicked. 
	 *
	 */
	protected function ___processInput(InputfieldForm $form) {

		//$this->wire('user')->notifications->message("initSelector=" . $this->wire('input')->post('initSelector')); 
		//$this->wire('user')->notifications->save();
		$form->processInput($this->wire('input')->post); 
		$settings = array();

		foreach($form->getAll() as $f) { 
			$name = $f->attr('name'); 
			$value = $f->attr('value');
			if(strpos($name, 'submit') === 0) continue; 
			if($name == 'lister_parent') $name = 'parent'; 
			$settings[$name] = $value; 
		}

		// convert delimiter from textarea into array
		if(isset($settings['delimiters'])) { 
			$value = array();
			foreach(explode("\n", $settings['delimiters']) as $line) {
				if(!strpos($line, '=')) continue; 
				list($name, $delimiter) = explode('=', $line); 
				if(empty($name)) continue; 
				$name = $this->wire('sanitizer')->fieldName($name); 
				$value[$name] = $delimiter; 	
			}
			$settings['delimiters'] = $value; 
		}
		$configData = $this->modules->getModuleConfigData($this->lister); 
		if(!isset($configData['settings'])) $configData['settings'] = array();
		// note settings are tied to the page name the lister lives on
		$configData['settings'][$this->page->name] = $settings; 
		$this->lister->sessionClear();
		$this->modules->saveModuleConfigData($this->lister, $configData); 
		$this->message($this->_('Saved lister configuration ') . ' - ' . $this->page->name); 

		$newListerTitle = $this->wire('input')->post('_new_lister_title'); 
		if($newListerTitle) {
			$page = new Page();
			$page->template = 'admin';
			$admin = $this->wire('pages')->get($this->wire('config')->adminRootPageID); 
			$parent = $admin->child('name=page, include=all'); 
			$page->parent = $parent->id ? $parent : $admin;
			$page->process = $this->wire('modules')->get('ProcessPageListerPro'); 
			$page->title = $newListerTitle; 
			try { 
				$page->save();
				$this->message($this->_('Created Lister') . ' - ' . $page->title . ' - ' . 
					$this->_('You are now configuring the new Lister')); 
				$this->wire('session')->redirect($page->url . 'config/'); 
			} catch(Exception $e) {
				$this->error("Error creating lister - " . $e->getMessage()); 	
			}
		}

		if($this->wire('input')->post->submit_view) $this->wire('session')->redirect('../'); 
			else $this->session->redirect('./?s=1'); 
	}

}
