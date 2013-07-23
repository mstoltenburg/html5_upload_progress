(function ($) {
	'use strict';

	var input,
		canvas,
		status,
		message,
		settings,
		success = 0,
		errors = 0,
		defaults = {
			animationSpeed: 'slow'
		},
		tests = {
			filereader: typeof FileReader != 'undefined',
			dragdrop: 'draggable' in document.createElement('span'),
			formdata: !!window.FormData,
			progress: "upload" in new XMLHttpRequest
		},
		acceptedTypes = {
			'image/png': true,
			'image/jpeg': true,
			'image/gif': true
		};

	function pimpMyBytes (bytes) {
		var units	= ['bytes', 'KB', 'MB', 'GB', 'TB'];

		var i		= 0;
		var round	= 1;
		var float	= bytes;
		var unit	= units[i];

		while (float >= 1024 && units.length > ++i) {
			float	= float / 1024;
			unit	= units[i];
		}

		if (float % 1 !== 0) {
			if (float < 10) {
				round	= 100;
			} else if (float < 100) {
				round	= 10;
			}
		}

		float = Math.round(float * round) / round;

		return float + ' ' + unit;
	};

	function showError (file, msg) {
		var result = $('<li class="g-error">'
			+ '<a class="close">&times;</a>'
			+ '<span>' + file.name + '</span> - '
			+ '<a target="_blank" href="http://codex.galleryproject.org/Gallery3:Troubleshooting:Uploading">' + msg + '</a>'
			+ '</li>');
		status.append(result);
	};

	function previewFile (file, item) {
		if (input.form.elements.show_preview.checked
			&& tests.filereader === true
			&& acceptedTypes[file.type] === true)
		{
			var reader = new FileReader();
			reader.onload = function (event) {
				var image = new Image();
				image.src = event.target.result;
				image.height = 48; // a fake resize
				item.prepend(image);
			};

			reader.readAsDataURL(file);
		}
	};

	function sendFile (file, item) {
		var formData,
			xhr,
			progress = item.children('progress');

		if (tests.formdata) {
			formData = new FormData();
			formData.append('Filedata', file);

			// add form elements
			$(input.form.elements).each(function(){
				if (!/^(file|fieldset|submit|button)$/.test(this.type)) {
					formData.append(this.name, this.value);
				}
			});

			// now post a new XHR request
			xhr = new XMLHttpRequest();
			xhr.open('POST', settings.url);
			xhr.onload = function(event) {
				item.removeData('xhr');
				queue.remove(item.data('id'));
				progress.attr({value: 100}).html(100);

				switch (event.target.status) {
					case 200:
						success++;
						var result = $('<li class="g-success">'
							+ '<a class="close">&times;</a>'
							+ '<span>' + file.name + '</span> - '
							+ settings.messages[200] + '</li>');
						status.append(result);
						setTimeout(function() { result.slideUp(settings.animationSpeed, function(){ result.remove(); }); }, 5000);
						break;

					case 500:
					case 404:
					case 400:
						errors++;
						showError(file, settings.messages[event.target.status]);
						break;

					default:
						errors++;
						showError(file, settings.messages['default']
												.replace("__INFO__", event.target.status)
												.replace("__TYPE__", event.target.status)); // nonsense, but I can't see where the error should come from
						break;
				}

				updateStatus();
			};
			xhr.onabort = function(event) {
				progress.removeAttr('value');
				item.prop({className: 'g-warning'}); //.clone().appendTo(status);
			};

			if (tests.progress) {
				xhr.upload.onprogress = function (event) {
					if (event.lengthComputable) {
						var complete = (event.loaded / event.total * 100 | 0);
						progress.attr({value: complete}).html(complete);
					}
				}
			}

			xhr.send(formData);
			item.data('xhr', xhr);
		}
	};

	function readFiles (files) {
		for (var i = 0; i < files.length; i++) {
			if (files[i].size > settings.sizeLimit) {
				showError(files[i], settings.messages[400]);
			} else {
				queue.add(files[i]);
			}
		}
	};

	function updateStatus () {
		$.get(settings.status.replace("_S", success).replace("_E", errors),
			function(data) {
				message.html(data);
			}
		);
	};

	var queue = {
		count: 0,
		items: {},

		size: function() {
			var size = 0, key;
			for (key in this.items) {
				if (this.items.hasOwnProperty(key)) size++;
			};
			return size;
		},

		add: function(file) {
			this.count++;
			var item = $('<li data-id="' + this.count + '" class="g-info">'
				+ '<a class="close">&times;</a>'
				+ file.name + ' - ' + pimpMyBytes(file.size)
				+ '<progress min="0" max="100"></progress>'
				+ '</li>');
			item.on('click', '.close', {id: this.count}, function(event){
				event.preventDefault();
				queue.remove(event.data.id);
			});

			canvas.append(item);

			previewFile(file, item);

			this.items[this.count] = item;

			if (this.size() <= settings.requestLimit) {
				sendFile(file, item);
			} else {
				item.data('pending', true);
				item.data('file', file);
			}
		},

		remove: function(key) {
			if (this.items[key]) {
				var xhr = this.items[key].data('xhr');

				if (xhr) {
					xhr.abort();
				}

				this.items[key].slideUp(settings.animationSpeed, function(){ $(this).remove(); });
				// this.items[key].remove();

				delete this.items[key];
			}

			this.next();
		},

		next: function() {
			var key;
			for (key in this.items) {
				if (this.items.hasOwnProperty(key) && this.items[key].data('pending')) {
					this.items[key].removeData('pending')
					sendFile(this.items[key].data('file'), this.items[key]);
					return true;
				}
			};
		},

		clear: function() {
			console.log(this.size());
		}
	};

	var h5up = {
		init: function(element) {
			input = document.getElementById('h5up-input');
			canvas = $('#g-add-photos-canvas');
			status = $('#g-add-photos-status');
			message = $('#g-add-photos-status-message');

			var dropzone = document.getElementById('h5up-dropzone'),
				messages = $('#h5up-action-status'),
				alerts = {
					filereader: $('#h5up-filereader'),
					dragdrop: $('#h5up-dragdrop'),
					formdata: $('#h5up-formdata'),
					progress: $('#h5up-progress')
				};

			for (var api in alerts) {
				if (tests[api] !== false) {
					alerts[api].remove();
				}
			}

			if (!messages.children().length) {
				messages.remove();
			}

			if (tests.dragdrop) {
				dropzone.ondragover = function () { this.className = 'hover'; return false; };
				dropzone.ondragend = function () { this.className = ''; return false; };
				dropzone.ondragleave = function () { this.className = ''; return false; };
				dropzone.ondrop = function (e) {
					this.className = '';
					e.preventDefault();
					readFiles(e.dataTransfer.files);
				}
			} else {
				dropzone.className = 'hidden';
			}

			input.onchange = function () {
				readFiles(this.files);
				// input.value = '';
			};

			status.on('click', '.close', function(event){
				event.preventDefault();
				$(this).parent().remove();
			});
		}
	};

	$.fn.h5up = function (options) {
		settings = $.extend({}, defaults, options);

		return this.each(function () {
			h5up.init(this);

			return this;
		});
	};

})(jQuery);
