<?php

class FormBuilderRender extends WireData {
	
	protected $form; 
	protected $out; // rendered form
	protected $framework;
	protected $vars = array(); // vars to pre-populate

	/**
	 * @param FormBuilderForm $form
	 * @param array $vars Optional associative array of variables to pre-populate in form
	 * 
	 */
	public function __construct(FormBuilderForm $form, array $vars = array()) {
		$this->form = $form;
		$this->vars = $vars; 
		$this->framework = $this->form->getFramework();
		$this->framework->ready();
		$this->out = $this->_render();
	}
	
	public function __toString() {
		return $this->out; 
	}
	
	public function get($key) {
		if($key == 'scripts') return $this->renderScripts();
		if($key == 'styles') return $this->renderStyles();
		if($key == 'form') return $this->form;
		if($key == 'framework') return $this->framework;
		return parent::get($key);
	}
	
	public function scripts() { // alias for renderScripts
		return $this->renderScripts();
	}
	public function styles() { // alias for renderStyles
		return $this->renderStyles();
	}
	
	public function ___renderInlineStyles($wrap = true) {
		$styles = '';
		if($this->wire('page')->template == 'form-builder') {
			// embed method A or B
			$styles .=
				"html, body { background: transparent; margin: 0; padding: 0; } " .
				"body { margin-top: 1px; } " .
				".container { width: 100%; margin: 0; padding: 0; min-width: 100px; } " .
				"#content { margin: 0; padding: 1px; }";
		}
		$styles .= $this->framework->getInlineStyles();
		$mobilePx = $this->form->mobilePx;
		if(!$mobilePx) $mobilePx = "479px"; // default
		if($mobilePx != 1) { // 1=bypass
			if(ctype_digit("$mobilePx")) $mobilePx .= "px";
			$styles .=
				"\n@media only screen and (max-width: {$mobilePx}) { " .
					".InputfieldFormWidths .Inputfield { " .
						"clear: both !important; " .
						"width: 100% !important; " .
						"margin-left: 0 !important; " .
						"margin-bottom: 1em !important; " .
					"} "  .
					".Inputfield .InputfieldContent, " .
					".Inputfield .InputfieldHeader { " .
						"padding-left: 0 !important; "  .
						"padding-right: 0 !important; "  .
						"float: none !important; " .
						"width: 100%; " .
					"} " .
					".InputfieldFormWidths .Inputfield .InputfieldHeader { " .
						"margin-bottom: 0; " .
					"}" .
					".InputfieldFormNoWidths .Inputfield .InputfieldHeader { " .
						"text-align: initial; " .
					"}" .
				"}";
		}

		// minify
		$styles = preg_replace('/\s{2,}/s', ' ', $styles);
		$styles = str_replace(array(' { ', ' } ', ': ', '; ', ', ', '} }'), array('{', '} ', ':', ';', ',', '}}'), $styles);
		
		if($styles && $wrap) return "\n\t<style type='text/css'>$styles</style>";
		
		return $styles;
	}
	
	public function ___getStyles() {
		$styles = array();
		foreach($this->wire('config')->styles->unique() as $file) {
			$styles[] = $file;
		}
		return $styles;
	}

	public function renderStyles() {
		$out = '';
		foreach($this->getStyles() as $file) {
			$out .= "\n\t<link type='text/css' href='$file' rel='stylesheet' />";
		}
		$out .= $this->renderInlineStyles();
		return $out;
	}

	public function ___getScripts() {
		$scripts = array();
		$jquery = $this->framework->allowLoad('jquery');
		$jqueryui = $this->framework->allowLoad('jqueryui');
		foreach($this->wire('config')->scripts->unique() as $file) {
			if(!$jquery && strpos($file, 'JqueryCore') !== false) continue;
			if(!$jqueryui && strpos($file, 'JqueryUI') !== false) continue; 
			$scripts[] = $file;
		}
		return $scripts;
	}

	public function renderScripts() {
		
		$config = $this->wire('config');
		$jsConfig = $config->js();
		$jsConfig['debug'] = $config->debug;
		$jsConfig['urls'] = array('root' => $config->urls->root); //'modules' => $config->urls->modules,

		$out = 
			"\n\t<script type='text/javascript'>" . 
			"\n\t\tvar config = " . wireEncodeJSON($jsConfig, true, $config->debug) . 
			"\n\t</script>";
		
		foreach($this->getScripts() as $file) {
			$out .= "\n\t<script type='text/javascript' src='$file'></script>";
		}
		
		return $out;
	}
	
	public function render() {
		$out = $this->out; 
		$this->out = '';
		return $out; 
	}

	/**
	 * Render the form, for embed method A, B or C
	 *
	 * This method should be called before getScripts/renderScripts and getStyles/renderStyles
	 *
	 * @return string
	 * @throws Wire404Exception
	 *
	 */
	protected function _render() {

		$form = $this->form;
		$config = $this->wire('config');
		$out = $form->render();
		$jqueryUI = false;
		
		// identify if we will be using jQuery UI themes
		if($form->framework == 'Legacy') {
			// legacy always requires jQuery UI theme
			$jqueryUI = true; 
			
		} else if($form->framework == 'Admin') {
			// admin already loads jquery ui, so we don't need it a second time
			$jqueryUI = false;
			
		} else {
			// see if any Inputfields trigger jQuery UI to be loaded
			foreach($config->scripts as $file) {
				if(strpos($file, '/JqueryUI/')) {
					$jqueryUI = true;
				}
			}
		}
		
		if($jqueryUI) {
			// i.e. default when framework selected but not theme
			if(!$form->theme) $form->theme = 'delta';
			foreach(array('jquery-ui', 'inputfields', 'main') as $file) {
				// we only use the jquery-ui file(s) when a non-legacy framework is in use
				if($file != 'jquery-ui' && $form->framework != 'Legacy') continue;
				$path = $this->forms->themesPath($form->theme) . $file;
				$url = $this->forms->themesURL($form->theme) . $file;
				if(is_file("$path.css")) $config->styles->append("$url.css");
				if(is_file("$path.js")) $config->scripts->append("$url.js");
			}
		}

		$minfile = $config->paths->adminTemplates . "scripts/inputfields.min.js";
		if($config->debug && is_file($minfile)) {
			$config->scripts->append($config->urls->adminTemplates . "scripts/inputfields.min.js");
		} else {
			$config->scripts->append($config->urls->adminTemplates . "scripts/inputfields.js");
		}
		$config->scripts->append($config->urls->FormBuilder . "form-builder.js");
		
		$this->checkTemplateVersion($out);
		
		return $out;
	}
	
	protected function checkTemplateVersion(&$out) {
		if($this->wire('forms')->getTemplateVersion() < FormBuilder::requireTemplateVersion) {
			$page = $this->wire('page');
			if($page->template == 'form-builder') {
				$error = "<strong>This template file (/site/templates/form-builder.php) is out of date.</strong> Please replace it with a new copy from /site/modules/FormBuilder/form-builder.php. ";
				$error .= "This error message only visible to administrators.";
				if($page->editable()) {
					$markup = InputfieldWrapper::getMarkup();
					$out = str_replace('{out}', $error, $markup['error']) . $out;
				}
				$this->error($error, Notice::logOnly); 
			}
		}
	}
}