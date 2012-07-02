/***************************************************************************
 *   Copyright (C) 2011 by Alexey Denisov                                  *
 *   alexeydsov@gmail.com                                                  *
 ***************************************************************************/

var DialogController = DialogController || {};

DialogController.spawnByLink = function (event, link, dialogId, parentId) {
	if ( event.which > 1 || event.metaKey ) {
		return true
	}
	var url = $(link).attr('href');
	this.spawnByUrl(url, dialogId, link, parentId);
	return false
};

DialogController.spawnByUrl = function (url, dialogId, initiateObject, parentId) {
	var self = this;
	$.ajax({
		url: url,
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
				var dialog = $.tk._getDialog({id: dialogId, parent: parentId})
				if (typeof(initiateObject) !== 'undefined') {
					initiateObject = $(initiateObject);
					dialog.dialog('option', "position", [initiateObject.offset().left, initiateObject.offset().top]);
				}
				dialog.html($("<div>").append(responseText));
				dialog.dialog("open").dialog( "moveToTop" );
				$('body').trigger('dialog.loaded');
				dialog.dialog('option', 'url', url);
			} else {
				//If not success - making window with error msg
				var dialog = $.tk._spawn();
				dialog.html($("<div>").append("Loading page error..."));
				if (typeof(initiateObject) !== 'undefined') {
					initiateObject = $(initiateObject);
					dialog.dialog('option', "position", [initiateObject.offset().left, initiateObject.offset().top]);
				}
				dialog.dialog('option', {dialogClass: 'ui-state-error', title: 'Error'});
				dialog.dialog('open');
			}
		}
	});
	return false;
};

DialogController.setParam = function(dialogId, name, value) {
	$('#' + dialogId).dialog('option', 'TK_' + name, value);
};

DialogController.getParam = function(dialogId, name) {
	var dialog = $('#' + dialogId);
	var result = dialog.dialog('option', 'TK_' + name);
	return result == dialog ? null : result;
};

DialogController.shareUrl = function(dialogId) {
	var url = DialogController.getParam(dialogId, 'url')
	if (url) {
		var urlDialog = $.tk._spawn();
		urlDialog.html('<input type="text" value="' + url + '&_window&_dialogId=' + dialogId + '" readonly disabled class="w95"/>');
		urlDialog.dialog('open');
	}
};

DialogController.refreshParent = function(dialogId) {
	var parentId = DialogController.getParam(dialogId, 'parent_id');
	if(parentId) {
		$.tk.getDialog.dialog('refresh');
	}
};

//costmization dialog
(function($){

	$.widget('tk.dialog', $.ui.dialog, {
		minimized: false,
		minimizedHeight: 0,
		options: {parent: null, url: null},
		refreshButton: null,
		shareButton: null,
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
			var shareHtml = '<a href="#" class="ui-dialog-titlebar-share ui-corner-all" role="button">'
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
		},
		_setOption: function() {
			$.ui.dialog.prototype._setOption.apply(this, arguments);
			
			$(this.refreshButton).toggle(this.options.url !== null);
			$(this.shareButton).toggle(this.options.url !== null);
		},
		refreshUrl: function() {
			if (this.options.url) {
				DialogController.spawnByUrl(this.options.url, this.element.attr('id'));
			}
		},
		shareUrl: function() {
			if (this.options.url) {
				var urlDialog = $.tk._spawn({id: null});
				urlDialog.html(
					'<input type="text" value="'
						+ this.options.url + '&_window&_dialogId='
						+ this.element.attr('id') + '" readonly disabled class="w95"/>'
				);
				urlDialog.dialog('open');
			}
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
			var dialogId = this.element.attr('id');
			
			$.each(buttonsOptions, function(buttonName, buttonParams){
				var button = null;
				if (typeof buttonParams == 'function') {
					button = buttonParams;
				} else {
					if (!buttonParams.url)
						return;

					if (buttonParams.window) {
						var buttonDialog = buttonParams.dialogName;
						button = function() {
							DialogController.spawnByUrl(
								buttonParams.url,
								typeof(buttonDialog) !== 'undefined' ? buttonDialog : dialogId
							);
						};
					} else {
						button = function() {
							Application.goUrl(buttonParams.url);
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
			this._fillUrl(options);
			this._fillPosition(options);
		},
		_getDialog: function(options) {
			var dialog = null;
			if ((dialog = $('#' + options.id)).length == 1) {
				if (options.parent) {
					dialog.dialog('option', 'parent', options.parent);
				}
			} else {
				dialog = $.tk._spawn(options);
			}
			return dialog;
		},
		_spawn: function(options) {
			if (options.id === null) {
				options.id = $.tk.randomId();
			}
			return dialog = $("<div id=" + options.id + "><!-- --></div>")
				.appendTo('body')
				.dialog({
					disabled: true,
					autoOpen: false,
					parent: options.parent ? options.parent : null
			});
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
			parent: null,
			link: null,
			event: null,
			url: null,
			options: {},
			position: null
		}
	});
})(jQuery);