/**
 * Form Builder Process Javascript
 *
 * JS used by the ProcessFormBuilder module
 *
 * Copyright (C) 2012 by Ryan Cramer 
 *
 * PLEASE DO NOT DISTRIBUTE
 *
 */

$(document).ready(function() {

	// remove scripts, because they've already been executed since we are manipulating the DOM below (WireTabs)
	// which would cause any scripts to get executed twice
	$("#ProcessFormBuilder").find("script").remove();

	var $formFields = $('#form_fields');

        // asmSelect fieldgroup indentation
        var formFieldsChange = function() {
                $ol = $formFields.prev('ol.asmList');
                $ol.find('span.asmFieldsetIndent').remove();
                $ol.children('li').children('span.asmListItemLabel').children("a:contains('_END')").each(function() {
                        var label = $(this).text();
                        if(label.substring(label.length-4) != '_END') return;
                        label = label.substring(0, label.length-4);
                        var $li = $(this).parents('li.asmListItem');
                        $li.addClass('asmFieldset asmFieldsetEnd');
                        while(1) {
                                $li = $li.prev('li.asmListItem');
                                if($li.size() < 1) break;
                                var $span = $li.children('span.asmListItemLabel'); 
                                var label2 = $span.text();
                                if(label2 == label) {
                                        $li.addClass('asmFieldset asmFieldsetStart');
                                        break;
                                }
                                $span.prepend($('<span class="asmFieldsetIndent"></span>'));
                        }
                });
        };

        $formFields.change(formFieldsChange).bind('init', formFieldsChange);

	// $("#ProcessFormBuilder > ul.Inputfields").WireTabs({ 
	$("#ProcessFormBuilder").WireTabs({ 
		items: $('.WireTab')
	});

	function formBuilderViewport(id) {
		
		var formName = $("#form_name").val();
		var viewportID = 'FormBuilderViewport_' + formName; 
		if($('#' + viewportID).size() > 0) return;

		var href = $('#' + id).find('a').attr('href');
		var $iframe = $("<iframe frameborder='0' class='FormBuilderViewport' id='" + viewportID + "' data-name='" + formName + "'></iframe>").attr('src', href);
		var $note = $("<h2>Loading&hellip;</h2>"); 

		$("#" + id).css('margin-top', '1px').prepend($iframe).find(".InputfieldContent, .ui-widget-content").remove();

		if($iframe) { 
			$iframe.before($note);
			//$iframe.css('opacity', '0');
			$iframe.attr('src', href).width('100%');
			$iframe.load(function() {
				//$iframe.animate({ opacity: 1.0 }, 500);
				$note.remove();
			});
		}

	}

	$("#_ProcessFormBuilderView").click(function(e) {
		formBuilderViewport('ProcessFormBuilderView'); 
		return false;
	}); 

	$("#_ProcessFormBuilderEntries").attr('href', '../listEntries/?id=' + $('#form_id').attr('value'));
	
	$("textarea.code").click(function() { $(this).select();	});

	$("#_ProcessFormBuilderEmbed").click(function() { 
		$.get("../embedForm?id=" + $("#form_id").val(), function(data) {
			$("#ProcessFormBuilderEmbedMarkup").html(data);
			$(".ProcessFormBuilderAccordion").accordion({ autoHeight: false, heightStyle: 'content' });
		}); 
	});

	$("#_ProcessFormBuilderExport").click(function() {
		$.get("../exportForm?id=" + $("#form_id").val(), function(data) {
			$("#ProcessFormBuilderExportJSON").val(data);
		}); 
	}); 

	$columnWidth = $("#columnWidth"); 
	if($columnWidth.size() > 0 && parseInt($columnWidth.val()) < 1) $columnWidth.val('100'); 


	// submit/save settings
	if($("#fieldsetActions").size() > 0) { 
		var saveFlagsChange = function() {
			//if($("#form_saveFlags_1").is(":checked")) $("#wrap_form_listFields").slideDown('fast', function() { $(this).effect('highlight', 500); });		
			if($("#form_saveFlags_1").is(":checked")) $("#wrap_form_listFields").slideDown('fast');
				else $("#wrap_form_listFields").hide();
			if($("#form_saveFlags_2").is(":checked")) $("#fieldsetEmail").slideDown('fast');
				else $("#fieldsetEmail").hide();
			if($("#form_saveFlags_4").is(":checked")) $("#fieldset3rdParty").slideDown('fast');
				else $("#fieldset3rdParty").hide();
			if($("#form_saveFlags_8").is(":checked")) $("#fieldsetSavePage").slideDown('fast');
				else $("#fieldsetSavePage").hide();
			if($("#form_saveFlags_32").is(":checked")) $("#fieldsetSpam").slideDown('fast');
				else $("#fieldsetSpam").hide();
			if($("#form_saveFlags_64").is(":checked")) $("#fieldsetResponder").slideDown('fast');
				else $("#fieldsetResponder").hide();

			if($("#form_saveFlags_16").is(":checked")) {
				$("#fieldsetSubmitTo").slideDown('fast');
				$("#wrap_form_listFields").hide();
				$("#fieldsetEmail").hide();
				$("#fieldsetResponder").hide();
				$("#fieldset3rdParty").hide();
				$("#fieldsetSavePage").hide();
				$("#fieldsetSpam").hide();
				$("#wrap_form_saveFlags").find("input[value!=16]").each(function() { 
					$(this).removeAttr('checked').attr('disabled', 'disabled'); 
				}); 
			} else {
				$("#fieldsetSubmitTo").hide();
				//$("#fieldsetSpam").show();
				$("#wrap_form_saveFlags").find('input:disabled').removeAttr('disabled');
			}
		}; 
		$("#wrap_form_saveFlags").find('input').change(saveFlagsChange);	
		saveFlagsChange();
	}

	// listEntries
	$("#check_all").click(function() {
		var $checkboxes = $("input[type=checkbox].delete"); 
		if($(this).is(":checked")) $checkboxes.attr('checked', 'checked'); 
			else $checkboxes.removeAttr('checked');
	});

	$("#submit_delete_entries").click(function() {
		return confirm($(this).val());
	});

	/*
	$(".FormBuilderDialog").click(function() {

		var $dialog = $("<div class='FormBuilderDialog'></div>"); 
		var windowWidth = $(window).width()-300;
		var windowHeight = $(window).height()-300;
		var browserTitle = $(this).attr('title');
		if(windowHeight > 800) windowHeight = 800;

		var $iframe = $("<iframe />").attr("src", $(this).attr("href")).attr('frameborder', 0).css({ 
			'border': 'none',
			'display': 'inline',
			'min-height': '0px', 
			'max-height': 'none',
			'width': '100%',
			'height': '99%',
			'title': browserTitle
		}); 
		
		$dialog.append($iframe).dialog({
			modal: true, 
			width: windowWidth,
			height: windowHeight,
			position: [150,80]
		}).width(windowWidth).height(windowHeight); 

		$iframe.load(function() {
			var $icontents = $iframe.contents();
			if(!browserTitle.length) browserTitle = $icontents.find('head title').text();
			$dialog.dialog('option', 'title', browserTitle); 
			$dialog.width(windowWidth).height(windowHeight); 
		}); 

		return false;
	}); 
	*/

}); 

