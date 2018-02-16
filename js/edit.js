/*!
 * @package Restrict User Access
 * @author Joachim Jensen <jv@intox.dk>
 * @license GPLv3
 * @copyright 2018 by Joachim Jensen
 */

(function($) {
	"use strict";

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
			this.suggestUsers();
			this.suggestPages();
			this.actionRoleHandler();
			this.tabController();
			this.capController();
		},

		suggestPages: function() {
			var $elem = $('.js-rua-page'),
				rootUrl = $elem.data("rua-url");
			$elem.select2({
				theme:'wpca',
				minimumInputLength: 0,
				closeOnSelect: true,
				allowClear:false,
				width:"250px",
				//tags: CAS.canCreate, defined in html for 3.5 compat
				ajax:{
					delay: 400,
					url: ajaxurl,
					data: function (params) {
						var query = {
							search: params.term || '',
							action: 'rua/page/suggest',
							paged: params.page || 1
						}
						return query;
					},
					dataType: 'JSON',
					type: 'POST',
					processResults: function (data, params) {
						return {
							results: data,
							pagination: {
								more: !(data.length < 20)
							}
						};
					}
				},
				createTag: function (params) {
					var term = $.trim(params.term.replace(rootUrl,''));
					if (term === '') {
						return null;
					}
					return {
						id: term,
						text: term,
						new: true
					}
				},
				templateResult: function(term) {
					if(term.new) {
						return $('<span>').html('<b>Custom Link:</b> ' + term.text);
					}
					return term.text;
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
			var post_id = $("#post_ID").val();
			var $elem = $('.js-rua-user-suggest');
			$elem.select2({
				theme:'wpca',
				cachedResults: {},
				quietMillis: 400,
				searchTimer: null,
				post_id: post_id,
				placeholder: "Search for Users...",
				minimumInputLength: 1,
				closeOnSelect: false,
				allowClear:false,
				width:"250px",
				ajax:{
					delay:400,
					url: ajaxurl,
					data: function (params) {
						var query = {
							q: params.term || '',
							action: 'rua/user/suggest',
							post_id: post_id
						}
						return query;
					},
					dataType: 'JSON',
					type: 'POST',
					processResults: function(data) {
						var results = [];
						for(var i = data.length-1; i >= 0; i--) {
							results.push({
								id:data[i].ID,
								text:data[i].user_login+" ("+data[i].user_email+")"
							});
						}

						return {
							results: results
						};
					}
				},
				nextSearchTerm: function(selectedObject, currentSearchTerm) {
					return currentSearchTerm;
				},
				language: {
					noResults:function(){
						return WPCA.noResults;
					},
					searching: function(){
						return WPCA.searching+"...";
					}
				}
			});
			// .on("select2:selecting",function(e) {
			// 	$elem.data("forceOpen",true);
			// })
			// .on("select2:close",function(e) {
			// 	if($elem.data("forceOpen")) {
			// 		e.preventDefault();
			// 		$elem.select2("open");
			// 		$elem.data("forceOpen",false);
			// 	}
			// });
		},

		/**
		 * Toggle Members tab based on
		 * role sync
		 *
		 * @since  0.4
		 * @return {void}
		 */
		actionRoleHandler: function() {
			var $container = $('#rua-members');
			$container.on("change",".js-rua-role", function(e) {
				var isNotRole = $(this).val() === '';
				$container.find(".js-rua-members").toggle(isNotRole);
				$(".js-rua-drip-option").toggle(isNotRole);
				$(".duration").toggle(isNotRole);
			});
			$container.find(".js-rua-role").trigger('change');
		},

		/**
		 * Initiate tabs dynamically
		 *
		 * @since  3.4
		 * @return {void}
		 */
		initTabSections: function() {
			$(".js-rua-tabs").find(".nav-tab").each(function() {
				var start = this.href.lastIndexOf("#");
				if(start >= 0) {
					var section = this.href.substr(start);
					rua_edit.sections.push(section);
					$(section).hide();
					//.find("input, select").attr("disabled",true);
				}
			});
		},

		/**
		 * Manage tab clicks
		 *
		 * @since  3.4
		 * @return {void}
		 */
		tabController: function() {
			this.initTabSections();
			this.setCurrentSection(window.location.hash);
			$("#poststuff")
			.on("click",".js-nav-link",function(e) {
				rua_edit.setCurrentSection(this.href);
			});
		},

		/**
		 * Find section index based on
		 * hash in a URL string
		 *
		 * @since  3.4
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
		 * @since 3.4
		 * @param {string} url
		 */
		setCurrentSection: function(url) {
			var section = this.findSectionByURL(url) || 0,
				$tabs = $(".js-rua-tabs").find(".nav-tab");
			if($tabs.eq(section).is(":visible")) {
				$(this.sections[this.current_section]).hide();
				$tabs.eq(this.current_section).removeClass("nav-tab-active");
				this.current_section = section;
				$(this.sections[this.current_section]).show();
				$tabs.eq(this.current_section).addClass("nav-tab-active");

				$('#_rua_section').val('#top'+this.sections[this.current_section]);
			}
		},

		/**
		 * Handle counting and toggling
		 * of capabilities
		 *
		 * @since  0.9
		 * @return {void}
		 */
		capController: function() {
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
		}
	};
	$(document).ready(function(){rua_edit.init();});
})(jQuery);
