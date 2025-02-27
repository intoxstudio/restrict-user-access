/*!
 * @package Restrict User Access
 * @author Joachim Jensen <joachim@dev.institute>
 * @license GPLv3
 * @copyright 2024 by Joachim Jensen
 */

(function($, RUA, WPCA, CAE) {
	"use strict";

	var rua_edit = {

		current_section: 0,
		sections: [],
		alert: null,

		/**
		 * Initiator
		 *
		 * @since  0.4
		 * @return {void}
		 */
		init: function() {
			this.alert = new CAE.Views.Alert({
				model:new CAE.Models.Alert()
			});

			this.suggestUsers();
			this.suggestPages();
			this.tabController();
			this.capController();
			this.automationController();
			this.extensionController();
			this.handleCopyToClipboard();
		},

		extensionController: function() {
			var $button = $('#extend_member'),
				$date = $('.js-rua-extend-date'),
				$type = $('.js-rua-extend-type');
			$('.wp-list-table.members').on('click', '.js-rua-member-extend', function(e) {
				e.preventDefault();
				var $this = $(this),
					expiration = $this.data('expiration');

				if(expiration.length) {
					$date.val(expiration);
					$('.js-rua-extend-type-1').prop('checked', true);
				} else {
					$date.val($date.prop('min'));
					$('.js-rua-extend-type-0').prop('checked', true);
				}

				$button.data('userid', $this.data('userid'));
			});

			$button.on('click', function(e) {
				e.preventDefault();

				$.ajax({
					url: ajaxurl,
					data: {
						user_id: $(this).data('userid'),
						post_id: $("#post_ID").val(),
						action: 'rua/membership/extend',
						nonce: RUA.nonce,
						extend_date: $date.val(),
						extend_type: $type.filter(':checked').first().val()
					},
					dataType: 'JSON',
					type: 'POST',
					success: function(data) {
						window.location.reload();
					},
					error: function(xhr, desc, e) {
						rua_edit.alert.failure(xhr.responseJSON.data);
					}
				})
			});
		},

		automationController: function() {
			var $container = $('.js-rua-member-automations'),
				i = $container.children().length;

			//listener to delete automator
			$container.on('click', '.js-rua-member-trigger-remove', function(e) {
				e.preventDefault();
				$(this).closest('.rua-member-trigger').remove();
			});
			
			//listener to add automator with content selector
			$('.js-rua-add-member-automator').on('change', function(e) {
				e.preventDefault();

				var option = e.target.options[e.target.selectedIndex];

				if(option.value === '') {
					return;
				}

				var $content = $('<div data-no="'+i+'" class="rua-member-trigger"><span class="rua-member-trigger-icon dashicons '+option.getAttribute('data-icon')+'"></span> ' + option.getAttribute('data-sentence') + ' <input type="hidden" name="member_automations['+i+'][name]" value="'+option.value+'" /></div>');

				if(option.getAttribute('data-search') === '0') {
					$content.append('<input type="hidden" name="member_automations['+i+'][value]" value="1" /><span class="js-rua-member-trigger-remove wpca-condition-remove wpca-pull-right dashicons dashicons-trash"></span>');
					$container.append($content);
					i++;
					e.target.value = "";
					return;
				}

				var $contentSelectorLocal = $('<select><option></option></select>');
				$content.append($contentSelectorLocal);
				$container.append($content);

				$contentSelectorLocal.select2({
					cachedResults: {},
					quietMillis: 400,
					searchTimer: null,
					type:option.value,
					theme:'wpca',
					dir:WPCA.text_direction,
					minimumInputLength: 0,
					closeOnSelect: true,//false not working properly when hiding selected
					width:"250px",
					placeholder: '-- Select --',
					language: {
						noResults:function(){
							return WPCA.noResults;
						},
						searching: function(){
							return WPCA.searching+'...';
						},
						loadingMore: function() {
							return WPCA.loadingMore+'...';
						}
					},
					data: [],
					dataAdapter: $.fn.select2.amd.require('select2/rua/automatorData'),
					ajax:{}
				})
				.on("select2:select",function(e) {
					e.preventDefault();

					var option = e.target.options[e.target.selectedIndex],
						$parent = $(e.target).parent();

					if(option.value === '') {
						return;
					}

					$parent.append('<input type="hidden" name="member_automations['+$parent.data('no')+'][value]" value="'+option.value+'" /><span class="rua-member-trigger-value">'+option.text+'</span><span class="js-rua-member-trigger-remove wpca-condition-remove wpca-pull-right dashicons dashicons-trash"></span>');
					$contentSelectorLocal.select2('destroy');
					e.target.remove();
				});

				i++;
				e.target.value = "";
			});
		},

		suggestPages: function() {
			var $elem = $('.js-rua-page'),
				rootUrl = $elem.data("rua-url");
			$elem.select2({
				theme:'wpca',
				dir:WPCA.text_direction,
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
							paged: params.page || 1,
							nonce: RUA.nonce
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
					if(term[0] !== '/') {
						term = '/' + term;
					}
					if (term.indexOf('.') === -1 && term[term.length-1] !== '/') {
						term += '/';
					}
					return {
						id: term,
						text: term,
						new: true
					}
				},
				templateResult: function (term) {
					if (term.new) {
						return $("<i>" + rootUrl + "</i><b>" + term.text + "</b>")
					}
					return term.text;
				},
				language: {
					noResults: function () {
						return rootUrl;
					},
					inputTooShort: function() {
						return 'Search for pages or enter custom link';
					}
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
				dir:WPCA.text_direction,
				cachedResults: {},
				quietMillis: 400,
				searchTimer: null,
				post_id: post_id,
				placeholder: "Add Members",
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
							post_id: post_id,
							nonce: RUA.nonce
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
					},
					inputTooShort: function () {
						return 'Search users by name or email';
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
			var startSection = $('#_rua_section').val();
			this.setCurrentSection(startSection ? startSection : window.location.hash);
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

			var columns = [
				{
					"value": 0,
					"sum": $(".sum-0").first(),
					"checkboxes": $('.column-deny').find('input.rua-cb')
				},
				{
					"value": 1,
					"sum": $(".sum-1").first(),
					"checkboxes": $('.column-permit').find('input.rua-cb')
				},
				{
					"value": -1,
					"sum": $(".sum--1").first(),
					"checkboxes": $('.column-unset').find('input.rua-cb')
				}
			],
			$topCheckboxes = $("input.js-rua-cb-all"),
			updateSum = function(column) {
				column.sum.text(column.checkboxes.not('.js-rua-cb-all').filter(':checked').length);
			};

			for(var i in columns) {
				updateSum(columns[i]);
			}

			$("input.js-rua-cb-all")
			.on("change",function() {
				var $this = $(this),
					isChecked = $this.prop("checked"),
					value = $this.val();

				for(var i in columns) {
					columns[i].checkboxes.prop('checked', columns[i].value == value ? isChecked : !isChecked);
					updateSum(columns[i]);
				}
			});

			$("td input.rua-cb")
			.on("change",function() {
				for(var i in columns) {
					updateSum(columns[i]);
				}
				$topCheckboxes.prop("checked",false);
			});
		},
		handleCopyToClipboard: function() {
			$('.js-rua-copy').each(function(i, item) {
				item.setAttribute('title', RUA.copy);
				item.style.cursor = 'pointer';
			});
			$('body').on("click", ".js-rua-copy", function (e) {
				try {
					var text = e.target.dataset.copy;
					if(typeof text == "undefined") {
						text = e.target.textContent;
					}
					if(text.length) {
						navigator.clipboard.writeText(text);
						rua_edit.alert.success(RUA.copy + ': ' + text);
					}
				} catch (err) {
				}
			});
		}
	};

	$.fn.select2.amd.define('select2/rua/automatorData', ['select2/data/array', 'select2/utils'],
		function (ArrayAdapter, Utils) {
			function RUADataAdapter ($element, options) {
				RUADataAdapter.__super__.constructor.call(this, $element, options);
			}

			Utils.Extend(RUADataAdapter, ArrayAdapter);

			RUADataAdapter.prototype.query = function (params, callback) {

				params.term = params.term || '';

				var self = this.options.options,
					cachedData = self.cachedResults[params.term],
					page = params.page || 1;

				if(cachedData && cachedData.page >= page) {
					if(page > 1) {
						page = cachedData.page;
					} else {
						callback({
							results: cachedData.items,
							pagination:{
								more:cachedData.more
							}
						});
						return;
					}
				}

				clearTimeout(self.searchTimer);
				self.searchTimer = setTimeout(function(){
					$.ajax({
						url: ajaxurl,
						data: {
							search: params.term,
							paged: page,
							limit: 20,
							action: "rua/automator/"+self.type,
							nonce: RUA.nonce
						},
						dataType: 'JSON',
						type: 'POST',
						success: function(data) {
							var more = data.length >= 20;

							self.cachedResults[params.term] = {
								page: page,
								more: more,
								items: cachedData ? self.cachedResults[params.term].items.concat(data) : data
							};

							callback({
								results: data,
								pagination: {
									more:more
								}
							});
						}
					});
				}, self.quietMillis);
			};

			return RUADataAdapter;
		}
	);

	$(document).ready(function(){rua_edit.init();});
})(jQuery, RUA, WPCA, CAE);
