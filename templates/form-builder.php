<?php

/**
 * ProcessWire Form Builder Template File
 *
 * This template file handles display of a form within an iframe, for embed
 * options A and B. It corresponds with template 'form-builder' in ProcessWire.
 *
 * This template file should be placed in /site/templates/form-builder.php,
 * or you may make it a symlink to /site/modules/FormBuilder/form-builder.php
 *
 */

if(!defined("PROCESSWIRE")) die();

?><!DOCTYPE html>
<html lang="<?php echo __('en', __FILE__); // HTML tag lang attribute
	/* this intentionally on a separate line */ ?>"> 
<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8" />
	<meta name="robots" content="noindex, nofollow" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
	<title><?php echo $page->title; ?></title>
	<?php
	$forms->setTemplateVersion(2); 
	$form = $forms->getFromURL(true);
	echo $form->styles;
	echo $form->scripts; 
	?>
	
</head>
<body>
	<div id="content" class="content">
		<div class="container">
			<?php echo $form; ?>
		</div>
	</div>
</body>
</html>
