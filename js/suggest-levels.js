/*!
 * @package Restrict User Access
 * @copyright Joachim Jensen <jv@intox.dk>
 * @license GPLv3
 */

(function($) {
	var rua_edit = {

		init: function() {
			this.suggestLevels();
			this.lazyInitsuggestLevels();
		},
		/**
		 * Suggest levels input
		 *
		 * @since  0.11
		 * @return {void}
		 */
		suggestLevels: function() {
			//select2 requires to loop through
			$('.js-rua-levels').each(function() {
				rua_edit.createDropdown($(this));
			});
		},

		/**
		 * Suggest levels input for new menu items
		 * Instantiates first time user clicks edit
		 *
		 * @since  0.12.1
		 * @return {void}
		 */
		lazyInitsuggestLevels: function() {
			$('#menu-to-edit').on('click','.item-edit', function(e) {
				var $parent = $(this).closest('.menu-item');
				//inactive -> active
				if($parent.hasClass('menu-item-edit-inactive')) {
					var $input = $parent.find('.js-rua-levels');
					if(!$input.data('select2')) {
						rua_edit.createDropdown($input);
					}
				}
			});
		},

		/**
		 * Instantiate select2 for dropdown
		 *
		 * @since  0.12.1
		 * @param  {object} $elem
		 * @return {void}
		 */
		createDropdown: function($elem) {
			$elem.select2({
				containerCssClass:'cas-select2',
				dropdownCssClass: 'cas-select2',
				placeholder: RUA.search,
				minimumInputLength: 0,
				closeOnSelect: true,//does not work properly on false
				allowClear:true,
				multiple: true,
				width:"resolve",
				nextSearchTerm: function(selectedObject, currentSearchTerm) {
					return currentSearchTerm;
				},
				data: RUA.levels
			})
			.on("select2-selecting",function(e) {
				$elem.data("forceOpen",true);
			})
			.on("select2-close",function(e) {
				if($elem.data("forceOpen")) {
					e.preventDefault();
					$elem.select2("open");
					$elem.data("forceOpen",false);
				}
			});
		}
	};
	$(document).ready(function() {
		rua_edit.init();
	});
})(jQuery);
