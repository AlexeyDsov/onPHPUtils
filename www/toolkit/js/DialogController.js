/***************************************************************************
 *   Copyright (C) 2011 by Alexey Denisov                                  *
 *   alexeydsov@gmail.com                                                  *
 ***************************************************************************/

var DialogController = DialogController || {};

DialogController.currentDialog = null;

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
				var dialog = self.markCurrent(self.getDialog(dialogId, parentId));
				if (typeof(initiateObject) !== 'undefined') {
					initiateObject = $(initiateObject);
					dialog.dialog('option', "position", [initiateObject.offset().left, initiateObject.offset().top]);
				}
				dialog.html($("<div>").append(responseText));
				dialog.dialog("open").dialog( "moveToTop" );
				$('body').trigger('dialog.loaded');
				self.setParam(dialogId, 'url', url);
			} else {
				//If not success - making window with error msg
				var dialog = self.markCurrent(self.spawn());
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

DialogController.markCurrent = function (dialog) {
	this.currentDialog = dialog;
	return dialog;
};

DialogController.getDialog = function (dialogId, parentId) {
	var dialog = $('#' + dialogId);
	if (dialog.length != 1) {
		dialog = this.spawn(dialogId, parentId);
	} else {
		if (parentId) {
			DialogController.setParent(dialogId, parentId);
		}
	}
	return dialog;
};

DialogController.spawn = function(dialogId, parentId) {
	if (typeof(dialogId) == 'undefined') {
		dialogId = this.generateId();
	}
	var html = "<div id=" + dialogId + "><!-- --></div><div id='" + dialogId + "_params' style='display: none'><!-- --></div>";
	$('body').append(html);
	var dialog = $('#' + dialogId).dialog({
		disabled: true,
		autoOpen: false
	});
	if (parentId) {
		DialogController.setParent(dialogId, parentId);
	}
	return dialog;
};

DialogController.setParent = function(dialogId, parentId) {
	DialogController.setParam(dialogId, 'parent_id', parentId);
	$('#' + dialogId).dialog({
		close: function() {
			DialogController.refresh(parentId);
		}
	})
};

DialogController.setParam = function(dialogId, name, value) {
	$('#' + dialogId).dialog('option', 'DC_' + name, value);
};

DialogController.getParam = function(dialogId, name) {
	var dialog = $('#' + dialogId);
	var result = dialog.dialog('option', 'DC_' + name);
	return result == dialog ? null : result;
};

DialogController.generateId = function () {
	var min = 10000;
	var max = 99999;
	var time = Math.floor(new Date().getTime() / 1000);
	var rand = Math.floor(Math.random() * (max - min + 1)) + min;
	return "" + time + rand;
};

DialogController.refresh = function(dialogId) {
	if ((url = DialogController.getParam(dialogId, 'url')) !== null) {
		DialogController.spawnByUrl(url, dialogId);
	}
};

DialogController.shareUrl = function(dialogId) {
	var url = DialogController.getParam(dialogId, 'url')
	if (url) {
		var urlDialog = DialogController.spawn();
		urlDialog.html('<input type="text" value="' + url + '&_window&_dialogId=' + dialogId + '" readonly disabled style="width: 100%"/>');
		urlDialog.dialog('open');
	}
};

DialogController.refreshParent = function(dialogId) {
	var parentId = DialogController.getParam(dialogId, 'parent_id');
	if(parentId) {
		DialogController.refresh(parentId);
	}
};

//costmization dialog
(function($){

	$.widget('tk.dialog', $.ui.dialog, {
		minimized: false,
		minimizedHeight: 0,
		_init: function () {
			$.ui.dialog.prototype._init.apply(this, arguments);
			
			// { Minimize button part
			var minimizeHtml = '<a href="#" class="ui-dialog-titlebar-minimize ui-corner-all" role="button">'
				+ '<span class="ui-icon ui-icon-minus">minimize</span>'
				+ '</a>';

			var self = this;
			var uiDialogTitlebar = this.uiDialogTitlebar;
			uiDialogTitlebar.append(minimizeHtml);
			this.uiDialogTitlebarMin = $('.ui-dialog-titlebar-minimize', uiDialogTitlebar)
				.click(function(){
					self.switchMinimize();
					return false;
				});
		},
		_setOption: function() {
			$.ui.dialog.prototype._setOption.apply(this, arguments);
			
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
})(jQuery);