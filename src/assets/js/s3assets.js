$.put = function (url, data, callback, type) {

	if ($.isFunction(data)) {
		type = type || callback,
			callback = data,
			data = {}
	}

	return $.ajax({
		url: url,
		type: 'PUT',
		success: callback,
		data: data,
		contentType: type
	});
}