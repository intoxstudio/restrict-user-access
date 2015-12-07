/*!
 * @package Restrict User Access
 * @copyright Joachim Jensen <jv@intox.dk>
 * @license GPLv3
 */

(function($) {

	var rua_edit = {

		current_section: 0,
		sections: [],

		/**
		 * Initiator
		 *
		 * @since  0.4
		 * @return {void}
		 */
		init: function() {
			this.initTabSections();
			this.toggleMembersTab();
			this.setCurrentSection(window.location.hash);
			this.tabController();
			this.suggestUsers();
			$(".rua-cb input")
			.on("change",function() {
				var $this = $(this);
				var isChecked = $this.prop("checked");
				var $sum = $(".sum-"+$this.val());
				$sum.text(parseInt($sum.text()) + (1 * (isChecked ? 1 : -1)));

				$this.toggleClass("checked",isChecked);

				if(isChecked) {
					$("input[name='"+$this.attr("name")+"']:checked")
					.not($this)
					.prop("checked",false)
					.trigger("change");
				}
			});
			$(".rua-cb input:checked").each(function() {
				var $this = $(this);
				var $sum = $(".sum-"+$this.val());
				$sum.text(parseInt($sum.text()) + 1);
				$this.addClass("checked");
			});
		},

		initTabSections: function() {
			$(".js-rua-tabs").find(".nav-tab").each(function() {
				var start = this.href.lastIndexOf("#");
				if(start >= 0) {
					var section = this.href.substr(start);
					rua_edit.sections.push(section);
					$(section).find("input, select").attr("disabled",true);
				}
			});
		},

		/**
		 * Suggest users input
		 *
		 * @since  0.6
		 * @return {void}
		 */
		suggestUsers: function() {
			var post_id = $(".js-rua-post-id").val();
			$('.js-rua-user-suggest').select2({
				placeholder:"Search for Users...",
				minimumInputLength: 1,
				closeOnSelect: false,
				multiple: true,
				width:"250",
				ajax: {
					url: ajaxurl+"?action=rua/user/suggest&post_id="+post_id,
					dataType: 'json',
					quietMillis: 250,
					data: function (term, page) {
						return {
							q: term
						};
					},
					results: function (data, page) {
						var results = [];
						for(var i = data.length-1; i >= 0; i--) {
							results.push({
								id:data[i].ID,
								text:data[i].user_login+" ("+data[i].user_email+")"
							});
						}
						return {results:results};
					}
				},
			});
		},

		/**
		 * Toggle Members tab based on
		 * role sync
		 *
		 * @since  0.4
		 * @return {void}
		 */
		toggleMembersTab: function() {
			$("#rua-options .role").on("change","select", function(e) {
				var isNotRole = $(this).val() == -1;
				$(".js-rua-tabs").find(".nav-tab").eq(1).toggle(isNotRole);
				$(".js-rua-drip-option").closest("div").toggle(isNotRole);
				$(".duration").toggle(isNotRole);
			});
			$("#rua-options .role select").change();
		},

		/**
		 * Manage tab clicks
		 *
		 * @since  0.4
		 * @return {void}
		 */
		tabController: function() {
			$(".js-rua-tabs")
			.on("click",".nav-tab",function(e) {
				rua_edit.setCurrentSection(this.href);
			})
			.one("click",".nav-tab",function(e) {
				//make sure empty check for meta boxes
				//is done while visible
				if(rua_edit.current_section === 0 && postboxes && _.isFunction(postboxes._mark_area)) {
					postboxes._mark_area();
				}
			});
		},

		/**
		 * Find section index based on
		 * hash in a URL string
		 *
		 * @since  0.4
		 * @param  {string} url
		 * @return {int}
		 */
		findSectionByURL: function(url) {
			var section = this.sections.indexOf(url.substring(url.lastIndexOf("#")));
			return section >= 0 ? section : null;
		},

		/**
		 * Set and display current section and tab
		 * hide previous current section
		 *
		 * @since 0.4
		 * @param {string} url
		 */
		setCurrentSection: function(url) {
			var section = this.findSectionByURL(url),
				$tabs = $(".js-rua-tabs").find(".nav-tab");
			if(section !== null && $tabs.eq(section).is(":visible")) {
				$(this.sections[this.current_section])
				.hide()
				.find("input, select").attr("disabled",true);
				this.current_section = section;
				$(this.sections[this.current_section])
				.show()
				.find("input, select").attr("disabled",false);

				$tabs.removeClass("nav-tab-active");
				$tabs.eq(this.current_section).addClass("nav-tab-active");
			}
		}

	};
	$(document).ready(function(){rua_edit.init();});
})(jQuery);
