<?php

/**
 * ProcessWire Form Builder Module Installer
 *
 * This is kept in a separate file since there's a lot of code and don't need it
 * taking up space when it's not needed on every request. 
 *
 * Note that the tables are installed/uninstalled by FormBuilderMain.
 *
 * Copyright (C) 2012 by Ryan Cramer Design, LLC
 * 
 * PLEASE DO NOT DISTRIBUTE
 * 
 */

class FormBuilderInstall extends Wire {

	protected $permissionArray = array(
		'form-builder' => 'Access Form Builder admin page',
		'form-builder-add' => 'Add new or import Form Builder forms',
		);

	/**
	 * Install the form builder 
	 *
	 */
	public function install() {

		// create the fieldgroup to be used by the form-builder template
		$fieldgroup = new Fieldgroup();
		$fieldgroup->name = FormBuilderMain::name;
		$fieldgroup->add($this->fields->get('title'));
		$fieldgroup->save();

		// create the template used by the form-builder page
		$template = new Template();
		$template->name = FormBuilderMain::name;
		$template->fieldgroup = $fieldgroup;
		$template->slashUrls = 1;
		$template->urlSegments = 1; 
		$template->noGlobal = 1;
		$template->flags = Template::flagSystem;
		$template->save();
		$this->message("Created Template: {$template->name}"); 

		// create the form-builder page
		$page = new Page();
		$page->template = $template;
		$page->name = FormBuilderMain::name; 
		$page->parent = '/';
		$page->addStatus(Page::statusHidden); 
		$page->title = 'Form Builder';
		$page->save();

		// specify that this template may no longer be used for new pages
		$template->noParents = 1; 
		$template->save();

		// install the form-builder template file	

		$src = dirname(__FILE__) . '/' . FormBuilderMain::name . '.php'; 
		$dst = wire('config')->paths->templates . FormBuilderMain::name . '.php';
		if(@copy($src, $dst)) $this->message("Copied $src to $dst"); 
			else $this->error("Unable to copy $src to $dst"); 

		// install permissions
		$this->installPermissions();

		$this->message("Please click the 'submit' button on this screen to complete the Form Builder installation."); 
	}

	/**
	 * Uninstall the form builder
	 *
	 */
	public function uninstall() {

		$page = wire('pages')->get("template=" . FormBuilderMain::name); 
		if($page->id) {
			$this->message("Removing page: {$page->path}"); 
			$page->delete();
		}

		$template = wire('templates')->get(FormBuilderMain::name); 
		if($template) { 
			$template->flags = Template::flagSystemOverride; 
			$template->flags = 0; 
			$this->message("Removing template: {$template->name}"); 
			wire('templates')->delete($template); 
		}

		$fieldgroup = wire('fieldgroups')->get(FormBuilderMain::name); 
		if($fieldgroup) {
			$this->message("Removing fieldgroup: {$fieldgroup->name}"); 
			wire('fieldgroups')->delete($fieldgroup); 
		}

		$templateFile = wire('config')->paths->templates . FormBuilderMain::name . '.php';
		if(is_file($templateFile)) {
			if(@unlink($templateFile)) $this->message("Removed template file: $templateFile"); 
				else $this->error("Unable to delete /site/templates/" . FormBuilderMain::name . ".php - please delete it manually.");
		}

		// remove permissions
		foreach($this->permissionArray as $name => $title) {
			$permission = $this->permissions->get($name);
			if(!$permission || !$permission->id) continue; 
			$permission->delete();
			$this->message("Deleted permission: $name");
		}

	}

	/**
	 * Install permissions for form builder
	 *
	 */
	public function installPermissions() {

		$message = 'Added permissions: ';

		foreach($this->permissionArray as $name => $title) {

			if($name == 'form-builder') {
				// for upgrades vs. installs
				$permission = $this->permissions->get($name);
				if($permission && $permission->id) {
					$permission->title = $title;
					$permission->save();
					continue;
				}
			}

			$permission = $this->permissions->add($name); 
			$permission->title = $title;
			$permission->save();
			$message .= "$name, ";	
		}

		$this->message(trim($message, ", "));
	}

}
