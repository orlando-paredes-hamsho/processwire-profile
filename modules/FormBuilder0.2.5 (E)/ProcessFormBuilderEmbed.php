<?php 

/**
 * This view serves as the contents of the ProcessFormBuilder 'embed' tab. 
 *
 */

if(!defined("PROCESSWIRE")) throw new WireException("This file may not be accessed directly "); 

$theme = $form->theme ? $form->theme : 'default';

$embedFields = wire('modules')->get('FormBuilder')->embedFields;
$embedFieldsStr = '';

foreach($embedFields as $field_id) {
	$field = wire('fields')->get((int) $field_id);
	if(!$field) continue; 
	$embedFieldsStr .= "<u>{$field->label}</u>, ";
}
$embedFieldsStr = rtrim($embedFieldsStr, ", "); 

?>

<div class='ProcessFormBuilderAccordion'>
	<h5><a href='#'><b><?php echo __('Option A: Easy Embed', __FILE__); ?></b></a></h5>
	<div>
		<?php if(count($embedFields)): ?>

		<p>
		<b><?php echo __('Paste a tag into your text where you want the form to appear.', __FILE__); ?></b>
		<?php echo sprintf(__('This is the easiest method and requires you do nothing other than edit a page and paste in (or type) a tag. You can just copy and paste the following tag where you want your form to appear in %s.', __FILE__), $embedFieldsStr); ?>
		</p>

		<p><textarea class='code' rows='1'><?php echo $embedTag . '/' . $form->name; ?></textarea></p>

		<p>
		<?php echo __('Note that the tag above must be pasted (or typed) into a paragraph or a headline (p, h1, h2, h3, h4) and be the only thing in it.', __FILE__); ?>
		<?php echo __('Save the page and view it, and you should see your form.', __FILE__); ?>
		</p>

		<p class='detail'><?php echo __('If you want to support this easy embed option in other fields, you may add more from the Form Builder module settings.', __FILE__); ?></p>
		
		<?php else: ?>

		<p><?php echo __('This embed option cannot be used because no embed fields have been defined in your Form Builder module settings. Please edit the Form Builder module settings and check the box for at least one field.', __FILE__); ?></p>

		<?php endif; ?>
	</div>

	<h5><a href='#'><b><?php echo __('Option B: Template Embed', __FILE__); ?></b></a></h5>
	<div>
		<p>
		<b><?php echo __('Paste an embed code into your template file.', __FILE__); ?></b>
		<?php echo __('Use this option if you want the form to be loaded from a template file rather than from a field.', __FILE__); ?> 
		<?php echo __('This provides you with more defined placement options than option A, but requires editing a template file.', __FILE__); ?> 
		<?php echo __('Copy and paste the following directly into your template file(s) where you want the form to appear:', __FILE__); ?>
		</p>

		<p><textarea class='code' rows='1'>&lt;?php echo $forms->embed('<?php echo $form->name; ?>'); ?&gt;</textarea></p>
	</div>

	<h5><a href='#'><b><?php echo __('Option C: Custom Embed', __FILE__); ?></b></a></h5>
	<div>
		<p>
		<b><?php echo __('Render the form markup directly from your template file (no iframe).', __FILE__); ?></b> 
		<?php echo __('This option is recommended for those that are already using a compatible CSS framework, or those that do not mind resolving potential CSS conflicts between your site styles and those used by the form.', __FILE__); ?>
		<?php echo __('It renders the form markup directly in the page, which is either a good thing or a bad thing, depending on what you want.', __FILE__); ?>
		<?php echo __('If already using one of the compatible CSS frameworks (Uikit, Foundation, Bootstrap) you may find embed method C to be ideal, as the markup will be ready for your framework.', __FILE__); ?>
		<?php echo __('To proceed, copy and paste the following code into your template file(s) where appropriate.'); ?>
		</p>
		<p><b>1. <?php echo __('Place the following somewhere before output begins (like in an _init.php file, or top of a template file).', __FILE__); ?></b></p>
		<p><textarea class='code' rows='1'>&lt;?php $form = $forms->render('<?php echo $form->name; ?>'); ?&gt;</textarea></p>
		<p><b>2. <?php echo $sanitizer->entities(__('Place the following in your document <head></head> section, wherever you output CSS files (styles) and JS files (scripts).', __FILE__)); ?></b>
		<?php echo __('You may split these two lines as needed, or you may combine with the line mentioned above.', __FILE__); ?></b></p>
		<p><textarea class='code' rows='2'>&lt;?php echo $form->styles; ?&gt;
&lt;?php echo $form->scripts; ?&gt;</textarea></p>
		<p><b>3. <?php echo $sanitizer->entities(__('Place the following somewhere later in your document <body>, where you want your form to be rendered:', __FILE__)); ?></b></p>
		<p><textarea class='code' rows='1'>&lt;?php echo $form; ?&gt;</textarea></p>
	</div>
</div>
<script type='text/javascript'>$("textarea.code").click(function() { $(this).select()});</script>

