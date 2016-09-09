(function (window, document, $) {
	"use strict";
	$(function () {
		// Default keybindings for the document and form elements
		// These keyboard shorcut combinations are made available by jQuery.hotkeys that is bundled with CA
		// https://github.com/jeresig/jquery.hotkeys
		var keyBindings = {
			// Save
			'keydown.ctrl_s keydown.alt_s keydown.ctrl_return': function () {
				$(this).blur();
				$(".sectionBox>form:first-child").submit();
				return false;
			},
			// Next record in results
			'keydown.ctrl_right': function () {
				$('.nextPrevious a.next')[0].click();
				return false;
			},
			// Previous record in results
			'keydown.ctrl_left': function () {
				$('.nextPrevious a.prev')[0].click();
				return false;
			},
			// Previous Record
			'keydown.alt_r': function () {
				$('.resultCount a')[0].click();
				return false;
			},
			// Previous Screen
			'keydown.ctrl_up': function () {
				$('#leftNavSidebar').find('h2:has(a.sf-menu-selected)').prev().find('a')[0].click();
				return false;
			},
			// Next Screen
			'keydown.ctrl_down': function () {
				$('#leftNavSidebar').find('h2:has(a.sf-menu-selected)').next().find('a')[0].click();
				return false;
			},
			// Jump to screens list
			'keydown.alt_j': function () {
				$('#leftNavSidebar').find('h2:has(a.sf-menu-selected)').next().find('a')[0].focus();
				return false;
			},
			// Expand all collapsed fields
			'keydown.alt_e': function () {
				caBundleVisibilityManager.open();
				return false;
			},
			// Collapse all fields
			'keydown.alt_c': function () {
				caBundleVisibilityManager.close();
				return false;
			},
			// Focus the first element in popup
			'keydown.ctrl_f': function () {
				$('#editorFieldList').find('a:first-child').focus();
				return false;
			},
			// Duplicate the current record
			'keydown.alt_p': function () {
				$('#DuplicateItemForm').submit();
				return false;
			}
		};
		$(document).on($.extend({
			// Go to the quickfind box. We don't want this shortcut bound in inputs for fairly obvious reasons
			'keydown./ keydown.ctrl_/': function () {
				$('#caQuickSearchFormText').focus();
				return false;
			},
			'keydown.alt_w': function () {
				$('.bundleLabel').toggleClass('fullWidth');
				return false;
			}
		}, keyBindings));
		var fixTabIndex = function (i) {
			$(this).attr('tabindex', i + 1);
		};

		setTimeout(function(){
			// Using a timeout because related item bundles only appear after this.
			$('form[id$="EditorForm"] :input').on($.extend({
				'keydown.ctrl_del': function () {
					var bundleLabel = $(this).parents('.bundleLabel');
					bundleLabel.find('.caDeleteLabelButton,.caDeleteItemButton')[0].click();
					bundleLabel.find('.caAddItemButton a,caAddLabelButton a')[0].focus();
					return false;
				},
				'keydown.alt_w': function () {
					$(this).parents('.bundleLabel').toggleClass('cell-24');
					return false;
				},
				'keydown.esc': function () {
					$(this).blur();
					// ESC might be cancelling something else
					return true;
				},
				'keydown.return': function () {
					var $input = $(this);
					if ($input.innerHeight() <= 40){
						// deal with enter key in textareas
						$input.blur();
						return false;
					} else {
						return true;
					}
				}
			}, keyBindings)).each(fixTabIndex);
		}, 300);
	});
})(window, document, jQuery);
