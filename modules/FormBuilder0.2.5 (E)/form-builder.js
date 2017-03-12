/**
 * Common JS used by form-builder template file.
 * 
 */

/**
 * Size viewport 
 * 
 */
function sizeFormBuilderViewport() {
	var formName = $("form.InputfieldForm[id^=FormBuilder_]").attr('name');
	if(!formName) formName = $("#FormBuilderSubmitted").attr('data-name');
	var viewport = parent.document.getElementById('FormBuilderViewport_' + formName);
	if(typeof viewport !== 'undefined' && viewport) {
		var $viewport = $(viewport); 
		// optional data-pad-bottom attribute that can be specified with the viewport
		// to reduce or increase the amount of default bottom padding (to prevent scrollbars or hidden content)
		// var bottom = $viewport.attr('data-pad-bottom');
		var targetHeight = $("#content").height();
		if(Math.abs(targetHeight - $viewport.height()) > 2 || Math.abs($viewport.height() - targetHeight) > 2) {
			$(viewport).height(targetHeight); 
		}
		$(viewport).attr('scrolling', 'no');
	}
}
	
function setupFormBuilderEditLinks() {
	var url = $("#FormBuilderPreview").val();
	$(".Inputfield").each(function() {
		var $label = $(this).children("label.ui-widget-header[for], label.InputfieldHeader[for]").eq(0);
		var forID = $label.attr('for');
		if(!forID) return;
		var $input = $(this).find('#' + forID);
		var name = $input.attr('name');
		if(typeof name == "undefined" || !name) return;
		if(name.indexOf('[') > 0) name = name.substring(0, name.indexOf('['));
		var $edit = $(
			"<a class='FormBuilderEditField' title='Edit Field' href='" + url + name + "'>" +
			"<span class='ui-icon ui-icon-pencil'></span></a>").click(function(e) {
				e.stopPropagation();
				window.top.location.href = $edit.attr('href');
			});
		$label.append($edit);
	});
}

function setupFormBuilderSubmitted() {
	// if form submitted, we will scroll to it's place in the page
	if(window.parent.jQuery) {
		var formName = $("form.InputfieldForm[id^=FormBuilder_]").attr('name'); // @todo
		if(!formName) formName = $("#FormBuilderSubmitted").attr('data-name');
		var $viewport = window.parent.jQuery('#FormBuilderViewport_' + formName);
		if($viewport.length) {
			var y = $viewport.offset().top;
		} else {
			var y = window.parent.jQuery('#FormBuilderSubmitted').offset().top;
		}
		window.parent.jQuery("body").animate( { scrollTop: y }, 'slow'); 
	} else {
		// scroll just to top if no jQuery to use
		window.parent.window.scrollTo(0,0);
	}
}

function initFormBuilderLegacy() {
	// legacy framework
	$(".Inputfields > .Inputfield > .ui-widget-header").click(function () {
		// resize the viewport when they open/collapse fields
		setTimeout('sizeFormBuilderViewport()', 250);
	});

	$("select.asmSelect").change(function () {
		// resize when items are added to an asmSelect, which adjusts the form height
		setTimeout('sizeFormBuilderViewport()', 50);
	});

	// size the viewport at the beginning of the request
	sizeFormBuilderViewport();

	$(window).resize(function (e) {
		setTimeout('sizeFormBuilderViewport()', 250);
	});

	// edit links, currently in legacy frameworks only
	if($("#FormBuilderPreview").length) setupFormBuilderEditLinks();
}

function initFormBuilder() {
	// non-legacy frameworks
	
	$(".Inputfields > .Inputfield > .InputfieldHeader").click(function () {
		// resize the viewport when they open/collapse fields
		setTimeout('sizeFormBuilderViewport()', 250);
	});

	$("select.asmSelect").change(function () {
		// resize when items are added to an asmSelect, which adjusts the form height
		setTimeout('sizeFormBuilderViewport()', 50);
	});

	// size the viewport at the beginning of the request
	sizeFormBuilderViewport();

	$(window).resize(function (e) {
		setTimeout('sizeFormBuilderViewport()', 50);
	});
} 

$(document).ready(function() {
	if($(".FormBuilderFrameworkLegacy").length) {
		initFormBuilderLegacy();
	} else {
		initFormBuilder();
	}
	if($("#FormBuilderSubmitted").length) setupFormBuilderSubmitted();
}); 
