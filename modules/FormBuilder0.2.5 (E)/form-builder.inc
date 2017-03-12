<?php

/**
 * If you want to add-on runtime hooks, validation or additional functionality to Form Builder,
 * place this file in /site/templates/form-builder.inc
 *
 * It will be included by /site/templates/form-builder.php when present. 
 *
 */

if(!defined("PROCESSWIRE")) die();

/**
 * Hook example for custom validation or manipulation of values
 * 
 */
$forms->addHookAfter('InputfieldForm::processInput', null, 'hookProcessInput'); 

function hookProcessInput(HookEvent $event) {
        $form = $event->object;
        if($form->name != 'contact') return; // if it's not the form you want, return
        $firstName = $form->get('first_name'); // the name of the field you want to check
        if($firstName->value == 'Ryan') { // we are checking to see if first_name is 'Ryan'
                $firstName->attr('value', 'Brian'); // if it is, we are changing it
		// this next line causes an error to the user, directly in the first_name field
                $firstName->error("Sorry, we don't allow people named Ryan so we changed it to Brian"); 
        }
}
