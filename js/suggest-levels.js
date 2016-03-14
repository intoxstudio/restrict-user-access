/*!
 * @package Restrict User Access
 * @copyright Joachim Jensen <jv@intox.dk>
 * @license GPLv3
 */

(function($) {
	var rua_edit = {
		/**
		 * Suggest levels input
		 *
		 * @since  0.11
		 * @return {void}
		 */
		suggestLevels: function() {
			var $elem = $('.js-rua-levels');
			$elem.select2({
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
	$(document).ready(function(){rua_edit.suggestLevels();});
})(jQuery);
