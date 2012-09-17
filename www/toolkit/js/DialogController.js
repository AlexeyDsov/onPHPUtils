/***************************************************************************
 *   Copyright (C) 2011-2012 by Alexey Denisov                             *
 *   alexeydsov@gmail.com                                                  *
 ***************************************************************************/

//costmization dialog
(function($){

	$.widget('tk.dialog', $.ui.dialog, {
		minimized: false,
		minimizedHeight: 0,
		options: {parent: null, url: null, once: false},
		refreshButton: null,
		shareButton: null,
		errorField: null,
		_init: function () {
			$.ui.dialog.prototype._init.apply(this, arguments);
			var self = this;
			
			//refresh button part {
			var refreshHtml = '<a href="#" class="ui-dialog-titlebar-refresh ui-corner-all" role="button" style="display: none;">'
				+ '<span class="ui-icon ui-icon-refresh">refresh</span>'
				+ '</a>';
			this.refreshButton = $(refreshHtml)
				.appendTo(this.uiDialogTitlebar)
				.click(function(){
					self.refreshUrl();
					return false;
				});
			// } refresh button part 

			//share button part {
			var shareHtml = '<a href="#" class="ui-dialog-titlebar-share ui-corner-all" role="button" style="display: none;">'
				+ '<span class="ui-icon ui-icon-signal-diag">share</span>'
				+ '</a>';
			this.shareButton = $(shareHtml)
				.appendTo(this.uiDialogTitlebar)
				.click(function(){
					self.shareUrl();
					return false;
				});
			// } share button part 
			
			// { Minimize button part
			var minimizeHtml = '<a href="#" class="ui-dialog-titlebar-minimize ui-corner-all" role="button">'
				+ '<span class="ui-icon ui-icon-minus">minimize</span>'
				+ '</a>';
			$(minimizeHtml)
				.appendTo(this.uiDialogTitlebar)
				.click(function(){
					self.switchMinimize();
					return false;
				});
			// } Minimize button part
			
			var errorFieldHtml = '<div class="ui-helper-clearfix errorField"></div>';
			this.errorField = $(errorFieldHtml)
				.insertAfter(this.uiDialogTitlebar)
				.css('max-height', '100px')
				.css('overflow-y', 'auto')
				.css('margin-bottom', '5px');
		},
		close: function() {
			$.ui.dialog.prototype.close.apply(this, arguments);
			if (this.options.parent) {
				var parentDialog = $('#' + this.options.parent);
				if (parentDialog.length == 1) {
					parentDialog.dialog('refreshUrl');
				}
			}
		},
		_setOption: function(name, value) {
			var args = arguments;
			if (name == 'once') {
				if (this.options.once == false) {
					this.options.once = true;
					var options = $.extend({}, $.tk._defaultOnce, args[1]);
					this.option(options);
				}
				return;
			}
			if (name == 'width' || name == 'height') {
				if (parseInt(value).toString() == value) {
					//ok it's already number
				} else if (value.indexOf('%', 0) == (value.length - 1) && value.length > 1) {
					var percent = parseInt(args[1]);
					if (percent < 0) percent = 0;
					if (percent > 100) percent = 100;
					
					var maxValue = args[0] == 'width' ? $(window).width() : $(window).height();
					args[1] = maxValue / 100 * percent;
				}
			}
			if (name == 'error') {
				this.errorField.toggle(!!value);
				this.errorField.empty();
				if (value) {
					var texts = (value ? value : '').split("\n");
					for (i = 0; i < texts.length; i++) {
						$('<span />').text(texts[i]).appendTo(this.errorField);
						if (i < texts.length - 1)
							this.errorField.append('<br />');
					}
				}
			}
			
			$.ui.dialog.prototype._setOption.apply(this, args);
			
			$(this.refreshButton).toggle(this.options.url !== null);
			$(this.shareButton).toggle(this.options.url !== null);
		},
		refreshUrl: function() {
			if (this.options.url) {
				$.tk.load({url: this.options.url, id: this.element.attr('id')});
			}
		},
		shareUrl: function() {
			window.open(this.options.url);
		},
		switchMinimize: function() {
			var widget = this.uiDialog;
			var uiDialogTitlebar = this.uiDialogTitlebar;
			var widgetHideParts = $('.ui-widget-header', widget).nextAll();
			if (this.minimized) {
				$(widget).height(this.widgetHeight);
				widgetHideParts.show();
				this.minimized = false;
			} else {
				this.widgetHeight = $(widget).height();
				widgetHideParts.hide();
				$(widget).height(uiDialogTitlebar.height() + 20);
				this.minimized = true;
			}
		},
		buttons: function(buttonsOptions) {
			var buttons = {};
			var self = this;
			
			$.each(buttonsOptions, function(buttonName, buttonParams){
				var button = null;
				if (typeof buttonParams == 'function') {
					button = buttonParams;
				} else {
					var buttonParams = $.extend(
						{url: null, dialogName: null, window: false, post: null},
						buttonParams
					);
					if (!buttonParams.url)
						return;

					if (buttonParams.window) {
						button = function() {
							var options = {url: buttonParams.url, post: buttonParams.post};
							if (buttonParams.dialogName)
								options.id = buttonParams.dialogName;
							else
								options.dialog = self.element;
							
							$.tk.load(options);
						};
					} else {
						button = function() {
							Application.goUrl(buttonParams.url, buttonParams.post);
						};
					}
				}
				if (button) {
					buttons[buttonName] = button;
				}
			});
			
			this.option('buttons', buttons);
		}
	});
	
	$.extend($.tk, {
		randomId: function () {
			var min = 10000;
			var max = 99999;
			var time = Math.floor(new Date().getTime() / 1000);
			var rand = Math.floor(Math.random() * (max - min + 1)) + min;
			return "" + time + rand;
		},
		get: function(options) {
			options = $.extend({}, this._getDialogOptions, options);
			
			var dialog = null;
			if (options.dialog) {
				dialog = $(options.dialog);
				if (dialog.length != 1) {
					return null;
				}
			} else if ((dialog = $('#' + options.id)).length == 0) {
				dialog = $.tk._spawn(options);
			}
			options.options.parent = options.parent;
			dialog.dialog('option', options.options);
			return dialog;
		},
		load: function(options) {
			options = $.extend({}, this._getDialogOptions, options);
			if (options.event && (options.event.which > 1 || options.event.metaKey)) {
				return true;
			}
			
			this._fillUrl(options);
			this._fillPosition(options);
			var self = this;
			
			if (options.url) {
				var ajaxOpts = {
					url: options.url,
					type: 'GET',
					dataType: "html",
					cache: false,
					// Complete callback (responseText is used internally)
					complete: function(jqXHR, status, responseText) {
						// Store the response as specified by the jqXHR object
						responseText = jqXHR.responseText;
						// If successful, inject the HTML into all the matched elements
						if (jqXHR.isResolved()) {
							jqXHR.done(function(r) {
								responseText = r;
							});
							var dialog = self.get(options)
							if (options.position) {
								dialog.dialog('option', 'position', options.position);
							}
							dialog
								.html($("<div>").append(responseText))
								.dialog("open")
								.dialog("moveToTop")
								.dialog('option', 'url', options.url);
							$('body').trigger('dialog.loaded');
							if ($.isFunction(options.callback)) {
								options.callback.apply(this);
							}
						} else {
							//If not success - making window with error msg
							var dialog = self.get();
							dialog.html($("<div>").append("Loading page error..."));
							if (typeof(initiateObject) !== 'undefined') {
								initiateObject = $(initiateObject);
								dialog.dialog('option', "position", [initiateObject.offset().left, initiateObject.offset().top]);
							}
							dialog.dialog('option', {dialogClass: 'ui-state-error', title: 'Error'});
							dialog.dialog('open');
						}
					}
				};
				if (options.post) {
					ajaxOpts.data = options.post;
					ajaxOpts.type = 'POST';
				}
				$.ajax(ajaxOpts);
			}
			return false;
		},
		_spawn: function(options) {
			if (options.id === null) {
				options.id = $.tk.randomId();
			}
			var dialogOptions = {
					disabled: true,
					autoOpen: false
			};
			$.extend(dialogOptions, options.options);
			return dialog = $("<div id=" + options.id + "><!-- --></div>")
				.appendTo('body')
				.dialog(dialogOptions);
		},
		_fillUrl: function(options) {
			if (options.url) return;
			if ($(options.link).filter('a').length == 1)
				options.url = $(options.link).attr('href');
		},
		_fillPosition: function(options) {
			if (!options.position && options.link) {
				initiateObject = $(options.link);
				var left = initiateObject.offset().left;
				var top = initiateObject.offset().top;
				if (left && top)
					options.position = [initiateObject.offset().left, initiateObject.offset().top];
			}
		},
		_getDialogOptions: {
			id: null,
			dialog: null, //instead id you can pass dialg container
			parent: null,
			link: null,
			event: null,
			url: null,
			post: null,
			callback: null,
			options: {error: null},
			position: null
		},
		_defaultOnce: {
			width: '600',
			height: '80%'
		}
	});
})(jQuery);