<?php

/**
 * This is the email template used by the 'Email administrators' feature in Form Builder
 *
 * CUSTOMIZE
 * =========
 * To customize this email, copy this file to /site/templates/FormBuilder/email-administrator.php and modify it as needed.
 * It's preferable to do this so that your email template doesn't get overwritten during FormBuilder upgrades.
 * Inline styles are recommended in the markup since not all email clients will use <style></style> declarations.
 *
 * VARIABLES
 * =========
 * @var array $values This is an array of all submitted field values with ('field name' => 'field value') where the 'field value' is ready for output.
 * @var array $labels This is an array of all field labels with ('field name' => 'field label') where the 'field label' is ready for output.
 * @var array $formData Raw form data array, which is the same as $values but unformatted and with additional properties like 'entryID' and '_savePage' id. 
 * @var InputfieldForm $form Containing the entire form if you want grab anything else from it.
 *
 *
 */

if(!defined("PROCESSWIRE")) die();

?><!DOCTYPE html>
<html>
<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8" />
	<title><?php echo $form->name; ?> email</title>
</head>
<body>
	<table style='width: 100%; border-bottom: 1px solid #ccc;' cellspacing='0'>

		<?php foreach($values as $name => $value): ?>

		<tr>
			<th class='label' style='width: 30%; text-align: right; font-weight: bold; padding: 10px 10px 10px 0; vertical-align: top; border-top: 1px solid #ccc;'>
				<?php echo $labels[$name]; ?>
			</th>
			
			<td class='value' style='width: 70%; padding: 10px 0 10px 0; border-top: 1px solid #ccc;'>
				<?php echo $value; ?>
			</td>
		</tr>

		<?php endforeach; ?>

	</table>
	
	<p><small><?php echo __('Sent by ProcessWire Form Builder'); ?> &bull; <?php echo date('Y/m/d g:ia'); ?></small></p>

</body>
</html>
