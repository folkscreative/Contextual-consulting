jQuery(document).ready(function ($) {

	"use strict"

	var _win = $(window),
		_doc = $(document),
		_body = $('body'),
		_iframe = $('#mailster_iframe'),
		_template_wrap = $('#template-wrap'),
		_ibody, _idoc, _container = $('#mailster_template .inside'),
		_disabled = !!$('#mailster_disabled').val(),
		_title = $('#title'),
		_subject = $('#mailster_subject'),
		_preheader = $('#mailster_preheader'),
		_content = $('#content'),
		_excerpt = $('#excerpt'),
		_modulesraw = $('#modules'),
		_plaintext = $('#plain-text-wrap'),
		_html = $('#html-wrap'),
		_head = $('#head'),
		_obar = $('#optionbar'),
		_undo = [],
		campaign_id = $('#post_ID').val(),
		_currentundo = 0,
		_clickbadgestats = $('#clickmap-stats'),
		_mailsterdata = $('[name^="mailster_data"]'),
		wpnonce = $('#mailster_nonce').val(),
		iframeloaded = false,
		timeout, refreshtimout, updatecounttimeout, modules, optionbar, charts, editbar, animateDOM = $('html,body'),
		isWebkit = 'WebkitAppearance' in document.documentElement.style,
		isMozilla = (/firefox/i).test(navigator.userAgent),
		isMSIE = (/msie|trident/i).test(navigator.userAgent),
		getSelect, selectRange, isDisabled = false,
		is_touch_device = 'ontouchstart' in document.documentElement,
		isTinyMCE = typeof tinymce == 'object',
		codemirror, codemirrorargs = {
			mode: {
				name: "htmlmixed",
				scriptTypes: [{
					matches: /\/x-handlebars-template|\/x-mustache/i,
					mode: null
				}, {
					matches: /(text|application)\/(x-)?vb(a|script)/i,
					mode: "vbscript"
				}]
			},
			tabMode: "indent",
			lineNumbers: true,
			viewportMargin: Infinity,
			autofocus: true
		};

	function _init() {

		_trigger('disable');
		_time();

		//set the document of the iframe cross browser like
		_idoc = (_iframe[0].contentWindow || _iframe[0].contentDocument);
		if (_idoc.document) _idoc = _idoc.document;

		_events();

		var iframeloadinterval = setTimeout(function () {
			if (!iframeloaded) _iframe.trigger('load');
		}, 5000);

		window.Mailster = window.Mailster || {
			refresh: function () {
				_trigger('refresh');
			},
			save: function () {
				_trigger('save');
			},
			trigger: _trigger,
			autosave: '',
		};

		_iframe
			.on('load', function () {

				if (iframeloaded) return false;
				if (!_disabled) {
					if (!optionbar) optionbar = new _optionbar();
					if (!editbar) editbar = new _editbar();
					if (!modules) modules = new _modules();

					window.Mailster.editbar = editbar;

				} else {}

				_trigger('enable');

				iframeloaded = true;
				clearInterval(iframeloadinterval);

				_ibody = _iframe.contents().find('body');

				if (_disabled) {
					//overwrite autosave function since we don't need it
					window.autosave = wp.autosave = function () {
						return true;
					};
					window.onbeforeunload = null;

					_ibody.on('click', 'a', function () {
						window.open(this.href);
						return false;
					});

				} else {

				}

				_trigger('refresh');
				if (!_content.val()) {
					_trigger('save');
				}
				$("#normal-sortables").on("sortupdate", function (event, ui) {
					_trigger('resize');
				});

				_template_wrap.removeClass('load');

				// add current content to undo list
				_undo.push(_getFrameContent());

			});

		_win.on('resize.mailster', function () {
			_trigger('refresh');
		});

		//switch to autoresponder if referer is right or post_status is set
		if (/post_status=autoresponder/.test($('#referredby').val()) || /post_status=autoresponder/.test(location.search)) {
			$('#mailster_delivery').find('a[href="#autoresponder"]').click();
		}

		if (isMSIE) _body.addClass('ie');
		if (is_touch_device) _body.addClass('touch');

	}


	function _events() {

		_doc

			.on('click', 'a.external', function () {
			window.open(this.href);
			return false;
		})

		.on('change', 'input[name=screen_columns]', function () {
			_trigger('resize');
		});

		$('#mailster_submitdiv')
			.on('change', '#use_pwd', function () {
				$('#password-wrap').slideToggle(200).find('input').focus().select();
				$('#post_password').prop('disabled', !$(this).is(':checked'));
			})


		if (!_disabled) {


			_doc
				.on('heartbeat-send', function (e, data) {
					if (data && data['wp_autosave']) {
						data['wp_autosave']['content'] = _getContent();
						data['wp_autosave']['excerpt'] = _excerpt.val();
						data['mailsterdata'] = _mailsterdata.serialize();
					}
				})
				.on('click', '.restore-backup', function (e, data) {
					var data = wp.autosave.local.getSavedPostData();
					_setContent(data.content);
					_title.val(data.post_title);
					return false;
				})
				.on('change', '.dynamic_embed_options_taxonomy', function () {
					var $this = $(this),
						val = $this.val();
					$this.parent().find('.button').remove();
					if (val != -1) {
						if ($this.parent().find('select').length < $this.find('option').length - 1)
							$(' <a class="button button-small add_embed_options_taxonomy">' + mailsterL10n.add + '</a>').insertAfter($this);
					} else {
						$this.parent().html('').append($this);
					}

					return false;
				})
				.on('click', '.add_embed_options_taxonomy', function () {
					var $this = $(this),
						el = $this.prev().clone();

					el.insertBefore($this).val('-1');
					$('<span> ' + mailsterL10n.or + ' </span>').insertBefore(el);
					$this.remove();

					return false;
				});

			_title
				.on('change', function () {
					if (!_subject.val()) _subject.val($(this).val());
				});


			$('form#post')
				.on('submit', function () {
					if (isDisabled) return false;
					_trigger('save');
				});

			//submit box
			$('#mailster_submitdiv')
				.on('click', '.sendnow-button', function () {
					if (!confirm(mailsterL10n.send_now)) return false;
				});


			// delivery box
			$('#mailster_delivery')
				.on('change', 'input.timezone', function () {
					$('.active_wrap').toggleClass('timezone-enabled');
				})
				.on('change', 'input.autoresponder-timezone', function () {
					$('.autoresponderfield-mailster_autoresponder_timebased').toggleClass('timezone-enabled');
				})
				.on('change', 'input.userexactdate', function () {
					var wrap = $(this).parent().parent().parent();
					wrap.find('span').addClass('disabled');
				})
				.on('change', '#autoresponder-post_type', function () {
					var cats = $('#autoresponder-taxonomies');
					cats.find('select').prop('disabled', true);
					_ajax('get_post_term_dropdown', {
						labels: false,
						names: true,
						posttype: $(this).val()
					}, function (response) {
						if (response.success) {
							cats.html(response.html);
						}
					}, function (jqXHR, textStatus, errorThrown) {

						loader(false);

					});
				})
				.on('click', '.category-tabs a', function () {
					var _this = $(this),
						href = _this.attr('href');

					$('#mailster_delivery').find('.tabs-panel').hide();
					$('#mailster_delivery').find('.tabs').removeClass('tabs');
					_this.parent().addClass('tabs');
					$(href).show();
					$('#mailster_is_autoresponder').val((href == '#autoresponder') ? 1 : '');
					return false;
				})
				.on('click', '.mailster_sendtest', function () {
					var $this = $(this),
						loader = $('#delivery-ajax-loading').css('display', 'inline');

					$this.prop('disabled', true);
					_trigger('save');

					_ajax('send_test', {
						formdata: $('#post').serialize(),
						to: $('#mailster_testmail').val(),
						content: _content.val(),
						head: _head.val(),
						plaintext: _excerpt.val()

					}, function (response) {

						loader.hide();
						$this.prop('disabled', false);
						var msg = $('<div class="' + ((!response.success) ? 'error' : 'updated') + '"><p>' + response.msg + '</p></div>').hide().prependTo($this.parent()).slideDown(200).delay(200).fadeIn().delay(3000).fadeTo(200, 0).delay(200).slideUp(200, function () {
							msg.remove();
						});
					}, function (jqXHR, textStatus, errorThrown) {

						loader.hide();
						$this.prop('disabled', false);
						var msg = $('<div class="error"><p>' + textStatus + ' ' + jqXHR.status + ': ' + errorThrown + '</p></div>').hide().prependTo($this.parent()).slideDown(200).delay(200).fadeIn().delay(3000).fadeTo(200, 0).delay(200).slideUp(200, function () {
							msg.remove();
						});

					})
				})
				.on('change', '#mailster_data_active', function () {
					($(this).is(':checked')) ?
					$('.active_wrap').addClass('disabled'): $('.active_wrap').removeClass('disabled');
					$('.deliverydate, .deliverytime').prop('disabled', !$(this).is(':checked'));

				})
				.on('change', '#mailster_data_autoresponder_active', function () {
					($(this).is(':checked')) ?
					$('.autoresponder_active_wrap').addClass('disabled'): $('.autoresponder_active_wrap').removeClass('disabled');

				})
				.on('click', '.mailster_spamscore', function () {
					var $this = $(this),
						loader = $('#delivery-ajax-loading').css('display', 'inline'),
						progress = $('#spam_score_progress').removeClass('spam-score').slideDown(200),
						progressbar = progress.find('.bar');

					$this.prop('disabled', true);
					$('.score').html('');
					_trigger('save');
					progressbar.css('width', '20%');

					_ajax('send_test', {
						spamtest: true,
						formdata: $('#post').serialize(),
						to: $('#mailster_testmail').val(),
						content: _content.val(),
						head: _head.val(),
						plaintext: _excerpt.val()

					}, function (response) {

						if (response.success) {
							progressbar.css('width', '40%');
							check(response.id, 1);
						} else {
							loader.hide();
							progress.slideUp(200);
							var msg = $('<div class="error"><p>' + response.msg + '</p></div>').hide().prependTo($this.parent()).slideDown(200).delay(200).fadeIn().delay(3000).fadeTo(200, 0).delay(200).slideUp(200, function () {
								msg.remove();
							});
						}
					}, function (jqXHR, textStatus, errorThrown) {
						loader.hide();
						$this.prop('disabled', false);
						var msg = $('<div class="error"><p>' + textStatus + ' ' + jqXHR.status + ': ' + errorThrown + '</p></div>').hide().prependTo($this.parent()).slideDown(200).delay(200).fadeIn().delay(3000).fadeTo(200, 0).delay(200).slideUp(200, function () {
							msg.remove();
						});

					})

					function check(id, round) {

						_ajax('check_spam_score', {
							ID: id,
						}, function (response) {

							if (response.score) {
								loader.hide();
								$this.prop('disabled', false);
								progress.addClass('spam-score');
								progressbar.css('width', (parseFloat(response.score) * 10) + '%');

								$('.score').html('<strong>' + sprintf(mailsterL10n.yourscore, response.score) + '</strong>:<br>' + mailsterL10n.yourscores[Math.floor((response.score / 10) * mailsterL10n.yourscores.length)]);
							} else {

								if (round <= 5 && !response.abort) {
									var percentage = (round * 10) + 50;
									progressbar.css('width', (percentage) + '%');
									setTimeout(function () {
										check(id, ++round);
									}, round * 400);
								} else {

									loader.hide();
									$this.prop('disabled', false);
									progressbar.css('width', '100%');
									progress.slideUp(200);
									var msg = $('<div class="error"><p>' + response.msg + '</p></div>').hide().prependTo($this.parent()).slideDown(200).delay(200).fadeIn().delay(3000).fadeTo(200, 0).delay(200).slideUp(200, function () {
										msg.remove();
										progressbar.css('width', 0);
									});

								}

							}
						}, function (jqXHR, textStatus, errorThrown) {
							loader.hide();
							$this.prop('disabled', false);
							var msg = $('<div class="error"><p>' + textStatus + ' ' + jqXHR.status + ': ' + errorThrown + '</p></div>').hide().prependTo($this.parent()).slideDown(200).delay(200).fadeIn().delay(3000).fadeTo(200, 0).delay(200).slideUp(200, function () {
								msg.remove();
							});
						})
					}

				})
				.on('blur', 'input.deliverytime', function () {
					_doc.unbind('.mailster_deliverytime');
				})
				.on('focus, click', 'input.deliverytime', function (event) {
					var $this = $(this),
						input = $(this)[0],
						l = $this.offset().left,
						c = 0,
						startPos = 0,
						endPos = 2;

					if (event.clientX - l > 23) {
						c = 1,
							startPos = 3,
							endPos = 5;
					}
					_doc.unbind('.mailster_deliverytime')
						.on('keypress.mailster_deliverytime', function (event) {
							if (event.keyCode == 9) {
								return (c = !c) ? !selectRange(input, 3, 5) : (event.shiftKey) ? !selectRange(input, 0, 2) : true;
							}
						})
						.on('keyup.mailster_deliverytime', function (event) {
							if ($this.val().length == 1) {
								$this.val($this.val() + ':00');
								selectRange(input, 1, 1);
							}
							if (document.activeElement.selectionStart == 2) {
								if ($this.val().substr(0, 2) > 23) {
									$this.trigger('change');
									return false;
								}
								selectRange(input, 3, 5);
							}
						});
					selectRange(input, startPos, endPos);

				})
				.on('change', 'input.deliverytime', function () {
					var $this = $(this),
						val = $this.val(),
						time;
					$this.addClass('inactive');
					if (!/^\d+:\d+$/.test(val)) {

						if (val.length == 1) {
							val = "0" + val + ":00";
						} else if (val.length == 2) {
							val = val + ":00";
						} else if (val.length == 3) {
							val = val.substr(0, 2) + ":" + val.substr(2, 3) + "0";
						} else if (val.length == 4) {
							val = val.substr(0, 2) + ":" + val.substr(2, 4);
						}
					}
					time = val.split(':');

					if (!/\d\d:\d\d$/.test(val) && val != "" || time[0] > 23 || time[1] > 59) {
						$this.val('00:00').focus();
						selectRange($this[0], 0, 2);
					} else {
						$this.val(val);
					}
				})
				.on('change', '#mailster_autoresponder_action', function () {
					$('#autoresponder_wrap').removeAttr('class').addClass('autoresponder-' + $(this).val());
				})
				.on('change', '#time_extra', function () {
					$('#autoresponderfield-mailster_timebased_advanced').slideToggle();
				})
				.on('click', '.mailster_autoresponder_timebased-end-schedule', function () {
					($(this).is(':checked')) ?
					$('.mailster_autoresponder_timebased-end-schedule-field').slideDown(): $('.mailster_autoresponder_timebased-end-schedule-field').slideUp();
				})
				.on('change', '.mailster-action-hooks', function () {
					var val = $(this).val();
					$('.mailster-action-hook').val(val);
					if (!val) {
						$('.mailster-action-hook').focus();
					}
				})
				.on('change', '.mailster-action-hook', function () {
					var val = $(this).val();
					if (!$(".mailster-action-hooks option[value='" + val + "']").length) {
						$('.mailster-action-hooks').append('<option>' + val + '</option>');
					}
					$('.mailster-action-hooks').val(val);
				})
				.on('click', '.mailster-total', function () {
					_trigger('updateCount');
				})
				.on('change', '#list_extra', function () {
					if ($(this).is(':checked')) {
						$('#mailster_list_advanced').slideDown();
					} else {
						$('#mailster_list_advanced').slideUp();
					}
					$('#list-checkboxes').find('input.list').eq(0).trigger('change');
				})
				.on('focus', 'input.datepicker', function () {
					$(this).removeClass('inactive').trigger('click');
				})
				.on('blur', 'input.datepicker', function () {
					$('.deliverydate').html($(this).val());
					$(this).addClass('inactive');
				})
				.on('change', 'input.datepicker', function () {

				});

			$('#mailster_details')
				.on('click', '.default-value', function () {
					var _this = $(this);
					$('#' + _this.data('for')).val(_this.data('value'));
				});

			$('input.color').wpColorPicker({
				color: true,
				width: 250,
				mode: 'hsl',
				palettes: $('.colors').data('original-colors'),
				change: function (event, ui) {
					$(this).val(ui.color.toString()).trigger('change');
				},
				clear: function (event, ui) {}
			});

			$('.mailster-preview-iframe').on('load', function () {
				var $this = $(this),
					contents = $this.contents(),
					body = contents.find('body');

				body.on('click', 'a', function () {
					var href = $(this).attr('href');
					if (href && href != '#') window.open(href);
					return false;
				});

			});
			if (typeof jQuery.datepicker == 'object') {
				$('#mailster_delivery')
					.find('input.datepicker').datepicker({
						dateFormat: 'yy-mm-dd',
						minDate: new Date(),
						firstDay: mailsterL10n.start_of_week,
						showWeek: true,
						dayNames: mailsterL10n.day_names,
						dayNamesMin: mailsterL10n.day_names_min,
						monthNames: mailsterL10n.month_names,
						prevText: mailsterL10n.prev,
						nextText: mailsterL10n.next,
						showAnim: 'fadeIn',
						onClose: function () {
							var date = $(this).datepicker('getDate');
							$('.deliverydate').html($(this).val());
						}
					});

				$('input.datepicker.nolimit').datepicker("option", "minDate", null);


			} else {

				$('#mailster_delivery')
					.find('input.datepicker').prop('readonly', false);

			}

			$('#mailster_attachments')
				.on('click', '.delete-attachment', function (event) {
					event.preventDefault();
					$(this).parent().remove();
				})
				.on('click', '.add-attachment', function (event) {
					event.preventDefault();

					if (!wp.media.frames.mailster_attachments) {
						wp.media.frames.mailster_attachments = wp.media({
							title: mailsterL10n.add_attachment,
							button: {
								text: mailsterL10n.add_attachment,
							},
							multiple: false
						});
						wp.media.frames.mailster_attachments.on('select', function () {
							var attachment = wp.media.frames.mailster_attachments.state().get('selection').first().toJSON(),
								el = $('.mailster-attachment').eq(0).clone();
							el.find('img').attr('src', attachment.icon);
							el.find('.mailster-attachment-label').html(attachment.filename);
							el.find('input').attr('name', 'mailster_data[attachments][]').val(attachment.id);
							el.appendTo('.mailster-attachments');

						});
					}
					wp.media.frames.mailster_attachments.open();
				});

			$('#mailster_receivers')
				.on('change', 'input.list', function () {
					_trigger('updateCount');
				})
				.on('change', '#all_lists', function () {
					$('#list-checkboxes').find('input.list').prop('checked', $(this).is(':checked'));
					_trigger('updateCount');
				})
				.on('change', '#ignore_lists', function () {
					var checked = $(this).is(':checked');
					$('#list-checkboxes').each(function () {
						(checked) ? $(this).slideUp(200): $(this).slideDown(200);
					}).find('input.list');
					_trigger('updateCount');
				})
				.on('click', '.edit-conditions', function () {
					tb_show(mailsterL10n.edit_conditions, '#TB_inline?x=1&width=720&height=520&inlineId=receivers-dialog', null);
					return false;
				})
				.on('click', '.remove-conditions', function () {
					if (confirm(mailsterL10n.remove_conditions)) {
						$('#receivers-dialog').find('.mailster-conditions-wrap').empty();
						_trigger('updateCount');
					}
					return false;
				})
				.on('click', '.mailster-total', function () {
					_trigger('updateCount');
				});

			$('.close-conditions').on('click', tb_remove);



			$('#mailster_options')
				.on('click', '.wp-color-result', function () {
					$(this).closest('li.mailster-color').addClass('open');
				})
				.on('click', 'a.default-value', function () {
					var el = $(this).prev().find('input'),
						color = el.data('default');

					el.wpColorPicker('color', color);
					return false;
				})
				.on('click', 'ul.colorschema', function () {
					var colorfields = $('#mailster_options').find('input.color'),
						li = $(this).find('li.colorschema-field');

					_trigger('disable');

					$.each(li, function (i) {
						var color = li.eq(i).data('hex');
						colorfields.eq(i).wpColorPicker('color', color);
					});

					_trigger('enable');

				})
				.on('click', 'a.savecolorschema', function () {
					var colors = $.map($('#mailster_options').find('.color'), function (e) {
						return $(e).val();
					});

					var loader = $('#colorschema-ajax-loading').css('display', 'inline');

					_ajax('save_color_schema', {
						template: $('#mailster_template_name').val(),
						colors: colors
					}, function (response) {
						loader.hide();
						if (response.success) {
							$('.colorschema').last().after($(response.html).hide().fadeIn());
						}
					}, function (jqXHR, textStatus, errorThrown) {
						loader.hide();
					})

				})
				.on('click', '.colorschema-delete', function () {

					if (confirm(mailsterL10n.delete_colorschema)) {

						var schema = $(this).parent().parent();
						var loader = $('#colorschema-ajax-loading').css({
							'display': 'inline'
						});
						_ajax('delete_color_schema', {
							template: $('#mailster_template_name').val(),
							hash: schema.data('hash')
						}, function (response) {
							loader.hide();
							if (response.success) {
								schema.fadeOut(100, function () {
									schema.remove()
								});
							}
						}, function (jqXHR, textStatus, errorThrown) {
							loader.hide();
						});

					}

					return false;

				})
				.on('click', '.colorschema-delete-all', function () {

					if (confirm(mailsterL10n.delete_colorschema_all)) {

						var schema = $('.colorschema.custom');
						var loader = $('#colorschema-ajax-loading').css({
							'display': 'inline'
						});
						_ajax('delete_color_schema_all', {
							template: $('#mailster_template_name').val(),
						}, function (response) {
							loader.hide();
							if (response.success) {
								schema.fadeOut(100, function () {
									schema.remove()
								});
							}
						}, function (jqXHR, textStatus, errorThrown) {
							loader.hide();
						});

					}

					return false;

				})
				.on('change', '#mailster_version', function () {
					var val = $(this).val();
					_changeElements(val);
				})
				.on('change', 'input.color', function () {
					var _this = $(this);
					var from = _this.data('value');
					_changeColor(from, _this.val(), _this);
				});


			$('#mailster_template')
				.on('click', 'a.getplaintext', function () {
					var oldval = _excerpt.val();
					_excerpt.val(mailsterL10n.loading);
					_ajax('get_plaintext', {
						html: _getContent()
					}, function (response) {
						_excerpt.val(response);
					}, function (jqXHR, textStatus, errorThrown) {
						_excerpt.val(oldval);
					}, 'HTML');
				})
				.on('change', '#plaintext', function () {
					var checked = $(this).is(':checked');
					_excerpt.prop('disabled', checked)[checked ? 'addClass' : 'removeClass']('disabled');
				});

		} else {

			if (typeof autosavePeriodical != 'undefined') autosavePeriodical.repeat = false;

			$('#mailster_details')
				.on('click', '#show_recipients', function () {
					var $this = $(this),
						list = $('#recipients-list'),
						loader = $('#recipients-ajax-loading');

					if (!list.is(':hidden')) {
						$this.removeClass('open');
						list.slideUp(100);
						return false;
					}
					loader.css('display', 'inline');

					_ajax('get_recipients', {
						id: campaign_id
					}, function (response) {
						$this.addClass('open');
						loader.hide();
						list.html(response.html).slideDown(100);
					}, function (jqXHR, textStatus, errorThrown) {
						loader.hide();
					})
					return false;
				})
				.on('click', '#show_clicks', function () {
					var $this = $(this),
						list = $('#clicks-list'),
						loader = $('#clicks-ajax-loading');

					if (!list.is(':hidden')) {
						$this.removeClass('open');
						list.slideUp(100);
						return false;
					}
					loader.css('display', 'inline');

					_ajax('get_clicks', {
						id: campaign_id
					}, function (response) {
						$this.addClass('open');
						loader.hide();
						list.html(response.html).slideDown(100);
					}, function (jqXHR, textStatus, errorThrown) {
						loader.hide();
					})
					return false;
				})
				.on('click', '#show_environment', function () {
					var $this = $(this),
						list = $('#environment-list'),
						loader = $('#environment-ajax-loading');

					if (!list.is(':hidden')) {
						$this.removeClass('open');
						list.slideUp(100);
						return false;
					}
					loader.css('display', 'inline');

					_ajax('get_environment', {
						id: campaign_id
					}, function (response) {
						$this.addClass('open');
						loader.hide();
						list.html(response.html).slideDown(100);
					}, function (jqXHR, textStatus, errorThrown) {
						loader.hide();
					})
					return false;
				})
				.on('click', '#show_geolocation', function () {
					var $this = $(this),
						list = $('#geolocation-list'),
						loader = $('#geolocation-ajax-loading');

					if (!list.is(':hidden')) {
						$this.removeClass('open');
						list.slideUp(100);
						return false;
					}
					loader.css('display', 'inline');

					_ajax('get_geolocation', {
						id: campaign_id
					}, function (response) {
						$this.addClass('open');
						loader.hide();
						list.html(response.html).slideDown(100, function () {

							var data, countrydata,
								mapoptions = {
									legend: false,
									region: 'world',
									resolution: 'countries',
									datalessRegionColor: '#ffffff',
									enableRegionInteractivity: true,
									colors: ['#d7f1fc', '#2BB3E7'],
									backgroundColor: {
										fill: 'none',
										stroke: null,
										strokeWidth: 0
									}
								},
								hash;

							var geomap;

							google.load('visualization', '1.0', {
								packages: ['geochart', 'corechart'],
								callback: function () {

									geomap = new google.visualization.GeoChart(document.getElementById('countries_map'));
									data = countrydata = google.visualization.arrayToDataTable(response.countrydata);

									if (location.hash && (hash = location.hash.match(/region=([A-Z]{2})/))) {
										regionClick(hash[1]);
									} else {
										draw();
									}

									google.visualization.events.addListener(geomap, 'regionClick', regionClick);

								}
							});

							$('a.zoomout').on('click', function () {
								showWorld();
								return false;
							});

							$('#countries_table').find('tbody').find('tr').on('click', function () {
								var code = $(this).data('code');
								(code == 'unknown' || !code) ?
								showWorld(): regionClick(code);

								return false;
							});

							function showWorld() {
								var options = {
									'region': 'world',
									'displayMode': 'region',
									'resolution': 'countries',
									'colors': ['#D7E4E9', '#2BB3E7']
								};

								data = countrydata;
								draw(options);

								$('#countries_table').find('tr').removeClass('wp-ui-highlight');
								$('#mapinfo').hide();

								location.hash = '#region=';

							}

							function regionClick(event) {

								var options = {},
									region = event.region ? event.region : event,
									d;

								if (region.match(/-/)) return false;

								options['region'] = region;

								(response.unknown_cities[region]) ?
								$('#mapinfo').show().html('+ ' + response.unknown_cities[region] + ' unknown locations'): $('#mapinfo').hide();

								d = response.geodata[region] ? response.geodata[region] : [];

								options['resolution'] = 'provinces';
								options['displayMode'] = 'markers';
								options['dataMode'] = 'markers';
								options['colors'] = ['#4EBEE9', '#2BB3E7'];

								data = new google.visualization.DataTable()
								data.addColumn('number', 'Lat');
								data.addColumn('number', 'Long');
								data.addColumn('string', 'tooltip');
								data.addColumn('number', 'Value');
								data.addColumn({
									type: 'string',
									role: 'tooltip'
								});

								data.addRows(d);

								$('#countries_table').find('tr').removeClass('wp-ui-highlight');
								$('#country-row-' + region).addClass('wp-ui-highlight');

								location.hash = '#region=' + region
								draw(options);

							}



							function draw(options) {
								options = $.extend(mapoptions, options);
								geomap.draw(data, options);
								$('a.zoomout').css({
									'visibility': (mapoptions['region'] != 'world' ? 'visible' : 'hidden')
								});
							}

							function regTo3dig(region) {
								var regioncode = region;
								$.each(regions, function (code, regions) {
									if ($.inArray(region, regions) != -1) regioncode = code;
								})
								return regioncode;
							}




						});
					}, function (jqXHR, textStatus, errorThrown) {
						loader.hide();
					})
					return false;
				})
				.on('click', '#show_errors', function () {
					var $this = $(this),
						list = $('#error-list'),
						loader = $('#error-ajax-loading');

					if (!list.is(':hidden')) {
						$this.removeClass('open');
						list.slideUp(100);
						return false;
					}
					loader.css('display', 'inline');

					_ajax('get_errors', {
						id: campaign_id
					}, function (response) {
						$this.addClass('open');
						loader.hide();
						list.html(response.html).slideDown(100);
					}, function (jqXHR, textStatus, errorThrown) {
						loader.hide();
					})
					return false;
				})
				.on('click', '#show_countries', function () {
					$('#countries_wrap').toggle();
					return false;
				});

			$('#mailster_details')
				.on('click', '.load-more-receivers', function () {
					var $this = $(this),
						page = $this.data('page'),
						types = $this.data('types'),
						orderby = $this.data('orderby'),
						order = $this.data('order'),
						loader = $this.next().css({
							'display': 'inline'
						});

					_ajax('get_recipients_page', {
						id: campaign_id,
						types: types,
						page: page,
						orderby: orderby,
						order: order
					}, function (response) {
						loader.hide();
						if (response.success) {
							$this.parent().parent().replaceWith(response.html);
						}
					}, function (jqXHR, textStatus, errorThrown) {
						detailbox.removeClass('loading');
					});

					return false;
				})
				.on('click', '.recipients-limit', function (event) {
					if (event.altKey) {
						$('input.recipients-limit').prop('checked', false);
						$(this).prop('checked', true);
					}
				})
				.on('change', '.recipients-limit, select.recipients-order', function (event) {

					var list = $('#recipients-list'),
						loader = $('#recipients-ajax-loading'),
						types = $('input.recipients-limit:checked').map(function () {
							return this.value
						}).get(),
						orderby = $('select.recipients-order').val(),
						order = $('a.recipients-order').hasClass('asc') ? 'ASC' : 'DESC';

					loader.css({
						'display': 'inline'
					});
					$('input.recipients-limit').prop('disabled', true);

					_ajax('get_recipients', {
						id: campaign_id,
						types: types.join(','),
						orderby: orderby,
						order: order
					}, function (response) {
						loader.hide();
						$('input.recipients-limit').prop('disabled', false);
						list.html(response.html).slideDown(100);
					}, function (jqXHR, textStatus, errorThrown) {
						loader.hide();
					})
					return false;
				})
				.on('click', 'a.recipients-order', function () {
					$(this).toggleClass('asc');
					$('select.recipients-order').trigger('change');
				})
				.on('click', '.show-receiver-detail', function () {
					var $this = $(this),
						id = $this.data('id'),
						detailbox = $('#receiver-detail-' + id).show();

					$this.parent().addClass('loading').parent().addClass('expanded');

					_ajax('get_recipient_detail', {
						id: id,
						campaignid: campaign_id
					}, function (response) {
						$this.parent().removeClass('loading');
						if (response.success) {
							detailbox.find('div.receiver-detail-body').html(response.html).slideDown(100);
						}
					}, function (jqXHR, textStatus, errorThrown) {
						detailbox.removeClass('loading');
					});

					return false;
				})
				.on('click', '#stats label', function () {
					$('#recipients-list')
						.find('input').prop('checked', false)
						.filter('input.' + $(this).attr('class')).prop('checked', true)
						.trigger('change');
				});

			_container
				.on('mouseenter', 'a.clickbadge', function () {
					var _this = $(this),
						_position = _this.position(),
						p = _this.data('p'),
						link = _this.data('link'),
						clicks = _this.data('clicks'),
						total = _this.data('total');

					_clickbadgestats.find('.piechart').data('easyPieChart').update(p);
					_clickbadgestats.find('.link').html(link);
					_clickbadgestats.find('.clicks').html(clicks);
					_clickbadgestats.find('.total').html(total);
					_clickbadgestats.stop().fadeIn(100).css({
						top: _position.top - 85,
						left: _position.left - (_clickbadgestats.width() / 2 - _this.width() / 2)
					});

				})
				.on('mouseleave', 'a.clickbadge', function () {
					_clickbadgestats.stop().fadeOut(400);
				});


			$('#mailster_receivers')
				.on('click', '.create-new-list', function () {
					var $this = $(this).hide();
					$('.create-new-list-wrap').slideDown();
					$('.create-list-type').trigger('change');
					return false;
				})
				.on('click', '.create-list', function () {
					var $this = $(this),
						listtype = $('.create-list-type'),
						name = '',
						loader = $('.mailster-total');

					if (listtype.val() == -1) return false;

					if (name = prompt(mailsterL10n.enter_list_name, sprintf(mailsterL10n.create_list, listtype.find(':selected').text(), $('#title').val()))) {

						loader.addClass('loading');

						_ajax('create_list', {
							name: name,
							listtype: listtype.val(),
							id: campaign_id
						}, function (response) {
							loader.removeClass('loading');
							var msg = $('<div class="' + ((!response.success) ? 'error' : 'updated') + '"><p>' + response.msg + '</p></div>').hide().prependTo($('.create-new-list-wrap')).slideDown(200).delay(200).fadeIn().delay(3000).fadeTo(200, 0).delay(200).slideUp(200, function () {
								msg.remove();
							});
						}, function (jqXHR, textStatus, errorThrown) {
							loader.removeClass('loading');
						});
					}

					return false;
				})
				.on('change', '.create-list-type', function () {
					var listtype = $(this),
						loader = $('.mailster-total');

					if (listtype.val() == -1) return false;
					listtype.prop('disabled', true);
					loader.addClass('loading');

					_ajax('get_create_list_count', {
						listtype: listtype.val(),
						id: campaign_id
					}, function (response) {
						listtype.prop('disabled', false);
						loader.removeClass('loading').html(response.count);

					}, function (jqXHR, textStatus, errorThrown) {
						listtype.prop('disabled', false);
						loader.removeClass('loading').html('');
					});
				})
				.on('click', '.mailster-total', function () {
					$('.create-list-type').trigger('change');
				});

			$('.piechart').easyPieChart({
				animate: 2000,
				rotate: 180,
				barColor: '#2BB3E7',
				trackColor: '#50626f',
				trackColor: '#f3f3f3',
				lineWidth: 9,
				size: 75,
				lineCap: 'butt',
				onStep: function (value) {
					this.$el.find('span').text(Math.round(value));
				},
				onStop: function (value) {
					this.$el.find('span').text(Math.round(value));
				}
			});


			_doc
				.on('heartbeat-send', function (e, data) {

					if (data['wp_autosave'])
						delete data['wp_autosave'];

					data['mailster'] = {
						page: 'edit',
						id: campaign_id
					};

				})
				.on('heartbeat-tick', function (e, data) {

					if (!data.mailster[campaign_id]) return;

					var _data = data.mailster[campaign_id],
						stats = $('#stats').find('.verybold'),
						charts = $('#stats').find('.piechart'),
						progress = $('.progress'),
						p = (_data.sent / _data.total * 100);

					$('.hb-sent').html(_data.sent_f);
					$('.hb-deleted').html(_data.deleted_f);
					$('.hb-opens').html(_data.opens_f);
					$('.hb-clicks').html(_data.clicks_f);
					$('.hb-clicks_total').html(_data.clicks_total_f);
					$('.hb-unsubs').html(_data.unsubs_f);
					$('.hb-bounces').html(_data.bounces_f);
					$('.hb-geo_location').html(_data.geo_location);

					$.each(_data.environment, function (type) {
						$('.hb-' + type).html((this.percentage * 100).toFixed(2) + '%');
					});

					if ($('#stats_opens').length) $('#stats_opens').data('easyPieChart').update(Math.round(_data.open_rate));
					if ($('#stats_clicks').length) $('#stats_clicks').data('easyPieChart').update(Math.round(_data.click_rate));
					if ($('#stats_unsubscribes').length) $('#stats_unsubscribes').data('easyPieChart').update(Math.round(_data.unsub_rate));
					if ($('#stats_bounces').length) $('#stats_bounces').data('easyPieChart').update(Math.round(_data.bounce_rate));

					progress.find('.bar').width(p + '%');
					progress.find('span').eq(1).html(_data.sent_formatted);
					progress.find('span').eq(2).html(_data.sent_formatted);
					progress.find('var').html(Math.round(p) + '%');

					_clickBadges(_data.clickbadges);

					if (_data.status != $('#original_post_status').val() && !$('#mailster_status_changed_info').length) {

						$('<div id="mailster_status_changed_info" class="error inline"><p>' + sprintf(mailsterL10n.statuschanged, '<a href="post.php?post=' + campaign_id + '&action=edit">' + mailsterL10n.click_here + '</a></p></div>'))
							.hide()
							.prependTo('#postbox-container-2')
							.slideDown(200);
					}

				});

			if (typeof wp != 'undefined' && wp.heartbeat) wp.heartbeat.interval('fast');

		}

	}

	var _optionbar = function () {

		var containeroffset = _container.offset();

		function init() {
			_obar
				.on('click', 'a.template', showFiles)
				.on('click', 'a.save-template', openSaveDialog)
				.on('click', 'a.clear-modules', clear)
				.on('click', 'a.preview', preview)
				.on('click', 'a.undo', undo)
				.on('click', 'a.redo', redo)
				.on('click', 'a.code', codeView)
				.on('click', 'a.plaintext', plainText)
				.on('click', 'a.dfw', dfw)
				.on('click', 'a.file', changeTemplate);

			_body
				.on('click', 'button.save-template', save)
				.on('click', 'button.save-template-cancel', tb_remove);
			_win
				.on('scroll.optionbar', function () {
					var scrolltop = _win.scrollTop();

					if (scrolltop < containeroffset.top - 50 || scrolltop > containeroffset.top + _container.height() - 120) {
						if (/fixed-optionbar/.test(_body[0].className))
							_body.removeClass('fixed-optionbar');
						_obar.width('auto');
					} else {
						if (!/fixed-optionbar/.test(_body[0].className))
							_body.addClass('fixed-optionbar');
						_obar.width(_container.width());
					}
				})
				.on('resize.optionbar', function () {
					containeroffset = _container.offset();
					_win.trigger('scroll.optionbar');
				});

		}

		function openSaveDialog() {

			tb_show(mailsterL10n.save_template, '#TB_inline?x=1&width=480&height=320&inlineId=mailster_template_save', null);

			$('#new_template_name').focus().select();

			return false;
		}

		function save() {

			_trigger('disable');
			var name = $('#new_template_name').val();
			if (!name) return false;
			_trigger('save');
			var loader = $('#new_template-ajax-loading').css('display', 'inline'),
				modules = $('#new_template_modules').is(':checked'),
				activemodules = $('#new_template_active_modules').is(':checked'),
				file = $('#new_template_saveas_dropdown').val(),
				overwrite = !!parseInt($('input[name="new_template_overwrite"]:checked').val(), 10),
				content = _getContent();

			_ajax('create_new_template', {
				name: name,
				modules: modules,
				activemodules: activemodules,
				overwrite: overwrite ? file : false,
				template: $('#mailster_template_name').val(),
				content: content,
				head: _head.val()
			}, function (response) {
				loader.hide();
				if (response.success) {
					//destroy wp object
					if (window.wp) window.wp = null;
					window.location = response.url;
				} else {
					alert(response.msg);
				}
			}, function (jqXHR, textStatus, errorThrown) {
				loader.hide();
			});
			return false;
		}

		function undo() {

			if (_currentundo) {
				_currentundo--;
				_setContent(_undo[_currentundo], 100, false);
				_content.val(_undo[_currentundo]);
				_obar.find('a.redo').removeClass('disabled');
				if (!_currentundo) $(this).addClass('disabled');
			}

			return false;
		}

		function redo() {
			var length = _undo.length;

			if (_currentundo < length - 1) {
				_currentundo++;
				_setContent(_undo[_currentundo], 100, false);
				_content.val(_undo[_currentundo]);
				_obar.find('a.undo').removeClass('disabled');
				if (_currentundo >= length - 1) $(this).addClass('disabled');
			}

			return false;
		}

		function clear() {
			if (confirm(mailsterL10n.remove_all_modules)) {
				var modules = _iframe.contents().find('module');
				var modulecontainer = _iframe.contents().find('modules');
				modulecontainer.slideUp(function () {
					modules.remove();
					modulecontainer.html('').show();
					_trigger('refresh');
					_trigger('save');
				});
			}
			return false;
		}

		function preview() {

			_trigger('save');

			var _this = $(this),
				content = _getContent(),
				subject = _subject.val(),
				preheader = _preheader.val(),
				title = _title.val();

			if (_obar.find('a.preview').is('.loading')) return false;

			_obar.find('a.preview').addClass('loading');
			_ajax('set_preview', {
				id: campaign_id,
				content: content,
				head: _head.val(),
				issue: $('#mailster_autoresponder_issue').val(),
				subject: subject,
				preheader: preheader
			}, function (response) {
				_obar.find('a.preview').removeClass('loading');

				$('.mailster-preview-iframe').attr('src', ajaxurl + '?action=mailster_get_preview&hash=' + response.hash + '&_wpnonce=' + response.nonce);
				tb_show((title ? sprintf(mailsterL10n.preview_for, '"' + title + '"') : mailsterL10n.preview), '#TB_inline?hash=' + response.hash + '&_wpnonce=' + response.nonce + '&width=' + (Math.min(1200, _win.width() - 50)) + '&height=' + (_win.height() - 100) + '&inlineId=mailster_campaign_preview', null);

			}, function (jqXHR, textStatus, errorThrown) {
				_obar.find('a.preview').removeClass('loading');
			});

		}

		function hide() {
			_obar.remove();
		}

		function focusName() {
			$('#new_template_name')
				.on('focus', function () {
					_doc.unbind('keypress.mailster').bind('keypress.mailster', function (event) {
						if (event.keyCode == 13) {
							save();
							return false;
						}
					});
				}).select().focus()
				.on('blur', function () {
					_doc.unbind('keypress.mailster');
				});
		}

		function showFiles(name) {
			var $this = $(this);

			$this.parent().find('ul').eq(0).slideToggle(100);
			return false;
		}

		function codeView() {
			var isCodeview = !_iframe.is(':visible');
			var structure;

			if (!isCodeview) {

				structure = _getHTMLStructure(_getFrameContent());

				_obar.find('a.code').addClass('loading');
				_trigger('disable');

				_ajax('toggle_codeview', {
					bodyattributes: structure.parts[2],
					content: structure.content,
					head: structure.head
				}, function (response) {
					_obar.find('a.code').addClass('active').removeClass('loading');
					_html.hide();
					_content.val(response.content);
					_obar.find('a').not('a.redo, a.undo, a.code').addClass('disabled');

					codemirror = CodeMirror.fromTextArea(_content.get(0), codemirrorargs);

				}, function (jqXHR, textStatus, errorThrown) {
					_obar.find('a.code').addClass('active').removeClass('loading');
					_trigger('enable');
				});

			} else {
				structure = _getHTMLStructure(codemirror.getValue());
				codemirror.clearHistory();

				_obar.find('a.code').addClass('loading');
				_trigger('disable');

				_ajax('toggle_codeview', {
					bodyattributes: structure.parts[2],
					content: structure.content,
					head: structure.head
				}, function (response) {
					_setContent(response.content, 100, true, response.style);
					_html.show();
					_content.hide();
					$('.CodeMirror').remove();
					_obar.find('a.code').removeClass('active').removeClass('loading');
					_obar.find('a').not('a.redo, a.undo, a.code').removeClass('disabled');

					_trigger('enable');

				}, function (jqXHR, textStatus, errorThrown) {
					_obar.find('a.code').addClass('active').removeClass('loading');
					_trigger('enable');
				});

			}
			return false;
		}

		function plainText() {
			var isPlain = !_iframe.is(':visible');

			if (!isPlain) {

				_obar.find('a.plaintext').addClass('active');
				_html.hide();
				_excerpt.show();
				_plaintext.show();
				_obar.find('a').not('a.redo, a.undo, a.plaintext, a.preview').addClass('disabled');

			} else {

				_html.show();
				_plaintext.hide();
				_obar.find('a.plaintext').removeClass('active');
				_obar.find('a').not('a.redo, a.undo, a.plaintext, a.preview').removeClass('disabled');

				_trigger('refresh');

			}
			return false;
		}

		function dfw(event) {

			if (event.type == 'mouseout' && !/DIV|H3/.test(event.target.nodeName)) return;

			containeroffset = _container.offset();

			if (!_body.hasClass('focus-on')) {
				_body.removeClass('focus-off').addClass('focus-on');
				$('#wpbody').on('mouseleave.dfw', dfw);
				_obar.find('a.dfw').addClass('active');
				if (_win.scrollTop() < containeroffset.top) _scroll(containeroffset.top - 80);

			} else {
				_body.removeClass('focus-on').addClass('focus-off');
				$('#wpbody').off('mouseleave', dfw);
				_obar.find('a.dfw').removeClass('active');
			}
			return false;
		}

		function changeTemplate(event) {

			window.onbeforeunload = null;

		}

		init();

		return {
			hide: function () {
				hide();
			}
		}
	}



	var _editbar = function () {

		var bar = $('#editbar'),
			base, contentheights = {
				'img': 0,
				'single': 80,
				'multi': 0,
				'btn': 0,
				'auto': 0,
				'codeview': 0
			},
			imagepreview = bar.find('.imagepreview'),
			imagewidth = bar.find('.imagewidth'),
			imageheight = bar.find('.imageheight'),
			imagecrop = bar.find('.imagecrop'),
			factor = bar.find('.factor'),
			highdpi = bar.find('.highdpi'),
			original = bar.find('.original'),
			imagelink = bar.find('.imagelink'),
			imageurl = bar.find('.imageurl'),
			orgimageurl = bar.find('.orgimageurl'),
			imagealt = bar.find('.imagealt'),
			singlelink = bar.find('.singlelink'),
			buttonlink = bar.find('.buttonlink'),
			buttonlabel = bar.find('.buttonlabel'),
			buttonalt = bar.find('.buttonalt'),
			buttonnav = bar.find('.button-nav'),
			buttontabs = bar.find('ul.buttons'),
			buttontype, current, currentimage, currenttext, currenttag, assetstype, assetslist, itemcount, checkForPostsTimeout, lastpostsargs, searchTimeout, checkRSSfeedInterval,
			editor = $('#wp-mailster-editor-wrap'),
			searchstring = '',
			postsearch = $('#post-search'),
			imagesearch = $('#image-search'),
			imagesearchtype = $('[name="image-search-type"]');

		function init() {
			bar
				.on('keyup change', 'input.live', change)
				.on('keyup change', '#mailster-editor', change)
				.on('click', '.replace-image', replaceImage)
				.on('change', '.highdpi', toggleHighDPI)
				.on('click', 'button.save', save)
				.on('click', '.cancel', cancel)
				.on('click', 'a.remove', remove)
				.on('click', 'a.reload', loadPosts)
				.on('click', 'a.single-link-content', loadSingleLink)
				.on('click', 'a.add_image', openMedia)
				.on('click', 'a.add_image_url', openURL)
				.on('click', '.imagelist li', choosePic)
				.on('dblclick', '.imagelist li', save)
				.on('change', '#post_type_select input', loadPosts)
				.on('click', '.postlist li', choosePost)
				.on('click', '.load-more-posts', loadMorePosts)
				.on('click', 'a.btnsrc', changeBtn)
				.on('click', '.imagepreview', toggleImgZoom)
				.on('click', 'a.nav-tab', openTab)
				.on('change', 'select.check-for-posts', checkForPosts)
				.on('change paste', '#dynamic_rss_url', checkForPosts)
				.on('keyup change', '#post-search', searchPost)
				.on('keyup change', '#image-search', searchPost)
				.on('change', '[name="image-search-type"]', searchPost)
				.on('mouseenter', '#wp-mailster-editor-wrap, .imagelist, .postlist, .CodeMirror', disabledrag)
				.on('mouseleave', '#wp-mailster-editor-wrap, .imagelist, .postlist, .CodeMirror', enabledrag);

			_getRealDimensions(_iframe.contents().find("img").eq(0), function (w, h, f) {
				var ishighdpi = f >= 1.5;
				factor.val(f);
				highdpi.prop('checked', ishighdpi);
				(ishighdpi) ? bar.addClass('high-dpi'): bar.removeClass('high-dpi');
			});

			buttonnav.on('click', 'a', function () {
				$(this).parent().find('a').removeClass('nav-tab-active');
				$(this).parent().parent().find('ul.buttons').hide();
				var hash = $(this).addClass('nav-tab-active').attr('href');
				$('#tab-' + hash.substr(1)).show();
				return false;
			});

			imageurl.on('paste change', function (e) {
				var $this = $(this);
				setTimeout(function () {
					var url = dynamicImage($this.val()),
						img = new Image();
					if (url) {
						loader();
						img.onload = function () {
							imagepreview.attr('src', url);
							imageheight.val(Math.round(img.width / (img.width / img.height)));
							currentimage = {
								width: img.width,
								height: img.height,
								asp: img.width / img.height
							};
							loader(false);
						};
						img.onerror = function () {
							if (e.type != 'paste') alert(sprintf(mailsterL10n.invalid_image, '"' + url + '"'));
						};
						img.src = url;
					}
				}, 1);
			});

			imagewidth.on('keyup change', function () {
				if (!imagecrop.is(':checked')) imageheight.val(Math.round(imagewidth.val() / currentimage.asp));
				adjustImagePreview();
			});
			imageheight.on('keyup change', function () {
				if (!imagecrop.is(':checked')) imagewidth.val(Math.round(imageheight.val() * currentimage.asp));
				adjustImagePreview();
			});
			imagecrop.on('change', function () {
				if (!imagecrop.is(':checked')) {
					imageheight.val(Math.round(imagewidth.val() / currentimage.asp));
					imagecrop.parent().removeClass('not-cropped');
				} else {
					imagecrop.parent().addClass('not-cropped');
				}
				adjustImagePreview();
			});

			$('#dynamic_embed_options_post_type').on('change', function () {

				var cats = $('#dynamic_embed_options_cats'),
					val = $(this).val();
				cats.find('select').prop('disabled', true);
				bar.find('.dynamic-rss')[val == 'rss' ? 'show' : 'hide']();
				loader();
				_ajax('get_post_term_dropdown', {
					posttype: val
				}, function (response) {
					loader(false);
					if (response.success) {
						cats.html(response.html);
						if (currenttag && currenttag.terms) {
							var taxonomies = cats.find('.dynamic_embed_options_taxonomy_wrap');
							$.each(currenttag.terms, function (i, term) {
								if (!term) return;
								var term_ids = term.split(',');
								$.each(term_ids, function (j, id) {
									var select = taxonomies.eq(i).find('select').eq(j),
										last;
									if (!select.length) {
										last = taxonomies.eq(i).find('select').last();
										select = last.clone();
										select.insertAfter(last);
										$('<span> ' + mailsterL10n.or + ' </span>').insertBefore(select);
									}
									select.val(id);
								});
							});
						}



					}
					checkForPosts();
				}, function (jqXHR, textStatus, errorThrown) {

					loader(false);

				});

			});

			bar
				.on('keypress.mailster', function (event) {
					if (event.keyCode == 13 && event.target.nodeName.toLowerCase() != 'textarea') return false;
				})
				.on('keyup.mailster', function (event) {
					switch (event.keyCode) {
					case 27:
						cancel();
						return false;
						break;
					case 13:
						if (current.type != 'multi' && current.type != 'codeview') {
							save();
							return false;
						}
						break;
					}
				});
			bar.find('.current-tag').on('click', 'a', function () {
				return false;
			});


			if (bar.draggable) {
				bar.draggable({
					'distance': 20,
					'axis': 'y'
				});
			}

			if (isTinyMCE && tinymce.get('mailster-editor')) {
				if (tinymce.majorVersion >= 4) {

					tinymce.get('mailster-editor').on('keyup', function () {
						mceUpdater(this);
					});
					tinymce.get('mailster-editor').on('ExecCommand', function () {
						mceUpdater(this);
					});

				} else {
					tinymce.get('mailster-editor').onKeyUp.add(function () {
						mceUpdater(this);
					});
					tinymce.get('mailster-editor').onExecCommand.add(function () {
						mceUpdater(this);
					});
				}
			}

		}

		function draggable(bool) {
			if (bar.draggable) {
				if (bool !== false) {
					bar.draggable("enable");
				} else {
					bar.draggable("disable");
				}
			}
		}

		function disabledrag() {
			draggable(false);
		}

		function enabledrag() {
			draggable(true);
		}


		function openTab(id, trigger) {
			var $this;
			if (typeof id == 'string') {
				$this = base.find('a[href="' + id + '"]');
			} else {
				$this = $(this);
				id = $this.attr('href');
			}

			$this.parent().find('a.nav-tab').removeClass('nav-tab-active');
			$this.addClass('nav-tab-active');
			base.find('.tab').hide();
			base.find(id).show();

			if (id == '#dynamic_embed_options' && trigger !== false) $('#dynamic_embed_options_post_type').trigger('change');
			if (id == '#image_button') buttontype = 'image';
			if (id == '#text_button') buttontype = 'text';

			assetslist = base.find(id).find('.postlist').eq(0);
			return false;
		}


		function replaceImage() {
			loader();
			var f = factor.val(),
				w = current.element.width(),
				h = Math.round(w / 1.6),
				img = $('<img>', {
					'src': 'https://dummy.mailster.co/' + (w * f) + 'x' + (h * f) + '.jpg',
					'alt': current.content,
					'label': current.content,
					'width': w,
					'height': h,
					'border': 0,
					'editable': current.content
				});

			img[0].onload = function () {
				img.attr({
					'width': w,
					'height': h,
				}).removeAttr('style');
				close();
			};
			if (current.element.parent().is('a')) current.element.unwrap();
			if (!current.element.parent().is('td')) current.element.unwrap();
			current.element.replaceWith(img);
			return false;
		}


		function toggleHighDPI() {

			if ($(this).is(':checked')) {
				factor.val(2);
				bar.addClass('high-dpi');
			} else {
				factor.val(1);
				bar.removeClass('high-dpi');
			}
		}

		function checkForPosts() {
			clearInterval(checkForPostsTimeout);
			loader();
			checkForPostsTimeout = setTimeout(function () {

				var post_type = bar.find('#dynamic_embed_options_post_type').val(),
					content = bar.find('#dynamic_embed_options_content').val(),
					relative = bar.find('#dynamic_embed_options_relative').val(),
					taxonomies = bar.find('.dynamic_embed_options_taxonomy_wrap'),
					rss_url = $('#dynamic_rss_url').val(),
					postargs = {},
					extra = [];

				$.each(taxonomies, function (i) {
					var selects = $(this).find('select'),
						values = [];
					$.each(selects, function () {
						var val = parseInt($(this).val(), 10);
						if (val != -1 && $.inArray(val, values) == -1 && !isNaN(val)) values.push(val);
					});
					values = values.join(',');
					if (values) extra[i] = values;
				});
				postargs = {
					id: campaign_id,
					post_type: post_type,
					relative: relative,
					extra: extra,
					modulename: current.name,
					expect: current.elements.expects,
					rss_url: rss_url
				};

				if (JSON.stringify(postargs) === JSON.stringify(lastpostsargs)) {
					loader(false);
					return;
				}

				$('#dynamic_embed_options').find('h4.current-match').html('&hellip;');
				$('#dynamic_embed_options').find('div.current-tag').html('&hellip;');

				if ('rss' == post_type && !rss_url) {
					loader(false);
					return;
				}

				lastpostsargs = postargs;

				_ajax('check_for_posts', postargs, function (response) {
					loader(false);
					if (response.success) {
						currenttext = response.pattern;
						$('#dynamic_embed_options').find('h4.current-match').html(response.title);
						$('#dynamic_embed_options').find('div.current-tag').text(response.pattern.title + "\n\n" + response.pattern[content]);
					}
				}, function (jqXHR, textStatus, errorThrown) {

					loader(false);

				});

			}, 500);

		}

		function dynamicImage(val, w, h, c, o) {
			w = w || imagewidth.val();
			h = h || imageheight.val() || Math.round(w / 1.6);
			c = typeof c == 'undefined' ? imagecrop.prop(':checked') : c;
			o = typeof o == 'undefined' ? original.prop(':checked') : o;
			if (/^\{([a-z0-9-_,;:|~]+)\}$/.test(val)) {
				var f = factor.val();
				val = mailsterdata.ajaxurl + '?action=mailster_image_placeholder&tag=' + val.replace('{', '').replace('}', '') + '&w=' + Math.abs(w) + '&h=' + Math.abs(h) + '&c=' + (c ? 1 : 0) + '&o=' + (o ? 1 : 0) + '&f=' + f;
			}
			return val;
		}

		function isDynamicImage(val) {
			if (-1 !== val.indexOf('?action=mailster_image_placeholder&tag=')) {
				var m = val.match(/&tag=([a-z0-9-_,;:|~]+)&/);
				return '{' + m[1] + '}';
			}
			return false;
		}

		function change(e) {
			if ((e.keyCode || e.which) != 27 && current)
				current.element.html($(this).val());
		}

		function loader(bool) {
			if (bool === false) {
				$('#editbar-ajax-loading').hide();
				bar.find('.buttons').find('button').prop('disabled', false);
			} else {
				$('#editbar-ajax-loading').css('display', 'inline');
				bar.find('.buttons').find('button').prop('disabled', true);
			}
		}

		function save() {

			if (current.type == 'img') {

				var is_img = current.element.is('img');

				if (imageurl.val()) {

					currentimage = {
						id: null,
						name: '',
						src: dynamicImage(imageurl.val()),
						width: currentimage.width,
						height: currentimage.height,
						asp: currentimage.width / currentimage.height
					};

				}

				if (currentimage) {

					loader();

					var f = factor.val() || 1,
						w = imagewidth.val(),
						h = imageheight.val(),
						c = imagecrop.is(':checked'),
						o = original.is(':checked'),
						attribute = is_img ? 'src' : 'background',
						style;

					current.element.attr({
						'data-id': currentimage.id,
					}).data('id', currentimage.id).addClass('mailster-loading');

					if (is_img) {
						current.element.attr({
							'src': currentimage.src,
							'alt': currentimage.name
						});
						if (!current.is_percentage) {
							current.element.attr('width', Math.round(w))
						}
						if (current.element.attr('height') && current.element.attr('height') != 'auto') {
							current.element.attr('height', Math.round(h))
						}
						if (c) {
							current.element.attr({
								'data-crop': true,
							}).data('crop', true);
						}
						if (o) {
							current.element.attr({
								'data-original': true,
							}).data('original', true);
						}
					}

					if (currentimage.src) {
						current.element.attr(attribute, currentimage.src);
						if (style = current.element.attr('style')) {
							current.element.attr('style', style.replace(/url\("?(.+)"?\)/, "url(\'" + currentimage.src + "\')"));
						}
					}

					_ajax('create_image', {
						id: currentimage.id,
						original: o,
						src: currentimage.src,
						width: w * f,
						height: h * f,
						crop: c
					}, function (response) {

						loader(false);

						if (response.success) {
							imagepreview.attr('src', response.image.url);

							response.image.width = (response.image.width || currentimage.width) / f;
							response.image.height = response.image.width / (currentimage.asp);
							response.image.asp = currentimage.asp;

							currentimage = response.image;
							currentimage.name = imagealt.val();

							if (is_img) {
								current.element.one('load error', function (event) {
									if ('error' == event.type) {
										alert(sprintf(mailsterL10n.invalid_image, response.image.url));
									}
									current.element.removeClass('mailster-loading');
									_trigger('save');
								});

								current.element.attr({
									'alt': currentimage.name
								})
								if (!current.is_percentage) {
									current.element.attr('width', Math.round(imagewidth.val()))
								}
								if (current.element.attr('height') && current.element.attr('height') != 'auto') {
									current.element.attr('height', Math.round(imageheight.val()))
								}
								if (c) {
									current.element.attr({
										'data-crop': true,
									}).data('crop', true);
								}
								if (o) {
									current.element.attr({
										'data-original': true,
									}).data('original', true);
								}
							} else {

								current.element.removeClass('mailster-loading');
								var html = current.element.html(),
									is_root = html.match(/<modules/),
									reg;


								if (orgimageurl.val()) {
									if (is_root) {
										if (is_root = html.match(new RegExp('<v:background(.*)<\/v:background>', 's'))) {
											current.element.html(_replace(html, is_root[0], _replace(is_root[0], orgimageurl.val(), currentimage.url)));
										}
									} else {
										current.element.html(_replace(html, orgimageurl.val(), currentimage.url));
										//remove id to re trigger tinymce
										current.element.find('single, multi').removeAttr('id');
									}
								}

							}
							current.element.removeAttr(attribute).attr(attribute, currentimage.url);

						} else {
							current.element.removeClass('mailster-loading');
						}
						imagealt.val('');

						close();

					}, function (jqXHR, textStatus, errorThrown) {

						loader(false);

					});

				} else {
					current.element.attr({
						'alt': imagealt.val()
					});

					close();
				}

				if (current.element.parent().is('a')) current.element.unwrap();
				var link = imagelink.val();
				if (link) current.element.wrap('<a href="' + link + '"></a>');

				return false;

			} else if (current.type == 'btn') {

				var link = buttonlink.val();
				if (!link && !confirm(mailsterL10n.remove_btn)) return false;

				var btnsrc = base.find('a.active').find('img').attr('src');
				if (typeof btnsrc == 'undefined') {
					buttontype = 'text';
					if (!buttonlabel.val()) buttonlabel.val(mailsterL10n.read_more);
				}

				current.element.removeAttr('tmpbutton');

				if ('image' == buttontype) {
					var f = factor.val();
					var img = new Image();
					img.onload = function () {

						if (!current.element.find('img').length) {
							var wrap = current.element.closest('.textbutton');
							var element = $('<a href="" editable label="' + current.name + '"><img></a>');
							(wrap.length) ? wrap.replaceWith(element): current.element.replaceWith(element);
							current.element = element;
						}
						current.element.find('img').attr({
							'src': btnsrc,
							'width': Math.round((img.width || current.element.width()) / f),
							'height': Math.round((img.height || current.element.height()) / f),
							'alt': buttonalt.val(),
						});

						(link) ? current.element.attr('href', link): current.element.remove();
						close();
					}
					img.src = btnsrc;

				} else {

					var wrap = current.element.closest('.textbutton'),
						label = buttonlabel.val();

					if (!wrap.length) {
						current.element.replaceWith('<table class="textbutton" align="left" role="presentation"><tr><td align="center" width="auto"><a href="' + link + '" editable label="' + label + '">' + label + '</a></td></tr></table>')
					} else {
						if (current.element[0] == wrap[0]) {
							current.element = wrap.find('a');
						}
						current.element.text(label);
					}

					if (link) {
						current.element.attr('href', link);
					} else {
						current.element.remove();
						wrap.remove();
					}
					close();

				}

				return false;

			} else if (current.type == 'auto') {

				var insertmethod = $('#embedoption-bar').find('.nav-tab-active').data('type'),
					position = current.element.data('position') || 0,
					contenttype, images = [],
					post_type, rss_url;

				current.element.removeAttr('data-tag data-rss').removeData('tag').removeData('data-rss');

				if ('dynamic' == insertmethod) {

					contenttype = bar.find('#dynamic_embed_options_content').val();
					post_type = bar.find('#dynamic_embed_options_post_type').val();
					rss_url = $('#dynamic_rss_url').val();

					currenttext.content = currenttext[contenttype];

					current.element.attr('data-tag', currenttext.tag).data('tag', currenttext.tag);

					if ('rss' == post_type) {
						current.element.attr('data-rss', rss_url).data('rss', rss_url);
					}

				} else {

					contenttype = $('.embed_options_content:checked').val();
					current.element.removeAttr('data-tag').removeData('tag');

				}

				if (currenttext) {

					if (current.elements.single.length) {

						current.elements.single.each(function (i, e) {
							var _this = $(this),
								expected = _this.attr('expect') || 'title',
								org_content = currenttext[expected] ? currenttext[expected] : '',
								content = [],
								array = [];

							if (!$.isArray(org_content)) {
								content[position] = org_content;
							} else {
								content = org_content;
							}

							if (content[i]) {
								$(this).html(content[i]);
							}

						});

					}

					if (current.elements.multi.length) {

						current.elements.multi.each(function (i, e) {
							var _this = $(this),
								expected = _this.attr('expect') || contenttype,
								org_content = currenttext[expected] ? currenttext[expected] : '',
								content = [],
								array = [];

							if (!$.isArray(org_content)) {
								content[position] = org_content;
							} else {
								content = org_content;
							}

							if (content[i]) {
								$(this).html(content[i]);
							}

						});

					}

					if (currenttext.link) {

						if (current.elements.buttons.length) {
							current.elements.buttons.eq(position).attr('href', currenttext.link);
						}

					} else {

						if (current.elements.buttons.parent().length && current.elements.buttons.parent()[0].nodeName == 'TD') {
							current.elements.buttons.eq(position).closest('.textbutton').remove();
						} else if (current.elements.buttons.length) {
							if (current.elements.buttons.eq(position).last().find('img').length) {
								current.elements.buttons.remove();
							}
						}

					}

					if (currenttext.image && current.elements.images.length) {

						if (!$.isArray(currenttext.image)) {
							images[position] = currenttext.image;
						} else {
							images = currenttext.image;
						}

						currenttext.image = images;

						loader();

						current.elements.images.each(function (i, e) {

							if (!currenttext.image[i]) return;

							var imgelement = $(this);
							var f = factor.val();

							if ('static' == insertmethod) {
								_ajax('create_image', {
									id: currenttext.image[i].id,
									original: original.is(':checked'),
									width: imgelement.width() * f,
									height: imgelement.height() * f,
									crop: imgelement.data('crop'),
								}, function (response) {

									if (response.success) {
										loader(false);

										if (original.is(':checked')) {
											imgelement.attr({
												'data-original': true,
											}).data('original', true);
										}
										if ('img' == imgelement.prop('tagName').toLowerCase()) {
											imgelement
												.attr({
													'data-id': currenttext.image[i].id,
													'src': response.image.url,
													'width': Math.round(response.image.width / f),
													'alt': currenttext.alt || currenttext.title[i]
												})
												.data('id', currenttext.image[i].id);

											if (imgelement.attr('height') && imgelement.attr('height') != 'auto') {
												imgelement.attr('height', Math.round(response.image.height / f));
											}

											if (imgelement.parent().is('a')) {
												imgelement.unwrap();
											}

											if (currenttext.link) {
												imgelement.wrap('<a>');
												imgelement.parent().attr('href', currenttext.link);
											}
										} else {
											var orgurl = imgelement.attr('background');
											imgelement
												.attr({
													'data-id': currenttext.image[i].id,
													'background': response.image.url,
												})
												.data('id', currenttext.image[i].id)
												.css('background-image', '');

											current.element.html(_replace(current.element.html(), orgurl, response.image.url));

											//remove id to re trigger tinymce
											current.element.find('single, multi').removeAttr('id');
											_trigger('save');
											_trigger('refresh');

										}
									}
								}, function (jqXHR, textStatus, errorThrown) {

									loader(false);

								});

								return false;

								// dynamic
							} else if ('dynamic' == insertmethod) {

								var width = imgelement.width(),
									crop = imgelement.data('crop'),
									org = original.is(':checked'),
									height = crop ? imgelement.height() : null;

								if ('img' == imgelement.prop('tagName').toLowerCase()) {

									imgelement
										.removeAttr('data-id')
										.attr({
											'src': dynamicImage(currenttext.image[i], width, height, crop, org),
											'width': width,
											'alt': currenttext.alt || currenttext.title[i]
										})
										.removeData('id');
									if (imgelement.attr('height')) {
										imgelement.attr('height', height || Math.round(width / 1.6));
									}
								} else {
									var orgurl = imgelement.attr('background');
									imgelement
										.removeAttr('data-id')
										.attr({
											'background': dynamicImage(currenttext.image[i], width)
										})
										.removeData('id')
										.css('background-image', '');
									current.element.html(_replace(current.element.html(), orgurl, dynamicImage(currenttext.image[i], width, height, crop, org)));
									//remove id to re trigger tinymce
									current.element.find('single, multi').removeAttr('id');
								}

							}

						});


					}

					position = position + 1 >= (current.elements.multi.length || current.elements.single.length || current.elements.images.length) ? 0 : position + 1;

					current.element.data('position', position);

					_iframe.contents().find("html")
						.find("img").each(function () {
							this.onload = function () {
								_trigger('refresh');
							};
						});

				}

			} else if (current.type == 'multi') {

				if (isTinyMCE && tinymce.get('mailster-editor') && !tinymce.get('mailster-editor').isHidden()) {
					var content = tinymce.get('mailster-editor').getContent();
					content = content.replace('href="http://{', 'href="{'); //from tinymce if tag is used
					current.element.html(content);
				}

			} else if (current.type == 'single') {

				if (current.conditions) {
					current.aa = '<if';
					$.each($('.conditinal-area'), function () {
						current.aa = '';
					});
				}

				if (current.element.parent().is('a')) current.element.unwrap();
				var link = singlelink.val();
				if (link) current.element.wrap('<a href="' + link + '"></a>');

			} else if (current.type == 'codeview') {

				var html = codemirror.getValue();
				current.element.html(html);
				current.modulebuttons.prependTo(current.element);

			}

			close();
			return false;
		}

		function remove() {
			if (current.element.parent().is('a')) current.element.unwrap();
			if ('btn' == current.type) {
				var wrap = current.element.closest('.textbutton'),
					parent = wrap.parent();
				if (!wrap.length) {
					wrap = current.element;
				}
				if (parent.is('buttons') && !parent.find('.textbutton').length) {
					parent.remove();
				} else {
					wrap.remove();
				}
			} else if ('img' == current.type && 'img' != current.tag) {
				current.element.attr('background', '');
			} else {
				current.element.remove();
			}
			close();
			return false;
		}

		function cancel() {
			switch (current.type) {
			case 'img':
			case 'btn':
				if (current.element.is('[tmpbutton]')) {
					current.element.remove();
				}
				break;
			default:
				current.element.html(current.content);
				//remove id to re trigger tinymce
				current.element.find('single, multi').removeAttr('id');
			}
			close();
			return false;
		}

		function changeBtn() {
			var _this = $(this),
				link = _this.data('link');
			base.find('.btnsrc').removeClass('active');
			_this.addClass('active');

			buttonalt.val(_this.attr('title'));

			if (link) {
				var pos;
				buttonlink.val(link);
				if ((pos = (link + '').indexOf('USERNAME', 0)) != -1) {
					buttonlink.focus();
					selectRange(buttonlink[0], pos, pos + 8);
				};

			}
			return false;
		}

		function toggleImgZoom() {
			$(this).toggleClass('zoom');
		}

		function choosePic(event, el) {
			var _this = el || $(this),
				id = _this.data('id'),
				name = _this.data('name'),
				src = _this.data('src');

			if (!id) return;

			currentimage = {
				id: id,
				name: name,
				src: src
			};
			loader();

			base.find('li.selected').removeClass('selected');
			_this.addClass('selected');

			if (current.element.data('id') == id) {
				imagealt.val(current.element.attr('alt'));
			} else if (current.element.attr('alt') != name) {
				imagealt.val(name);
			}
			imageurl.val('');
			imagepreview.attr('src', '').on('load', function () {

				imagepreview.off('load');

				current.width = imagepreview.width();
				current.height = imagepreview.height();
				current.asp = _this.data('asp') || (current.width / current.height);

				currentimage.asp = current.asp;
				loader(false);

				if (!imagecrop.is(':checked')) imageheight.val(Math.round(imagewidth.val() / current.asp));

				adjustImagePreview();

			}).attr('src', src);

			return currentimage;
		}

		function adjustImagePreview() {
			var x = Math.round(.5 * (current.height - (current.width * (imageheight.val() / imagewidth.val())))) || 0,
				f = parseInt(factor.val(), 10);

			imagepreview.css({
				'clip': 'rect(' + (x) + 'px,' + (current.width * f) + 'px,' + (current.height * f - x) + 'px,0px)',
				'margin-top': (-1 * x) + 'px'
			});
		}

		function choosePost() {
			var _this = $(this),
				id = _this.data('id'),
				name = _this.data('name'),
				link = _this.data('link'),
				thumbid = _this.data('thumbid');

			if (current.type == 'btn') {

				buttonlink.val(link);
				buttonalt.val(name);
				base.find('li.selected').removeClass('selected');
				_this.addClass('selected')

			} else if (current.type == 'single') {

				singlelink.val(link);
				base.find('li.selected').removeClass('selected');
				_this.addClass('selected')

			} else {

				loader();
				_ajax('get_post', {
					id: id,
					expect: current.elements.expects
				}, function (response) {
					loader(false);
					base.find('li.selected').removeClass('selected');
					_this.addClass('selected')
					if (response.success) {
						currenttext = response.pattern;
						base.find('.editbarinfo').html(mailsterL10n.curr_selected + ': <span>' + currenttext.title + '</span>');
					}
				}, function (jqXHR, textStatus, errorThrown) {

					loader(false);
					base.find('li.selected').removeClass('selected');

				});

			}
			return false;
		}

		function open(data) {

			current = data;
			var el = data.element,
				module = el.closest('module'),
				top = (type != 'img') ? data.offset.top : 0,
				name = data.name || '',
				type = data.type,
				content = $.trim(el.html()),
				condition = el.find('if'),
				conditions,
				position = current.element.data('position') || 0,
				carea, cwrap, offset,
				fac = 1;

			base = bar.find('div.type.' + type);

			bar.addClass('current-' + type);

			current.width = el.width();
			current.height = el.height();
			current.asp = current.width / current.height;
			current.crop = el.data('crop') ? el.data('crop') : false;
			current.tag = el.prop('tagName').toLowerCase();
			current.is_percentage = el.attr('width') && el.attr('width').indexOf('%') !== -1;
			current.content = content;

			currenttag = current.element.data('tag');
			searchstring = '';

			_trigger('selectModule', module);

			if (condition.length) {

				conditions = {
					'if': null,
					'elseif': [],
					'else': null,
					'total': 0
				};
				conditions = [];

				bar.addClass('has-conditions');
				carea = base.find('.conditinal-area');
				cwrap = bar.find('.conditions').eq(0);
				cwrap.clone().prependTo(carea);

				$.each(condition.find('elseif'), function () {
					var _t = $(this),
						_c = _t.html();
					conditions.push({
						el: _t,
						html: _c,
						field: _t.attr('field'),
						operator: _t.attr('operator'),
						value: _t.attr('value')

					});
					_t.detach();
					carea.clone().val(_c).insertAfter(carea);
				})
				$.each(condition.find('else'), function () {
					var _t = $(this),
						_c = _t.html();
					conditions.push({
						el: _t,
						html: _c
					});
					_t.detach();
					carea.clone().val(_c).insertAfter(carea);
				})
				conditions.unshift({
					el: condition,
					html: condition.html(),
					field: condition.attr('field'),
					operator: condition.attr('operator'),
					value: condition.attr('value')
				});


			} else {
				bar.removeClass('has-conditions');
			}

			current.conditions = conditions;

			if (type == 'img') {

				original.prop('checked', current.original);
				imagecrop.prop('checked', current.crop).parent()[current.crop ? 'addClass' : 'removeClass']('not-cropped');
				searchstring = $.trim(imagesearch.val());

				factor.val(1);
				_getRealDimensions(el, function (w, h, f) {
					var h = f >= 1.5;
					factor.val(f);
					highdpi.prop('checked', h);

					(h) ? bar.addClass('high-dpi'): bar.removeClass('high-dpi');

					fac = f;
				});


			} else if (type == 'btn') {

				if (el.find('img').length) {

					$('#button-type-bar').find('a').eq(1).trigger('click');
					var btnsrc = el.find('img').attr('src');

					if (buttonnav.length) {

						var button = bar.find("img[src='" + btnsrc + "']");

						if (button.length) {
							bar.find('ul.buttons').hide();
							var b = button.parent().parent().parent();
							bar.find('a[href="#' + b.attr('id').substr(4) + '"]').trigger('click');
						} else {
							$.each(bar.find('.button-nav'), function () {
								$(this).find('.nav-tab').eq(0).trigger('click');
							});
						}

					}

					buttonlabel.val(el.find('img').attr('alt'));
					_getRealDimensions(el.find('img'), function (w, h, f) {
						var h = f >= 1.5;
						factor.val(f);
						highdpi.prop('checked', h);
						(h) ? bar.addClass('high-dpi'): bar.removeClass('high-dpi');

						fac = f;
					});

				} else {

					$('#button-type-bar').find('a').eq(0).trigger('click');
					buttonlabel.val($.trim(el.text())).focus().select();
					buttonlink.val(current.element.attr('href'));
					bar.find('ul.buttons').hide();
				}

			} else if (type == 'auto') {

				openTab('#' + (currenttag ? 'dynamic' : 'static') + '_embed_options', true);
				searchstring = $.trim(postsearch.val());

				if (currenttag) {

					var parts = currenttag.substr(1, currenttag.length - 2).split(':'),
						extra = parts[1].split(';'),
						relative = extra.shift(),
						terms = extra.length ? extra : null;

					currenttag = {
						'post_type': parts[0],
						'relative': relative,
						'terms': terms
					};

					$('#dynamic_embed_options_post_type').val(currenttag.post_type).trigger('change');
					$('#dynamic_embed_options_relative').val(currenttag.relative).trigger('change');

				} else {

				}

			} else if (type == 'codeview') {

				var textarea = base.find('textarea'),
					clone = el.clone();

				current.modulebuttons = clone.find('modulebuttons');

				clone.find('modulebuttons').remove();
				clone.find('single, multi')
					.removeAttr('contenteditable spellcheck id dir style class');

				var html = $.trim(clone.html().replace(/\u200c/g, '&zwnj;').replace(/\u200d/g, '&zwj;'));
				textarea.show().html(html);

			}

			offset = _container.offset().top + (current.offset.top - (_win.height() / 2) + (current.height / 2));

			offset = Math.max(_container.offset().top - 200, offset);

			_scroll(offset, function () {

				bar.find('h4.editbar-title').html(name);
				bar.find('div.type').hide();

				bar.find('div.' + type).show();

				if (module.data('rss')) $('#dynamic_rss_url').val(module.data('rss'));

				//center the bar
				var baroffset = _doc.scrollTop() + (_win.height() / 2) - _container.offset().top - (bar.height() / 2);

				bar.css({
					top: baroffset
				});

				loader();

				if (type == 'single') {

					if (conditions) {

						$.each(conditions, function (i, condition) {
							var _b = base.find('.conditinal-area').eq(i);
							_b.find('select.condition-fields').val(condition.field);
							_b.find('select.condition-operators').val(condition.operator);
							_b.find('input.condition-value').val(condition.value);
							_b.find('input.input').val(condition.html)
						});

					} else {

						var val = content.replace(/&amp;/g, '&');

						singlelink.val('');

						if (current.element.parent().is('a')) {
							var href = current.element.parent().attr('href');
							singlelink.val(href != '#' ? href : '');
							loadSingleLink();

						} else if (current.element.find('a').length) {
							var link = current.element.find('a');
							if (val == link.text()) {
								var href = link.attr('href');
								val = link.text();
								singlelink.val(href != '#' ? href : '');
							}
						}

						base.find('input').eq(0).val($.trim(val));

					}

				} else if (type == 'img') {

					var maxwidth = parseInt(el[0].style.maxWidth, 10) || el.parent().width() || el.width() || null;
					var maxheight = parseInt(el[0].style.maxHeight, 10) || el.parent().height() || el.height() || null;
					var src = el.attr('src') || el.attr('background');
					var url = isDynamicImage(src) || '';

					if (el.parent().is('a')) {
						imagelink.val(el.parent().attr('href').replace('%7B', '{').replace('%7D', '}'));
					} else {
						imagelink.val('');
					}

					imagealt.val(el.attr('alt'));
					imageurl.val(url);
					orgimageurl.val(src);

					el.data('id', el.attr('data-id'));

					$('.imageurl-popup').toggle(!!url);
					imagepreview
						.removeAttr('src')
						.attr('src', src);
					assetstype = 'attachment';
					assetslist = base.find('.imagelist');
					currentimage = {
						id: el.data('id'),
						src: src,
						width: el.width() * fac,
						height: el.height() * fac
					}
					currentimage.asp = currentimage.width / currentimage.height;
					loadPosts();
					adjustImagePreview();

				} else if (type == 'btn') {

					buttonalt.val(el.find('img').attr('alt'));
					if (el.attr('href')) buttonlink.val(el.attr('href').replace('%7B', '{').replace('%7D', '}'));

					assetstype = 'link';
					assetslist = base.find('.postlist').eq(0);
					loadPosts();

					$.each(base.find('.buttons img'), function () {
						var _this = $(this);
						_this.css('background-color', el.css('background-color'));
						(_this.attr('src') == btnsrc) ? _this.parent().addClass('active'): _this.parent().removeClass('active');

					});

				} else if (type == 'auto') {

					assetstype = 'post';
					assetslist = base.find('.postlist').eq(0);
					loadPosts();
					current.elements = {
						single: current.element.find('single'),
						multi: current.element.find('multi'),
						buttons: current.element.find('a[editable]'),
						images: current.element.find('img[editable], td[background], th[background]'),
						expects: current.element.find('[expect]').map(function () {
							return $(this).attr('expect');
						}).toArray()
					}

					if ((current.elements.multi.length || current.elements.single.length || current.elements.images.length) > 1) {
						bar.find('.editbarpostion').html(sprintf(mailsterL10n.for_area, '#' + (position + 1))).show();
					} else {
						bar.find('.editbarpostion').hide();
					}

				} else if (type == 'codeview') {

					if (codemirror) {
						codemirror.clearHistory();
						codemirror.setValue('');
						base.find('.CodeMirror').remove();
					}

				}

				bar.show(0, function () {

					if (type == 'single') {

						bar.find('input').focus().select();

					} else if (type == 'img') {

						imagewidth.val(current.width);
						imageheight.val(current.height);
						imagecrop.prop('checked', current.crop);

					} else if (type == 'btn') {

						imagewidth.val(maxwidth);
						imageheight.val(maxheight);

					} else if (type == 'multi') {

						$('#mailster-editor').val(content);

						if (isTinyMCE && tinymce.get('mailster-editor')) {
							tinymce.get('mailster-editor').setContent(content);
							tinymce.execCommand('mceFocus', false, 'mailster-editor');
						}

					} else if (type == 'codeview') {

						codemirror = CodeMirror.fromTextArea(textarea.get(0), codemirrorargs);

					}


				});

				loader(false);

			}, 100);


		}

		function loadPosts(event, callback) {

			var posttypes = $('#post_type_select').find('input:checked').serialize(),
				data = {
					type: assetstype,
					posttypes: posttypes,
					search: searchstring,
					imagetype: imagesearchtype.filter(':checked').val(),
					offset: 0
				};

			if (assetstype == 'attachment') {
				data.id = currentimage.id;
			}

			assetslist.empty();
			loader();

			_ajax('get_post_list', data, function (response) {
				loader(false);
				if (response.success) {
					itemcount = response.itemcount;
					displayPosts(response.html, true);
					callback && callback();
				}
			}, function (jqXHR, textStatus, errorThrown) {

				loader(false);

			});
		}

		function loadMorePosts() {
			var $this = $(this),
				offset = $this.data('offset'),
				type = $this.data('type');

			loader();

			var posttypes = $('#post_type_select').find('input:checked').serialize();

			_ajax('get_post_list', {
				type: type,
				posttypes: posttypes,
				search: searchstring,
				imagetype: imagesearchtype.filter(':checked').val(),
				offset: offset,
				itemcount: itemcount
			}, function (response) {
				loader(false);
				if (response.success) {
					itemcount = response.itemcount;
					$this.remove();
					displayPosts(response.html, false);
				}
			}, function (jqXHR, textStatus, errorThrown) {

				loader(false);

			});
			return false;
		}

		function searchPost() {
			var $this = $(this),
				temp = $.trim('attachment' == assetstype ? imagesearch.val() : postsearch.val());
			if ((!$this.is(':checked') && searchstring == temp)) {
				return false;
			}
			searchstring = temp;
			clearTimeout(searchTimeout);
			searchTimeout = setTimeout(function () {
				loadPosts();
			}, 500);
		}

		function loadSingleLink() {
			$('#single-link').slideDown(200);
			singlelink.focus().select();
			assetstype = 'link';
			assetslist = base.find('.postlist').eq(0);
			loadPosts();
			return false;

		}

		function displayPosts(html, replace, list) {
			if (!list) list = assetslist;
			if (replace) list.empty();
			if (!list.html()) list.html('<ul></ul>');

			list.find('ul').append(html);
		}

		function openURL() {
			$('.imageurl-popup').toggle();
			if (!imageurl.val() && currentimage.src.indexOf(location.origin) == -1 && currentimage.src.indexOf('dummy.mailster.co') == -1) {
				imageurl.val(currentimage.src);
			}
			imageurl.focus().select();
			return false;
		}

		function openMedia() {

			if (!wp.media.frames.mailster_editbar) {

				wp.media.frames.mailster_editbar = wp.media({
					title: mailsterL10n.select_image,
					library: {
						type: 'image'
					},
					multiple: false
				});

				wp.media.frames.mailster_editbar.on('select', function () {
					var attachment = wp.media.frames.mailster_editbar.state().get('selection').first().toJSON(),
						el = $('img').data({
							id: attachment.id,
							name: attachment.name,
							src: attachment.url
						});
					loadPosts(null, function () {
						choosePic(null, el);
					});
				});
			}

			wp.media.frames.mailster_editbar.open();

		}

		function mceUpdater(editor) {
			clearTimeout(timeout);
			timeout = setTimeout(function () {
				if (!editor) return;
				var val = $.trim(editor.save());
				current.element.html(val);
			}, 100);
		}

		function close() {

			bar.removeClass('current-' + current.type).hide();
			loader(false);
			$('#single-link').hide();
			_trigger('refresh');
			_trigger('save');
			return false;

		}

		init();

		return {
			open: function (data) {
				open(data);
			},
			close: function () {
				close();
			}
		}
	};



	var _modules = function () {

		var metabox = $('#mailster_template'),
			selector = $('#module-selector'),
			search = $('#module-search'),
			module_thumbs = selector.find('li'),
			toggle = $('a.toggle-modules'),
			container = _iframe.contents().find('modules'),
			body = _iframe.contents().find('body'),
			modules = container.find('module'),
			elements, modulesOBJ = {},
			show_modules = !!parseInt(window.getUserSetting('mailstershowmodules', 1), 10);

		function addmodule() {
			var module = selector.data('current');
			insert($(this).data('id'), ((module && module.is('module')) ? module : false), true, true);
			return false;
		}

		function up() {
			var module = $(this).parent().parent().parent().addClass('ui-sortable-fly-over'),
				prev = module.prev('module').addClass('ui-sortable-fly-under'),
				pos = (animateDOM.scrollTop() || document.scrollingElement.scrollTop) - prev.height();
			module.css({
				'transform': 'translateY(-' + prev.height() + 'px)'
			});
			prev.css({
				'transform': 'translateY(' + module.height() + 'px)'
			});
			_scroll(pos, function () {
				module.insertBefore(prev.css({
					'transform': ''
				}).removeClass('ui-sortable-fly-under')).css({
					'transform': ''
				}).removeClass('ui-sortable-fly-over');
				_trigger('refresh');
				_trigger('save');
			}, 250);
			return false;
		}

		function down() {
			var module = $(this).parent().parent().parent().addClass('ui-sortable-fly-over'),
				next = module.next('module').addClass('ui-sortable-fly-under'),
				pos = (animateDOM.scrollTop() || document.scrollingElement.scrollTop) + next.height();
			module.css({
				'transform': 'translateY(' + next.height() + 'px)'
			});
			next.css({
				'transform': 'translateY(-' + module.height() + 'px)'
			});
			_scroll(pos, function () {
				module.insertAfter(next.css({
					'transform': ''
				}).removeClass('ui-sortable-fly-under')).css({
					'transform': ''
				}).removeClass('ui-sortable-fly-over');
				_trigger('refresh');
				_trigger('save');
			}, 250);
			return false;
		}

		function duplicate() {
			var module = $(this).parent().parent().parent(),
				clone = module.clone().removeAttr('selected').hide();

			insert(clone, module, false, true);
			return false;
		}

		function auto() {
			var module = $(this).parent().parent().parent();
			editbar.open({
				element: module,
				name: module.attr('label'),
				type: 'auto',
				offset: module.offset()
			});
			return false;
		}

		function changeName() {
			var _this = $(this),
				value = _this.val(),
				module = _this.parent().parent();

			if (!value) {
				value = _this.attr('placeholder');
				_this.val(value);
			}

			module.attr('label', value);
		}

		function remove() {
			var module = $(this).parent().parent().parent();
			module.fadeTo(100, 0, function () {
				module.slideUp(100, function () {
					module.remove();
					modules = _iframe.contents().find('module');
					if (!modules.length) container.html('');
					_trigger('refresh');
					_trigger('save');
				});
			});
			return false;
		}

		function insert(id_or_clone, element, before, scroll) {

			var clone;

			if (modulesOBJ[id_or_clone]) {
				clone = modulesOBJ[id_or_clone].el.clone();
			} else if (id_or_clone instanceof jQuery) {
				clone = $(id_or_clone);
				clone.find('single, multi, buttons').removeAttr('contenteditable spellcheck id dir style class');
			} else {
				return false;
			}

			if (!element && !container.length) return false;

			modules = container.find('module');

			if (element) {
				(before ? clone.hide().insertBefore(element) : clone.hide().insertAfter(element))
			} else {
				if ('footer' == modules.last().attr('type')) {
					clone.hide().insertBefore(modules.last());
				} else {
					clone.hide().appendTo(container);
				}
			}

			modules = container.find('module');

			clone.slideDown(100, function () {
				clone.css('display', 'block');
				_trigger('refresh');
				_trigger('save');
			});

			if (scroll) {
				var offset = clone.offset().top + _container.offset().top - (_win.height() / 2);
				_scroll(offset);
			}

		}

		function codeView() {
			var module = $(this).parent().parent().parent();
			editbar.open({
				element: module,
				name: module.attr('label'),
				type: 'codeview',
				offset: module.offset()
			});
			return false;
		}

		function toggleModules() {
			_template_wrap.toggleClass('show-modules');
			show_modules = !show_modules;
			window.setUserSetting('mailstershowmodules', show_modules ? 1 : 0);
			setTimeout(function () {
				_trigger('resize');
			}, 200);
		}

		function searchModules() {

			module_thumbs.hide();
			selector.find("li:contains('" + $(this).val() + "')").show();

		}

		function init() {
			_container
				.on('click', 'a.toggle-modules', toggleModules)
				.on('keydown', 'a.addmodule', function (event) {
					if (13 == event.which) {
						addmodule.call(this);
					}
				})
				.on('click', 'a.addmodule', addmodule);

			search
				.on('keyup', searchModules)
				.on('focus', function () {
					search.select();
				});
			$('#module-search-remove').on('click', function () {
				search.val('').trigger('keyup').focus();
				return false;
			})

			refresh();
		}

		function refresh() {

			modules = _iframe.contents().find('module');
			if (!modules.length) container.html('');
			elements = $(_modulesraw.val()).add(modules);

			//no modules at all
			if (!elements.length) {
				selector.remove();
				return;
			}

			container = _iframe.contents().find('modules');
			container
				.on('click', 'button.up', up)
				.on('click', 'button.down', down)
				.on('click', 'button.auto', auto)
				.on('click', 'button.duplicate', duplicate)
				.on('click', 'button.remove', remove)
				.on('click', 'button.codeview', codeView)
				.on('change', 'input.modulelabel', changeName);

			var html = '',
				x = '',
				i = 0,
				mc = 0;

			//reset
			modulesOBJ = [];
			//add module buttons and add them to the list
			$.each(elements, function (j) {
				var $this = $(this);
				if ($this.is('module')) {
					var name = $this.attr('label') || sprintf(mailsterL10n.module, '#' + (++mc)),
						codeview = mailsterdata.codeview ? '<button class="mailster-btn codeview" title="' + mailsterL10n.codeview + '"></button>' : '',
						auto = ($this.is('[auto]') ? '<button class="mailster-btn auto" title="' + mailsterL10n.auto + '"></button>' : '');

					$('<modulebuttons>' + '<span>' + auto + '<button class="mailster-btn duplicate" title="' + mailsterL10n.duplicate_module + '"></button><button class="mailster-btn up" title="' + mailsterL10n.move_module_up + '"></button><button class="mailster-btn down" title="' + mailsterL10n.move_module_down + '"></button>' + codeview + '<button class="mailster-btn remove" title="' + mailsterL10n.remove_module + '"></button></span><input class="modulelabel" type="text" value="' + name + '" placeholder="' + name + '" title="' + mailsterL10n.module_label + '" tabindex="-1"></modulebuttons>').prependTo($this);


					if (!$this.parent().length) {

						modulesOBJ.push({
							name: name,
							el: $this
						});
					}
				}
			});

			var currentmodule,
				moduleid,
				pre_dropzone = $('<dropzone></dropzone>'),
				post_dropzone = pre_dropzone.clone(),
				dropzones = pre_dropzone.add(post_dropzone);

			//check if their are events assigned
			if (!$._data(selector[0], "events")) {

				selector
					.on('dragstart.mailster', 'li', function (event) {

						window.mailster_is_modulde_dragging = true;

						event.originalEvent.dataTransfer.setData('Text', this.id);
						body.addClass('drag-active');
						moduleid = $(event.target).data('id');

						container
							.on('dragenter.mailster', function (event) {
								var selectedmodule = $(event.target).closest('module');
								if (!selectedmodule.length || currentmodule && currentmodule[0] === selectedmodule[0]) return;
								currentmodule = selectedmodule;
								post_dropzone.appendTo(currentmodule);
								pre_dropzone.prependTo(currentmodule);
								setTimeout(function () {
									post_dropzone.addClass('visible');
									pre_dropzone.addClass('visible');
									modules.removeClass('drag-up drag-down');
									selectedmodule.prevAll('module').addClass('drag-up');
									selectedmodule.nextAll('module').addClass('drag-down')
								}, 1);
							})
							.on('dragover.mailster', function (event) {
								event.preventDefault();
							})
							.on('drop.mailster', function (event) {
								insert(moduleid, modules.length ? (currentmodule && currentmodule[0] === container ? false : currentmodule) : false, pre_dropzone[0] === event.target, false, true);
								modules = _iframe.contents().find('module');
								event.preventDefault();
							});

						dropzones
							.on('dragenter.mailster', function (event) {
								$(this).addClass('drag-over');
							})
							.on('dragleave.mailster', function (event) {
								$(this).removeClass('drag-over');
							});

					})
					.on('dragend.mailster', 'li', function (event) {
						currentmodule = null;
						body.removeClass('drag-active');
						dropzones.removeClass('visible drag-over').remove();
						modules.removeClass('drag-up drag-down');

						container
							.off('dragenter.mailster')
							.off('dragover.mailster')
							.off('drop.mailster');

						dropzones
							.off('dragenter.mailster')
							.off('dragleave.mailster');

						window.mailster_is_modulde_dragging = false;

					});

			}

		}

		init();
		return {
			refresh: function () {
				refresh();
			}
		}
	}


	function _scroll(pos, callback, speed) {
		pos = Math.round(pos);
		if (isNaN(speed)) speed = 200;
		if (!isMSIE && (animateDOM.scrollTop() == pos || document.scrollingElement.scrollTop == pos)) {
			callback && callback();
			return
		}
		animateDOM.stop().animate({
			'scrollTop': pos
		}, speed, function () {
			callback && callback()
		});
	}

	function _jump(val, rel) {
		val = Math.round(val);
		if (rel) {
			window.scrollBy(0, val);
		} else {
			window.scrollTo(0, val);
		}
	}

	$(window)

	.on('Mailster:refresh', function () {
		clearTimeout(refreshtimout);
		refreshtimout = setTimeout(function () {
			_trigger('resize');

			if (!_disabled) {
				_editButtons();
			} else {
				_clickBadges();
			}
		}, 10);
	})

	.on('Mailster:resize', function () {
		if (!iframeloaded) return false;
		setTimeout(function () {
			if (!_iframe[0].contentWindow.document.body) return;
			var height = _iframe.contents().find('body').outerHeight() ||
				_iframe.contents().height() ||
				_iframe[0].contentWindow.document.body.offsetHeight ||
				_iframe.contents().find("html")[0].innerHeight ||
				_iframe.contents().find("html").height();

			height = Math.max(500, height + 4);
			$('#editor-height').val(height);
			_iframe.attr("height", height);
		}, 50);
	})

	.on('Mailster:save', function () {
		if (!_disabled && iframeloaded) {

			var content = _getFrameContent();

			var length = _undo.length,
				lastundo = _undo[length - 1];

			if (lastundo != content) {

				_content.val(content);

				_preheader.prop('readonly', !content.match('{preheader}'));

				_undo = _undo.splice(0, _currentundo + 1);

				_undo.push(content);
				if (length >= mailsterL10n.undosteps) _undo.shift();
				_currentundo = _undo.length - 1;

				if (_currentundo) _obar.find('a.undo').removeClass('disabled');
				_obar.find('a.redo').addClass('disabled');

				if (wp && wp.autosave) wp.autosave.local.save();
			}

		}
	})

	.on('Mailster:disable', function () {
		isDisabled = true;
		$('.button').prop('disabled', true);
		$('input:visible').prop('disabled', true);
	})

	.on('Mailster:enable', function () {
		$('.button').prop('disabled', false);
		$('input:visible, input.wp-color-picker').prop('disabled', false);
		isDisabled = false;
	})

	.on('Mailster:selectModule', function (event) {
		if (!event.detail) return;
		var module = event.detail[0];
	})

	.on('Mailster:updateCount', function () {
		clearTimeout(updatecounttimeout);
		updatecounttimeout = setTimeout(function () {
			var lists = [],
				conditions = [],
				inputs = $('#list-checkboxes').find('input, select'),
				listinputs = $('#list-checkboxes').find('input.list'),
				extra = $('#list_extra'),
				data = {},
				total = $('.mailster-total'),
				cond = $('#mailster_conditions_render'),
				groups = $('.mailster-conditions-wrap > .mailster-condition-group'),
				i = 0;

			$.each(listinputs, function () {
				var id = $(this).val();
				if ($(this).is(':checked')) lists.push(id);
			});

			data.id = campaign_id;
			data.lists = lists;
			data.ignore_lists = $('#ignore_lists').is(':checked');

			$.each(groups, function () {
				var c = $(this).find('.mailster-condition');
				$.each(c, function () {
					var _this = $(this),
						value,
						field = _this.find('.condition-field').val(),
						operator = _this.find('.mailster-conditions-operator-field.active').find('.condition-operator').val();

					if (!operator || !field) return;

					value = _this.find('.mailster-conditions-value-field.active').find('.condition-value').map(function () {
						return $(this).val();
					}).toArray();
					if (value.length == 1) {
						value = value[0];
					}
					if (!conditions[i]) {
						conditions[i] = [];
					}

					conditions[i].push({
						field: field,
						operator: operator,
						value: value,
					});
				});
				i++;
			});

			data.operator = $('select.mailster-list-operator').val();
			data.conditions = conditions;

			total.addClass('loading');

			_trigger('disable');

			_ajax('get_totals', data, function (response) {
				_trigger('enable');
				total.removeClass('loading').html(response.totalformatted);
				cond.html(response.conditions);

			}, function (jqXHR, textStatus, errorThrown) {
				_trigger('enable');
				total.removeClass('loading').html('?');
			});
		}, 10);
	})

	.on('Mailster:xxx', function () {

	});

	function _trigger() {

		var triggerevent = arguments[0];
		var args = arguments[1] || null;
		var event;
		if (isMSIE) {
			event = document.createEvent("CustomEvent");
			event.initCustomEvent('Mailster:' + triggerevent, false, false, {
				'detail': args,
			});
		} else {
			event = new CustomEvent('Mailster:' + triggerevent, {
				'detail': args,
			});
		}

		window.dispatchEvent(event);
		_iframe[0].contentWindow.window.dispatchEvent(event);
	}

	function _editButtons() {
		_container.find('.content.mailster-btn').remove();
		var cont = _iframe.contents().find('html'),
			modulehelper = null;

		if (!cont) return;

		setTimeout(function () {

			//check if their are events assigned
			if (!$._data(cont[0], "events")) {

				cont
					.off('.mailster')
					.on('click.mailster', 'img[editable]', function (event) {
						event.stopPropagation();
						var $this = $(this),
							offset = $this.offset(),
							top = offset.top + 61,
							left = offset.left,
							name = $this.attr('label'),
							type = 'img';

						editbar.open({
							'offset': offset,
							'type': type,
							'name': name,
							'element': $this
						});

					})
					.on('click.mailster', 'module td[background],module th[background]', function (event) {
						event.stopPropagation();
						modulehelper = true;
					})
					.on('click.mailster', 'td[background], th[background]', function (event) {
						event.stopPropagation();
						if (!modulehelper && event.target != this) return;
						modulehelper = null;

						var $this = $(this),
							offset = $this.offset(),
							top = offset.top + 61,
							left = offset.left,
							name = $this.attr('label'),
							type = 'img';

						editbar.open({
							'offset': offset,
							'type': type,
							'name': name,
							'element': $this
						});

					})
					.on('click.mailster', 'a[editable]', function (event) {
						event.stopPropagation();
						event.preventDefault();
						var $this = $(this),
							offset = $this.offset(),
							top = offset.top + 40,
							left = offset.left,
							name = $this.attr('label'),
							type = 'btn';

						editbar.open({
							'offset': offset,
							'type': type,
							'name': name,
							'element': $this
						});


					})

			}

			if (!mailsterdata.inline) {
				cont
					.on('click.mailster', 'multi, single', function (event) {
						event.stopPropagation();
						var $this = $(this),
							offset = $this.offset(),
							top = offset.top + 40,
							left = offset.left,
							name = $this.attr('label'),
							type = $this.prop('tagName').toLowerCase();

						editbar.open({
							'offset': offset,
							'type': type,
							'name': name,
							'element': $this
						});
					});
			}

		}, 500);

	}

	function _clickBadges(stats) {
		_container.find('.clickbadge').remove();
		var stats = stats || $('#mailster_click_stats').data('stats'),
			total = parseInt(stats.total, 10);

		if (!total) return;

		$.each(stats.clicks, function (href, countobjects) {

			$.each(countobjects, function (index, counts) {

				var link = _iframe.contents().find('a[href="' + href.replace('&amp;', '&') + '"]').eq(index);

				if (link.length) {
					link.css({
						'display': 'inline-block'
					});

					var offset = link.offset(),
						top = offset.top,
						left = offset.left + 5,
						percentage = (counts.clicks / total) * 100,
						v = (percentage < 1 ? '&lsaquo;1' : Math.round(percentage)) + '%',
						badge = $('<a class="clickbadge ' + (percentage < 40 ? 'clickbadge-outside' : '') + '" data-p="' + percentage + '" data-link="' + href + '" data-clicks="' + counts.clicks + '" data-total="' + counts.total + '"><span style="width:' + (Math.max(0, percentage - 2)) + '%">' + (percentage < 40 ? '&nbsp;' : v) + '</span>' + (percentage < 40 ? ' ' + v : '') + '</a>')
						.css({
							top: top,
							left: left
						}).appendTo(_container);

				}

			});
		});
	}


	function _changeColor(color_from, color_to, element) {
		if (!color_from) color_from = color_to;
		if (!color_to) return false;
		color_from = color_from.toLowerCase();
		color_to = color_to.toLowerCase();
		if (color_from == color_to) return false;
		var raw = _getContent(),
			reg = new RegExp(color_from, 'gi'),
			m = _modulesraw.val();

		if (element)
			element.data('value', color_to);

		$('#mailster-color-' + color_from.substr(1)).attr('id', 'mailster-color-' + color_to.substr(1));

		if (reg.test(m))
			_modulesraw.val(m.replace(reg, color_to));

		if (reg.test(raw)) {
			_setContent(raw.replace(reg, color_to), 30);
		}


	}

	function _replace(str, match, repl) {
		if (match === repl)
			return str;
		do {
			str = str.replace(match, repl);
		} while (match && str.indexOf(match) !== -1);
		return str;
	}

	function _changeElements(version) {
		var raw = _getContent(),
			reg = /\/img\/version(\d+)\//g,
			to = '/img/' + version + '/';

		html = raw.replace(reg, to);

		var m = _modulesraw.val();
		_modulesraw.val(m.replace(reg, to));

		_setContent(html);

		return;
	}

	function _getFrameContent() {

		var body = _iframe[0].contentWindow.document.body,
			clone, content, bodyattributes, attrcount, s = '';

		if (typeof body == 'null' || !body) return '';

		clone = $('<div>' + body.innerHTML + '</div>');

		clone.find('.mce-tinymce, .mce-widget, .mce-toolbar-grp, .mce-container, .screen-reader-text, .ui-helper-hidden-accessible, .wplink-autocomplete, modulebuttons, mailster, #mailster-editorimage-upload-button, button').remove();
		//remove some third party elements
		clone.find('#droplr-chrome-extension-is-installed').remove();
		clone.find('single, multi, module, modules, buttons').removeAttr('contenteditable spellcheck id dir style class selected');
		content = $.trim(clone.html().replace(/\u200c/g, '&zwnj;').replace(/\u200d/g, '&zwj;'));


		bodyattributes = body.attributes || [];
		attrcount = bodyattributes.length;

		if (attrcount) {
			while (attrcount--) {
				s = ' ' + bodyattributes[attrcount].name + '="' + $.trim(bodyattributes[attrcount].value) + '"' + s;
			}
		}
		s = $.trim(s
			.replace(/(webkit |wp\-editor|mceContentBody|position: relative;|cursor: auto;|modal-open| spellcheck="(true|false)")/g, '')
			.replace(/(class="(\s*)"|style="(\s*)")/g, ''));

		return _head.val() + "\n<body" + (s ? ' ' + s : '') + ">\n" + content + "\n</body>\n</html>";
	}

	function _getContent() {
		return _content.val() || _getFrameContent();
	}

	function _getHTMLStructure(html) {
		var parts = html.match(/([^]*)<body([^>]*)?>([^]*)<\/body>([^]*)/m);

		return {
			parts: parts ? parts : ['', '', '', '<multi>' + html + '</multi>'],
			content: parts ? parts[3] : '<multi>' + html + '</multi>',
			head: parts ? $.trim(parts[1]) : '',
			bodyattributes: parts ? $('<div' + (parts[2] || '') + '></div>')[0].attributes : ''
		};
	}

	function _setContent(content, delay, saveit, extrastyle) {

		var structure = _getHTMLStructure(content);

		var attrcount = structure.bodyattributes.length,
			doc = (isWebkit || isMozilla) ? _iframe[0].contentWindow.document : _idoc,
			headstyles = $(doc).find('head').find('link'),
			headdoc = doc.getElementsByTagName('head')[0];

		_head.val(structure.head);
		if (!extrastyle) extrastyle = '';
		headdoc.innerHTML = structure.head.replace(/([^]*)<head([^>]*)?>([^]*)<\/head>([^]*)/m, '$3' + extrastyle);
		$(headdoc).append(headstyles);

		doc.body.innerHTML = structure.content;

		if (attrcount) {
			while (attrcount--) {
				doc.body.setAttribute(structure.bodyattributes[attrcount].name, structure.bodyattributes[attrcount].value)
			}
		}

		if (delay !== false) {
			clearTimeout(timeout);
			timeout = setTimeout(function () {
				modules && modules.refresh && modules.refresh();
				_trigger('refresh');
			}, delay || 100);
		} else {
			_trigger('refresh');
		}

		if (typeof saveit == 'undefined' || saveit === true) _trigger('save');
	}

	function _getAutosaveString() {
		return _title.val() + _content.val() + _excerpt.val() + _subject.val() + _preheader.val();
	}

	function _ajax(action, data, callback, errorCallback, dataType) {

		if ($.isFunction(data)) {
			if ($.isFunction(callback)) {
				errorCallback = callback;
			}
			callback = data;
			data = {};
		}

		dataType = dataType ? dataType : "JSON";
		$.ajax({
			type: 'POST',
			url: ajaxurl,
			data: $.extend({
				action: 'mailster_' + action,
				_wpnonce: wpnonce
			}, data),
			success: function (data, textStatus, jqXHR) {
				callback && callback.call(this, data, textStatus, jqXHR);
			},
			error: function (jqXHR, textStatus, errorThrown) {
				var response = $.trim(jqXHR.responseText);
				if (textStatus == 'error' && !errorThrown) return;
				if (console) console.error(response);
				if ('JSON' == dataType) {
					var maybe_json = response.match(/{(.*)}$/);
					if (maybe_json && callback) {
						try {
							callback.call(this, $.parseJSON(maybe_json[0]));
						} catch (e) {
							if (console) console.error(e);
						}
						return;
					}
				}
				errorCallback && errorCallback.call(this, jqXHR, textStatus, errorThrown);
				alert(textStatus + ' ' + jqXHR.status + ': ' + errorThrown + '\n\n' + mailsterL10n.check_console)

			},
			dataType: dataType
		});
	}

	function _sanitize(string) {
		return $.trim(string).toLowerCase().replace(/ /g, '_').replace(/[^a-z0-9_-]*/g, '');
	}

	function _time() {

		var t, x, h, m, l, usertime = new Date(),
			elements = $('.time'),
			deliverytime = $('.deliverytime').eq(0),
			activecheck = $('#mailster_data_active'),
			servertime = parseInt(elements.data('timestamp'), 10) * 1000,
			seconds = false,
			offset = servertime - usertime.getTime() + (usertime.getTimezoneOffset() * 60000);

		var delay = (seconds) ? 1000 : 20000;

		function set() {
			t = new Date();

			usertime = t.getTime();
			t.setTime(usertime + offset);
			h = t.getHours();
			m = t.getMinutes();

			if (!_disabled && x && m != x[1] && !activecheck.is(':checked')) deliverytime.val(zero(h) + ':' + zero(m));
			x = [];
			x.push(t.getHours());
			x.push(t.getMinutes());
			if (seconds) x.push(t.getSeconds());
			l = x.length;
			for (var i = 0; i < l; i++) {
				x[i] = zero(x[i]);
			};
			elements.html(x.join('<span class="blink">:</span>'));
			setTimeout(function () {
				set();
			}, delay);
		}

		function zero(value) {
			if (value < 10) {
				value = '0' + value;
			}
			return value;
		}

		set();
	}


	function sprintf() {
		var a = Array.prototype.slice.call(arguments),
			str = a.shift(),
			total = a.length,
			reg;
		for (var i = 0; i < total; i++) {
			reg = new RegExp('%(' + (i + 1) + '\\$)?(s|d|f)');
			str = str.replace(reg, a[i]);
		}
		return str;
	}

	function _getRealDimensions(el, callback) {
		el = el.eq(0);
		if (el.is('img') && el.attr('src')) {
			var image = new Image(),
				factor;
			image.onload = function () {
				factor = ((image.width / el.width()).toFixed(1) || 1);
				if (callback) callback.call(this, image.width, image.height, isFinite(factor) ? parseFloat(factor) : 1)
			}
			image.src = el.attr('src');
		};
	}

	function _rgbToHex(r, g, b, hash) {
		return (hash === false ? '' : '#') + ((1 << 24) + (r << 16) + (g << 8) + b).toString(16).slice(1);
	}

	if (document.selection && document.selection.createRange) {

		selectRange = function (input, startPos, endPos) {
			input.focus();
			input.select();
			var range = document.selection.createRange();
			range.collapse(true);
			range.moveEnd("character", endPos);
			range.moveStart("character", startPos);
			range.select();
			return true;
		}

	} else {

		selectRange = function (input, startPos, endPos) {
			input.selectionStart = startPos;
			input.selectionEnd = endPos;
			return true;
		}
	}

	if (window.getSelection) { // all browsers, except IE before version 9

		getSelect = function (input) {
			var selText = "";
			if (document.activeElement && (document.activeElement.tagName.toLowerCase() == "textarea" || document.activeElement.tagName.toLowerCase() == "input")) {
				var text = document.activeElement.value;
				selText = text.substring(document.activeElement.selectionStart, document.activeElement.selectionEnd);
			} else {
				var selRange = window.getSelection();
				selText = selRange.toString();
			}

			return selText;
		}

	} else {

		getSelect = function (input) {
			var selText = "";
			if (document.selection.createRange) { // Internet Explorer
				var range = document.selection.createRange();
				selText = range.text;
			}

			return selText;
		}
	}

	window.tb_position = function () {
		if (!window.TB_WIDTH || !window.TB_HEIGHT) return;
		jQuery("#TB_window").css({
			marginTop: '-' + parseInt((TB_HEIGHT / 2), 10) + 'px',
			marginLeft: '-' + parseInt((TB_WIDTH / 2), 10) + 'px',
			width: TB_WIDTH + 'px'
		});
	}

	_init();

});