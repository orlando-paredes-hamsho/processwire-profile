

var ProcessListerPro = {

	init: function() {

		var $viewport = $("#wrap_actions_viewport"); 
		$viewport.hide();

		$("#Inputfield_run_action").click(function() {
			var $form = $(this).parents('form'); 
			$form.attr('target', 'actions_viewport'); // change target for this submission
			$viewport.slideDown();
			if($viewport.is(".InputfieldStateCollapsed")) $viewport.find(".InputfieldHeader").click(); 
			setTimeout(function() { $form.attr('target', '_top'); }, 1000);  // restore target
			var $icon = $(this).find("i.fa"); 
			$icon.attr('id', 'actions_spinner').attr('data-icon', $icon.attr('class')).removeClass($icon.attr('class')).addClass("fa fa-lg fa-spinner fa-spin"); 
			return true; 
		}); 

		$("#submit_config").click(function() {
			window.location.href = './config/';
			return false;
		});

		$("#_ProcessListerConfigTab").unbind('click').attr('href', './config/'); 

		/*
		// @todo make the Add New button modal as an option
		setTimeout(function() {
			$("a.PageAddNew, a.PageAddNew button").unbind('click');
			$("a.PageAddNew").click(function(event) {
				event.PageAddNew = this; 
				ProcessListerPro.modalClick(event); 
				return false;
			}); 
		}, 1500); 
		*/

		$(document).on('click', 'a.PageEdit.modal, a.PageView.modal', ProcessListerPro.modalClick); 
		$(document).on('click', 'a.actions_toggle', ProcessListerPro.pageClick);

		ProcessLister.results.on('loaded', function() {
			ProcessListerPro.pageClick(); // refresh counters
		}); 

		if($('#ListerProConfigForm').length > 0) $("body").addClass('ListerProConfig'); 

	},

	refreshLister: false, // true when lister should refresh after a dialog close

	modalClick: function(event) {

		var $a = $(this);
		// if("PageAddNew" in event) $a = $(event.PageAddNew);
		
		var isEditLink = $a.hasClass('PageEdit') || $a.hasClass('PageAddNew'); 
		var href = $a.attr('href'); 
		var url = href + (isEditLink ? '&modal=1' : '');
		var closeOnSave = true; 
		var $iframe = $('<iframe class="ListerDialog" frameborder="0" src="' + url + '"></iframe>');
		var windowWidth = $(window).width()-100;
		var windowHeight = isEditLink ? $(window).height()-220 : $(window).height()-160; 
		var dialogPageID = 0;

		if(isEditLink) ProcessLister.clickAfterRefresh = $a.parents('.actions').siblings('.actions_toggle').attr('id'); 

		var $dialog = $iframe.dialog({
			modal: true,
			height: windowHeight,
			width: windowWidth,
			position: [50,49],
			close: function(event, ui) {
				if(!ProcessListerPro.refreshLister) return;
				var $refresh = ProcessLister.results.find(".MarkupPagerNavOn a"); 
				if($refresh.size() == 0) $refresh = $("#submit_refresh"); 
				$refresh.click();
				ProcessListerPro.refreshLister = false; 
			}
		}).width(windowWidth).height(windowHeight);

		$iframe.load(function() {

			var buttons = []; 	
			//$dialog.dialog('option', 'buttons', {}); 
			var $icontents = $iframe.contents();
			var n = 0;
			var title = $icontents.find('title').text();

			dialogPageID = $icontents.find('#Inputfield_id').val(); // page ID that will get added if not already present

			// set the dialog window title
			$dialog.dialog('option', 'title', title); 
			
			if(!isEditLink) return;

			// hide things we don't need in a modal context
			//$icontents.find('#wrap_Inputfield_template, #wrap_template, #wrap_parent_id').hide();
			$icontents.find('#breadcrumbs ul.nav, #_ProcessPageEditChildren').hide();

			closeOnSave = $icontents.find('#ProcessPageAdd').size() == 0; 

			// copy buttons in iframe to dialog
			$icontents.find("#content form button.ui-button[type=submit]").each(function() {
				var $button = $(this); 
				var text = $button.text();
				var skip = false;
				// avoid duplicate buttons
				for(i = 0; i < buttons.length; i++) {
					if(buttons[i].text == text || text.length < 1) skip = true; 
				}
				if(!skip) {
					buttons[n] = {
						'text': text, 
						'class': ($button.is('.ui-priority-secondary') ? 'ui-priority-secondary' : ''), 
						'click': function() {
							$button.click();
							if(closeOnSave) setTimeout(function() { 
								ProcessListerPro.refreshLister = true; 
								$dialog.dialog('close'); 
							}, 500); 
							closeOnSave = true; // only let closeOnSave happen once
						}
					};
					n++;
				}; 
				$button.hide();
			}); 

			$icontents.find("#submit_delete").click(function() {
				ProcessListerPro.refreshLister = true; 
				setTimeout(function() {
					$dialog.dialog('close'); 
				}, 500); 
			}); 

			// cancel button
			/*
			buttons[n] = {
				'text': 'Cancel', 
				'class': 'ui-priority-secondary', 
				'click': function() {
					$dialog.dialog('close'); 
				}
			}; 
			*/

			if(buttons.length > 0) $dialog.dialog('option', 'buttons', buttons); 
			$dialog.width(windowWidth).height(windowHeight);
		}); 
		return false; 
	},

	pageClick: function() {

		var $wrap_actions_items = $("#wrap_actions_items"); 
		var $counter = $("#lister_open_cnt"); 
		var $counter2 = $("#lister_open_cnt2"); 
		var $openItems = ProcessLister.results.find("tr.open");
		var cnt = $openItems.size();

		if(!$counter2.size()) {
			$counter2 = $("<span id='lister_open_cnt2'></span>"); 
			$("#actions_items_open").after($counter2); 
		}

		$counter.find('span').text(cnt); 
		$counter2.html('&nbsp;' + cnt); 

		if(cnt > 0) {
			var ids = []; 
			$openItems.each(function(n) {
				var $a = $(this).find("a.actions_toggle"); 
				ids[n] = $a.attr('id').replace('page', ''); 
			}); 

			$counter.show();
			if($wrap_actions_items.hasClass('InputfieldStateCollapsed')) {
				$wrap_actions_items.removeClass('InputfieldStateCollapsed'); 
			}
			$("#actions_items_all").removeAttr('checked'); 
			$("#actions_items_open")
				.removeAttr('disabled')
				.attr('checked', 'checked')
				.val(ids.join(','))
				.parent('label')
					.removeClass('ui-state-disabled'); 
		} else {
			$counter.hide();
			$("#actions_items_open")
				.removeAttr('checked')
				.attr('disabled', 'disabled')
				.val('')
				.parent('label')
					.addClass('ui-state-disabled'); 
			$("#actions_items_all").attr('checked', 'checked'); 
			if(!$wrap_actions_items.hasClass('InputfieldStateCollapsed')) {
				$wrap_actions_items.addClass('InputfieldStateCollapsed'); 
			}
		}

		return false; 
	}
};

$(document).ready(function() {
	ProcessLister.init();
	ProcessListerPro.init();
}); 
