<?php

/**
 * Lister Pro: Actions Support (ListerProActions)
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

class ListerProActions extends Wire {

	/**
	 * Instance of ProcessPageLister
	 *
	 */
	protected $lister = null;
	
	/**
	 * Max number of items per match when processing actions for large amounts of pages
	 *
	 */
	protected $batchLimit = 250; 

	/**
	 * True when actions are being processed
	 *
	 */
	protected $runningActions = false; 

	/**
	 * False when messages shouldn't be echoed, for scalability in processing thousands of pages.
	 *
	 */
	protected $echoMessages = true; 

	/**
	 * Instance of Ift Runner, if installed and used for queued actions
	 *
	 * Only set if queued actions have been requested. 
	 *
	 */
	protected $ift = null;

	public function __construct(ProcessPageListerPro $lister) {
		$this->lister = $lister; 
	}

	public function __get($key) {
		if($key == 'template') return $this->lister->template; 
		if($key == 'lister') return $this->lister; 
		return parent::__get($key); 
	}

	/**
	 * Render the markup for the actions form (hookable)
	 *
	 * @return string
	 *
	 */
	public function ___render() {
		return $this->buildActionsForm()->render();
	}

	/**
	 * Execute actions
	 *
	 * Output is direct rather than returned. 
	 *
	 */
	public function ___execute() {	

		if($this->wire('config')->demo) {
			$form = $this->wire('modules')->get('InputfieldForm');
			$this->___beginProcessActions($form);
			$this->message('...');
			sleep(1); 
			$this->message('...');
			sleep(1); 
			$this->message('...');
			sleep(1); 
			$this->message("This is demo mode, so actions are disabled."); 
			$this->___finishProcessActions($form);
			exit; 
		}

		// check if config was requested
		if($this->wire('input')->post->submit_config) $this->session->redirect("../config/"); 

		// check if filter reset was requested
		if($this->wire('input')->post->submit_reset) $this->resetAllFilters();

		// abort of no action requested
		if(!$this->wire('input')->post->run_action) return '';

		// if we are still here, then it's time to execute some actions
		$this->executeActions();
	}

	/**
	 * An action that simply resets all filters and redirects back to new/blank lister
	 *
	 */
	protected function resetAllFilters() {
		$this->lister->sessionClear();
		$this->message($this->_('All filters have been reset.')); 
		$this->session->redirect('../'); 
	}


	/**
	 * Build the Lister actions form
	 *
	 * Hooks most likely will want to hook the buildActions method instead,
	 * but this one remains hookable just in case.
	 *
	 * @return InputfieldForm
	 *
	 */
	protected function ___buildActionsForm() {

		$form = $this->modules->get('InputfieldForm'); 
		$form->attr('id', 'ProcessListerActionsForm'); 
		$form->action = './actions/';
		$form->method = 'post';
		$form->class .= ' WireTab';
		$form->attr('title', $this->_('Actions'));
		
		$fieldset = $this->modules->get('InputfieldFieldset');
		//$fieldset->collapsed = Inputfield::collapsedYes; 
		$fieldset->label = $form->attr('title'); 
		$fieldset->icon = 'tasks';
		$fieldset->description = $this->_('Actions can make changes to any quantity of pages as a group. As a result, it is always a good idea to make sure your site has a good backup before executing actions.'); 
		//$fieldset->description = $this->_('These actions apply to all pages matching your filters (including all paginations).'); 

		$f = $this->wire('modules')->get('InputfieldRadios'); 
		$f->attr('id+name', 'actions_items'); 
		$f->label = $this->_('Which pages should the actions apply to?'); 
		$f->addOption('all', $this->_('all pages matching your filters')); 
		$f->addOption('open', $this->_('selected page(s)')); 
		$f->attr('value', 'all'); 
		$f->collapsed = Inputfield::collapsedYes; 
		$fieldset->add($f); 

		$this->buildActions($fieldset); 

		if($this->modules->isInstalled('IftRunner')) {
			$f = $this->modules->get('InputfieldRadios'); 
			$f->attr('name', 'actions_later'); 
			$f->label = $this->_('When should the action(s) run?');
			$f->addOption(0, $this->_('Now')); 
			$f->addOption(1, $this->_('Later')); 
			$f->attr('value', 0); 
			$f->collapsed = Inputfield::collapsedYes; 
			$f->showIf = 'actions.count>0';
			$f->icon = 'clock-o';
			$f->columnWidth = 50; 
			$fieldset->add($f); 

			$email = $this->wire('user')->email;
			$f = $this->wire('modules')->get('InputfieldRadios'); 
			$f->attr('name', 'actions_later_email'); 
			$f->label = $this->_('Notify me when action(s) finish?'); 
			$f->addOption(0, $this->_('Do not notify me')); 
			if($email) $f->addOption(1, $email); 
			$f->attr('value', $email ? 1 : 0); 
			$f->showIf = 'actions_later=1';
			$f->columnWidth = 50; 
			$f->icon = 'envelope-o';
			$fieldset->add($f); 
		} 

		$f = $this->modules->get('InputfieldSubmit'); 
		$f->attr('value', $this->_('Execute')); 
		$f->attr('name', 'run_action');
		$f->icon = 'check';
		$fieldset->add($f);

		$f = $this->modules->get('InputfieldSubmit'); 
		$f->attr('id+name', 'submit_config'); 
		$f->value = $this->_x('Configure Lister', 'button'); 
		$f->class .= ' ui-priority-secondary';
		$f->icon = 'cog';
		$fieldset->add($f); 

		$f = $this->modules->get('InputfieldSubmit'); 
		$f->attr('id+name', 'submit_reset'); 
		$f->value = $this->_x('Reset Filters', 'button'); 
		$f->class .= ' ui-priority-secondary';
		$f->icon = 'minus-circle';
		$fieldset->add($f); 

		$f = $this->modules->get('InputfieldSubmit'); 
		$f->attr('id+name', 'submit_refresh'); 
		$f->value = $this->_x('Refresh Results', 'button'); 
		$f->class .= ' ui-priority-secondary';
		$f->icon = 'refresh';
		$fieldset->add($f); 

		$f = $this->modules->get('InputfieldMarkup'); 
		$f->attr('id+name', 'wrap_actions_viewport'); 
		$f->label = $this->_('Action Results'); 
		$f->attr('value', "<iframe id='actions_viewport' name='actions_viewport' src='{$this->page->url}/viewport/'></iframe>"); 
		$fieldset->add($f); 

		$form->add($fieldset); 

		return $form;
	}

	/**
	 * Build the actions to be displayed in the actions form
	 *
	 * Hooks shouild hook before or after this method to add any necessary actions
	 * fields to the form. Hooks should create a collapsed fieldset with their actions
	 * and then add the fieldset to the given $form. 
	 *
	 * @param InputfieldFieldset $fieldset The actions fieldset to populate - $event->arguments(0); 
	 *
	 */
	protected function ___buildActions(InputfieldWrapper $form) {
		// for hooks to add whatever actions are needed 

		$checkboxes = $this->wire('modules')->get('InputfieldCheckboxes'); 
		$checkboxes->attr('name', 'actions'); 
		$checkboxes->label = $this->_('Which actions do you want to run?'); 
		$checkboxes->description = $this->_('For each action you choose, additional configuration options may appear in the fields below.'); 
		$form->add($checkboxes); 
		$numActions = 0;

		foreach($this->lister->allowActions as $className) {
			$info = $this->wire('modules')->getModuleInfo($className); 
			if($info['permission'] && !$this->wire('user')->hasPermission($info['permission'])) continue; 
			$module = $this->wire('modules')->get($className); 
			if(!$module instanceof PageAction) continue; 
			$module->setRunner($this); 
			$title = $info['title'];
			if(stripos($title, 'Page Action:') === 0) $title = trim(str_ireplace('Page Action:', '', $title)); 
			$checkboxes->addOption($className, $title); 
			$fieldset = $module->getConfigInputfields();

			// namespace each action's config settings
			foreach($fieldset->getAll() as $inputfield) {
				$name = $className . "__" . $inputfield->attr('name'); 
				$inputfield->attr('name', $name); 
			}

			$fieldset->showIf = 'actions=' . $module->className();
			$form->add($fieldset); 
			$numActions++;
		}

		if(!$numActions) {
			$checkboxes->description = $this->_('This Lister does not have any actions assigned or you do not have access to them.'); 
			if($this->wire('user')->isSuperuser()) $checkboxes->description .= ' ' . $this->_('You may add actions from the Lister Config screen.'); 
		}
	}

	/**
	 * Process lister actions (all pages)
	 *
	 * @param InputfieldForm $form The actions form - $event->arguments(0); 
	 * @param PageArray The pages to perform actions on - $event->arguments(1); 
 	 *
	 */
	protected function ___processActions(InputfieldForm $form, PageArray $items) {

		$actions = array(); // array of Module objects that we will run
		$inputfields = array();

		foreach($form->get('actions')->value as $actionName) {

			$actionName = $this->wire('sanitizer')->name($actionName); 
			if(!in_array($actionName, $this->lister->allowActions)) continue; 
			$info = $this->wire('modules')->getModuleInfo($actionName); 
			if($info['permission'] && !$this->wire('user')->hasPermission($info['permission'])) continue; 
			$action = $this->wire('modules')->get($actionName); 
			if(!$action || !$action instanceof PageAction) continue; 
			$action->setRunner($this); 
			$actions[$actionName] = $action; 
			$this->message($this->_('Running Action') . " - $actionName"); 
		}

		if(!count($actions)) return;

		// undo the namespaces to populate the values to each action
		foreach($form->getAll() as $inputfield) {

			$inputName = $inputfield->attr('name'); 
			if(!strpos($inputName, '__')) continue; 

			foreach($actions as $actionName => $action) {		
				if(strpos($inputName, $actionName . '__') !== 0) continue; 
				$name = str_replace($actionName . '__', '', $inputName); 
				$value = $inputfield->attr('value'); 
				$action->set($name, $value); 
				break;
			}
		}
	
		// run actions on all $items now
		foreach($actions as $action) {
			try {
				$echoMessages = $this->echoMessages; 
				if(!$echoMessages) $this->echoMessages = true; 
				$action->executeMultiple($items); 
				if(!$echoMessages) $this->echoMessages = false; 
			} catch(Exception $e) {
				$this->error($e->getMessage()); 
			}
		}
	}

	/**
	 * Queue lister actions for IftRunner (all pages, by ID)
	 *
	 * @param InputfieldForm $form The actions form - $event->arguments(0); 
	 * @param PageArray The pages to perform actions on - $event->arguments(1); 
 	 *
	 */
	protected function ___processActionsIft(InputfieldForm $form, array $items) {

		$actions = array(); // array of Module objects that we will run
		$inputfields = array();

		foreach($form->get('actions')->value as $actionName) {

			$actionName = $this->wire('sanitizer')->name($actionName); 

			// $action is an IftAction
			$action = $this->ift->actions->getNew();
			$action->title = $this->_('Queued by Lister'); 
			$action->moduleName = $actionName; 
			if($this->wire('input')->post('actions_later_email')) $action->flags = 512; 
			$actions[$actionName] = $action; 
			$echoMessages = $this->echoMessages; 
			if(!$echoMessages) $this->echoMessages = true; 
			$this->message($this->_('Action Queued') . " - $actionName - " . 
				sprintf($this->_('%d pages'), count($items))); 
			if(!$echoMessages) $this->echoMessages = false;
		}

		if(!count($actions)) return;

		// undo the namespaces to populate the values to each action
		foreach($form->getAll() as $inputfield) {

			$inputName = $inputfield->attr('name'); 
			if(!strpos($inputName, '__')) continue; 

			foreach($actions as $actionName => $action) {		
				if(strpos($inputName, $actionName . '__') !== 0) continue; 
				$name = str_replace($actionName . '__', '', $inputName); 
				$value = $inputfield->attr('value'); 
				$action->settings($name, $value); 
				break;
			}
		}
	
		// queue actions with IftRunner
		static $prevActions = array();
		foreach($actions as $action) {
			$prevAction = isset($prevActions[$action->moduleName]) ? $prevActions[$action->moduleName] : null;
			$action = $this->ift->queueAction($action, $items, $prevAction); 
			$prevActions[$action->moduleName] = $action;
		}
	}

	/**
	 * Process lister actions (one page at a time)
	 *
	 * This method is the one you should hook if you want to process new actions.
	 * Unlike processLiterActions() this one operates on one page at a time. 
	 * If your hook $event->return is populated with any output, it will be shown. 
	 * Otherwise, your hook should use $this->message() or $this->error() to 
	 * indicate success or errors. 
	 *
	 * @param InputfieldForm $form The actions form - $event->arguments(0); 
	 * @param Page The page to perform actions on - $event->arguments(1); 
	 * @return string This method returns nothing, but hooks may optionally echo results directly (precede each line with a \n). 
 	 *
	protected function ___processActionsPage(InputfieldForm $form, Page $page) {

	}
	 */

	/**
	 * Build batches of page IDs into the database
	 *
	 * @param string $itemsCSV CSV string of page IDs to limit to or "all" to run on all (assumed to be unsanitized)
	 * @return array of database batch IDs
 	 *
	 */
	protected function buildBatches($itemsCSV = 'all') {

		$itemIDs = array();
		if($itemsCSV != 'all') {
			$this->message($this->_('Applying to specific pages')); 
			$dirtyIDs = explode(',', $itemsCSV); 	
			$valid = true; 
			foreach($dirtyIDs as $id) {
				if(!ctype_digit("$id")) $valid = false; 
				$itemIDs[] = (int) $id; 
			}
			if(!$valid || !count($itemIDs)) throw new WireException("No pages to run actions on"); 
		} else {
			$this->message($this->_('Applying to all pages matching filters')); 
		}

		$database = $this->wire('database'); 
		$selector = $this->lister->getSelector(0); 
		$start = 0; 
		$total = 0;
		$limit = $this->batchLimit; 
		$batchIDs = array();
		$batchNum = 1; 
		$pageFinder = new PageFinder();
		$allowSorts = array('id', 'name', 'modified', 'created', 'status', 'sort', 'modified_users_id', 'created_users_id'); 

		$selector = $this->lister->removeBlankSelectors($selector); 
		$selectors = new Selectors($selector); 
		$sortChanged = false;
		// limit sort to only native fields
		foreach($selectors as $s) {
			if($s->field == 'sort' && !in_array($s->value, $allowSorts)) {
				$selector = preg_replace('/\bsort=[-_a-zA-Z0-9]+/', 'sort=id', $selector); 
			}
		}
		
		while(1) {
			if(count($itemIDs)) {
				$selectorString = "$selector, id=" . implode('|', $itemIDs); // limit to only selected IDs
			} else {
				$selectorString = "$selector, start=$start, limit=$limit"; 
			}
			$selectors = new Selectors($selectorString); 
			$options = $total ? array('getTotal' => false) : array();
			$items = $pageFinder->find($selectors, $options); 
			if(!$total) $total = $pageFinder->getTotal();
			$start += $limit; 
			if(!count($items)) break;
			$message = $this->_('Building batch of pages') . " [$batchNum/" . ceil($total / $limit) . "]"; 
			if(ProcessPageListerPro::debug) $message .= " $selectorString";
			$this->message($message); 
			$data = array();
			foreach($items as $item) $data[] = $item['id'];
			$data = implode(',', $data); // to CSV
			$sql = "INSERT INTO lister_actions SET data=:data";
			$query = $database->prepare($sql); 
			$query->bindValue(':data', $data); 
			$query->execute();
			$query->closeCursor();
			$batchIDs[] = $database->lastInsertId();
			$batchNum++;
			if(count($itemIDs) && count($itemIDs) < $limit) break;
		} 

		return $batchIDs; 
	}

	protected function getMemoryUsage() {
		$unit = array('b','kb','mb','gb','tb','pb');
		$size = memory_get_usage(true);
		$usage = @round($size/pow(1024,($i=floor(log($size,1024)))),2).$unit[$i];
		return $usage; 
	}

	/**
	 * Execute lister actions
	 *
	 * If output is generated it shows the output with a 'return' button.
	 * If no output is generated then it just returns to the Lister. 
 	 *
	 */
	protected function ___executeActions() {

		$out = '';
		$form = $this->buildActionsForm();
		$form->processInput($this->input->post); 
		$this->initTable();
		$this->runningActions = true; 
		$this->beginProcessActions($form);
		$this->ift = $this->wire('input')->post('actions_later') ? $this->wire('modules')->get('IftRunner') : null;

		$itemsCSV = $this->wire('input')->post('actions_items');  // either "all" or "123,456,789" if page IDs
		$startTime = time();
		$numSaved = 0; 
		$numSkipped = 0;
		$numNotChanged = 0; 
		$isSuperuser = $this->wire('user')->isSuperuser();
		$database = $this->wire('database'); 
		$batchIDs = $this->buildBatches($itemsCSV); // buildBatches knows this is unsanitized
		$batchNum = 0; 
		$batchTotal = count($batchIDs); 
		$timer = Debug::timer();
		$this->message($this->_('Processing batches...')); 
		if($batchTotal > 10) $this->echoMessages = false; 

		foreach($batchIDs as $batchID) {

			$batchTimer = Debug::timer();
			$batchNum++;
			
			$sql = "SELECT data FROM lister_actions WHERE id=:id";
			$query = $database->prepare($sql); 
			$query->bindValue(':id', $batchID); 
			$query->execute(); 	
			list($data) = $query->fetch(PDO::FETCH_NUM); 
			$data = explode(',', $data); 
			$query->closeCursor();
			unset($query); 

			if($this->ift) {

				$this->message("processActionsIft", Notice::debug); 
				$this->processActionsIft($form, $data); 

			} else {  

				$items = $this->wire('pages')->getById($data, $this->lister->template); 

				// run any hooks that process groups of pages
				$this->processActions($form, $items);

				// save any pages that changed
				$n = 0; 
				$total = count($items); 
				foreach($items as $item) {
					$n++;

					$changes = $item->getChanges();
					if(!count($changes)) {
						if(!$item->statusPrevious && !$item->parentPrevious) $numNotChanged++;
						continue; 
					}

					if(!$item->editable()) {
						$this->message(sprintf($this->_('Skipped non-editable page: %s'), $item->path)); 
						$numNotChanged++;
						continue;
					}

					$changes = implode(', ', $changes); 
					$this->wire('pages')->save($item, array('uncacheAll' => false)); 
					$numSaved++; 

					if($this->echoMessages) {
						if(!strlen($changes)) $changes = '?'; 
						$this->message(
							$this->_('Batch') . " [$batchNum/$batchTotal] " . 
							$this->_('Page') . " [$n/$total] " . 
							sprintf($this->_('Saved %s for: %s'), $changes, $item->path)
							); 
					}
				}

				unset($items); 
				$this->wire('pages')->uncacheAll(); // clear up memory
			}

			// remove completed batch
			$query = $database->prepare("DELETE FROM lister_actions WHERE id=:id"); 
			$query->bindValue(':id', $batchID); 
			$query->execute();
			$query->closeCursor();
			unset($query); 

			$this->echoMessages = true; 
			$this->message($this->_('Processed batch') . " " . 
				"[$batchNum/$batchTotal] " . round(Debug::timer($batchTimer), 2) . "s " . 
				"(" . round(Debug::timer($timer), 2) . "s " . $this->_('total') . ") " . 
				$this->getMemoryUsage()
				);
			if($batchTotal > 10) $this->echoMessages = false; 
		}

		$this->echoMessages = true; 

		if($numSaved) $this->message(sprintf($this->_('%d pages were modified and saved.'), $numSaved));
		if($numNotChanged && $this->ift) $this->message(sprintf($this->_('%d pages have been queued.'), $numNotChanged)); 
			else if($numNotChanged) $this->message(sprintf($this->_('%d pages were not modified.'), $numNotChanged)); 
		if($numSkipped) $this->message(sprintf($this->_('%d pages were skipped due to access control.'), $numNotSaved)); 

		$this->finishProcessActions($form); 
		$this->runningActions = false; 
		exit; 
	}

	/**
	 * Just update the modified time for the given $page to NOW
	 *
	 * @param Page $page
	 * @param int $time
	 *
	 */ 
	protected function updateModifiedTime(Page $page, $time = NULL) {
		if(is_null($time)) $time = time();
		$time = date('Y-m-d H:i:s', $time); 
		$sql = "UPDATE pages SET modified=:time, modified_users_id=:user_id WHERE id=:page_id"; 
		$query = $this->wire('database')->prepare($sql); 
		$query->bindValue(':page_id', $page->id); 
		$query->bindValue(':user_id', $this->wire('user')->id); 
		$query->bindValue(':time', $time); 
		try {
			$query->execute();
		} catch(Exception $e) {
			$this->error($e->getMessage());
		}
	}

	/**
	 * Hook for any actions that want to take place before the built-in functionality, perhaps to override or replaceit.
	 *
	 * This is what you'd use if you wanted to output a CSV of all results, for example. 
	 * You would need to perform your own $pages->find($selector). If you intend to override/replace this method
	 * make sure your hook is a before hook. 
	 *
	 */ 
	protected function ___beginProcessActions(InputfieldForm $form) {
		$this->runningActions = true; 
		set_time_limit(0); // ignore php timeout
		while(ob_get_level()) @ob_end_flush(); // remove output buffers
		$url = $this->wire('config')->urls->JqueryCore . 'JqueryCore.js';
		echo 	"<!DOCTYPE html><html><head>" . 
			"<script src='$url'></script>" . 
			"<script>function bot() { $('body')[0].scrollTop = $('body')[0].scrollHeight; }</script>" . 
			"<body>";
		$this->lister->executeViewport(false);
	}

	/*
	public function executeTest() {
		$this->runningActions = true; 
		$form = new InputfieldForm();
		$this->beginProcessActions($form, ''); 
		$this->message("Test 1"); sleep(1); 
		$this->message("Test 2"); sleep(1);
		$this->message("Test 3"); sleep(1);
		$this->finishProcessActions($form); 
		exit; 
	}
	*/

	/**
	 * Hook for any actions that want to take place after all actions are completed. 
	 *
	 */ 
	protected function ___finishProcessActions(InputfieldForm $form) {
		/*
		    _______       _      __             __
		   / ____(_)___  (_)____/ /_  ___  ____/ /
		  / /_  / / __ \/ / ___/ __ \/ _ \/ __  / 
		 / __/ / / / / / (__  ) / / /  __/ /_/ /  
		/_/   /_/_/ /_/_/____/_/ /_/\___/\__,_/   
		*/
		$finished = $this->_("FINISHED!"); 
		echo "\n$finished
		
		<script>
		var spinner=$(parent.document.getElementById('actions_spinner')); spinner.removeClass(spinner.attr('class')).addClass(spinner.attr('data-icon')); 
		var button=parent.document.getElementById('submit_refresh'); $(button).click();
		</script>
		";
		$this->message(' '); 
	}

	public function message($text, $flags = 0) {
		if($this->runningActions) {
			if($this->echoMessages) { 
				echo str_pad("<span>$text</span><!--", 2048, " ") . "--><script>bot();</script><br>";
				@ob_flush();
				flush();
			}
			return $this; 
		} else {
			return parent::message($text, $flags); 
		}
	}

	public function error($text, $flags = 0) {
		if($this->runningActions) {
			echo str_pad("<span style='color: red;'>$text</span><!--", 2048, " ") . "--><script>bot();</script><br>";
			@ob_flush();
			flush();
			return $this; 
		} else {
			return parent::error($text, $flags); 
		}
	}

	protected function initTable() {

		$database = $this->wire('database');	
		$query = $database->prepare("SHOW TABLES LIKE 'lister_actions'"); 
		$query->execute(); 

		if(!$query->rowCount()) {
			// create table
			$sql = 	"CREATE TABLE lister_actions (" . 
				"id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, " . 
				"created TIMESTAMP NOT NULL, " . 
				"data TEXT NOT NULL," . 
				"INDEX created (created)" . 
				")";
			$database->exec($sql); 

		} else {
			// delete old stale data
			$created = date('Y-m-d H:i:s', strtotime("-1 WEEK")); 
			$query = $database->prepare("DELETE FROM lister_actions WHERE created<:created"); 
			$query->bindValue(':created', $created); 
			$query->execute();
		}
	}


}
