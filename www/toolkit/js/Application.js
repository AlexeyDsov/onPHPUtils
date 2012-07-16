/***************************************************************************
 *   Copyright (C) 2011 by Alexey Denisov                                  *
 *   alexeydsov@gmail.com                                                  *
 ***************************************************************************/

var Application = Application || {};

Application.ternaryRadio = null;
Application.emptyInputPattern = ':text[value=\'\'],:checkbox[checked=false],:radio[checked=false]';

Application.hideFilters = function(button, form) {
	var filter = function() {
		if ($(this).hasClass('error')) {
			return false;
		}
		var allEmpty = true;
		$(this).find(':input').each(function() {
			if ($(this).filter(':checkbox').length) {
				allEmpty = allEmpty && !$(this).attr('checked');
			} else {
				allEmpty = allEmpty && $(this).val() == '';
			}
		});
		return allEmpty;
	};
	$(form).find('div._filterBlock').filter(filter).hide();
	
	var filter = function() {return $(this).find('div._filterBlock:visible').length == 0;};
	$(form)
		.find('div._propertyBlock')
		.filter(filter)
		.hide();
		
	$(button).hide().next().show();
}

Application.showFilters = function(button, form) {
	$(form).find('div._filterBlock,div._propertyBlock').show();
	$(button).hide().prev().show();
}

Application.submit = function(form) {
	$(form.elements).filter(Application.emptyInputPattern + ',:submit').attr('disabled', 'true');
	form = $(form);
	var params = form.serializeArray();
	var url = form.attr('action');
	var method = (form.attr('method') == 'post') ? 'POST' : 'GET';
	var options = {container: '#pjaxArea', type: method};

	if (method != 'POST') {
		options.url = (url + (/\?/.test(url) ? "&" : "?") + $.param(params));
	} else {
		options.url = url;
		options.data = params;
	}

	$.pjax(options);
	return false;
}

Application.goUrl = function(url, post) {
	if (post == 'undefined')
		post = null;
	
	var method = post ? 'POST' : 'GET';
	var options = {container: '#pjaxArea', url: url, type: method};
	if (post) {
		options.data = post;
	}
	$.pjax(options);
}

Application.initDatepicker = function () {
	var inputs = $('._hasDatepicker');
	inputs.removeClass('_hasDatepicker').addClass('_hasDatepickerInit');
	inputs.datepicker({buttonImage: '/images/datepicker.gif', dateFormat: 'yy-mm-dd', buttonText: 'Choose'});

	var inputs = $('._hasDatepickerTime');
	inputs.removeClass('_hasDatepicker').addClass('_hasDatepickerInit');
	inputs.datepicker({buttonImage: '/images/datepicker.gif', dateFormat: 'yy-mm-dd 00:00:00', buttonText: 'Choose'});
}

Application.init = function () {
	$('a.js-pjax').pjax('#pjaxArea');
	$('form.js-pjax').live('submit', function(event){Application.submit(this);return false;});
	Application.initDatepicker();
	$('#pjaxArea').bind('end.pjax', function() {
		Application.initDatepicker();
	});
	$('body').bind('dialog.loaded', function() {
		Application.initDatepicker();
	});
	
	$('._ternary').live('click', function(event){
		TernaryRadio.switched($(this));
	});
}

Application.onmouseoverWrap = function (object, func) {
	$(object).removeAttr('onmouseover');
	func(object);
}

var TernaryRadio = TernaryRadio || {};

TernaryRadio.switched = function (input) {
	var val = input.val();
	if (val == '1') {
		input.parent().prev().prev().children('input').attr('checked', false);
	} else if (val == '0') {
		input.parent().prev().children('input').attr('checked', false);
	} else {
		input.parent().next().children('input').attr('checked', false);
		input.parent().next().next().children('input').attr('checked', false);
	}
}

$(function(){
	$('body').ajaxStart(function(){$('.ajaxLoader').show();});
	$('body').ajaxStop(function(){$('.ajaxLoader').hide();});
});

/* Autocomplete shorter */
var AppAutocomplete = AppAutocomplete || {};

AppAutocomplete.url = '';

AppAutocomplete.autocomplete = function (options) {
	var options = this._autocompleteMakeOptions(options);
	
	var objectName = $(options.inputText);
	var objectId = $(options.inputId);
	
	objectName.autocomplete({
		source: function( request, response ) {
			var getData = $.extend(
				{object: options.object, property: options.property, search: request.term},
				options.params
			);
			$.ajax({
				url: AppAutocomplete.url,
				dataType: 'json',
				data: getData,
				success: function( data ) {
					response(data.array);
				}
			});
		},
		minLength: options.minLength,
		select: function( event, ui ) {
			objectId.val(ui.item.id);
			return true;
		},
		change: function(event, ui) {
			if (!ui.item) {
				objectId.val('');
			}
		}
	});
}

AppAutocomplete._autocompleteMakeOptions = function (options) {
	var defaults = {
		object: '',
		property: '',
		inputId: null,
		inputText: null,
		minLength: 1,
		params: {}
	};
	var options = $.extend(true, {}, defaults, options);
	
	if (!options.object) { throw "Empty option 'object'"; }
	if (!options.property) { throw "Empty option 'property'"; }
	if (!options.inputId || options.inputId.length != 1) { throw "Empty option 'inputId'"; }
	if (!options.inputText || options.inputText.length != 1) { throw "Empty option 'inputText'"; }
	
	return options;
}