/* DOKUWIKI:include_once lib/clipboard.min.js */

var clipboard = new ClipboardJS('.clipu-c');

	clipboard.on('success', function (e) {
		console.log(e);
	});

	clipboard.on('error', function (e) {
		console.log(e);
	});

