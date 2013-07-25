(function ($) {
	'use strict';

	var tests = {
			filereader: typeof FileReader !== 'undefined',
			dragdrop: 'draggable' in document.createElement('span'),
			formdata: !!window.FormData,
			progress: 'upload' in new XMLHttpRequest()
		},
		acceptedTypes = {
			'image/png': true,
			'image/jpeg': true,
			'image/gif': true
		};

	$.widget('gallery.h5up', {

		version: 'in flux',

		// default options
		options: {
			animationSpeed: 'slow'
		},

		input: null,
		canvas: null,
		status: null,
		button: null,
		message: null,
		success: 0,
		errors: 0,

		queue: {
			count: 0,
			items: {},

			size: function() {
				var size = 0, key;
				for (key in this.items) {
					if (this.items.hasOwnProperty(key)) { size++; }
				}
				return size;
			}
		},

		_create: function() {
			this.input = this.element.get(0);
			this.canvas = $('#g-add-photos-canvas');
			this.status = $('#g-add-photos-status');
			this.button = $(this.input.form.elements.cancel);
			this.message = $('#g-add-photos-status-message');

			var self = this,
				dropzone = document.getElementById('h5up-dropzone'),
				messages = $('#h5up-action-status'),
				alerts = {
					filereader: '#h5up-filereader',
					dragdrop: '#h5up-dragdrop',
					formdata: '#h5up-formdata',
					progress: '#h5up-progress'
				};

			for (var api in alerts) {
				if (tests[api] !== false) {
					$(alerts[api]).remove();
				} else {
					switch (api) {
						case 'filereader':
							this.input.form.elements.show_preview.disabled = true;
							break;
					}
				}
			}

			if (!messages.children().length) {
				messages.remove();
			}

			if (tests.dragdrop) {
				dropzone.ondragover = function () { this.className = 'hover'; return false; };
				dropzone.ondragend = function () { this.className = ''; return false; };
				dropzone.ondragleave = function () { this.className = ''; return false; };
				dropzone.ondrop = function (event) {
					this.className = '';
					event.preventDefault();
					self._readFiles(event.dataTransfer.files);
				};
			} else {
				dropzone.className = 'hidden';
			}

			this.input.onchange = function () {
				self._readFiles(this.files);
			};

			this.status.on('click', '.close', function(event){
				event.preventDefault();
				$(this).parent().remove();
			});
		},

		_init: function() {
			// console.log('init');
			this.button.prop('disabled', true);
		},

		_destroy: function() {
			// console.log('destroy');
			this._clearQueue();
		},

		_pimpMyBytes: function(bytes) {
			var units	= ['bytes', 'KB', 'MB', 'GB', 'TB'],
				i		= 0,
				round	= 1,
				float	= bytes,
				unit	= units[i];

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
		},

		_showError: function(file, msg) {
			var result = $('<li class="g-error">' +
				'<a class="close">&times;</a>' +
				'<span>' + file.name + '</span> - ' +
				'<a target="_blank" href="http://codex.galleryproject.org/Gallery3:Troubleshooting:Uploading">' + msg + '</a>' +
				'</li>');
			this.status.append(result);
		},

		_previewFile: function(file, item) {
			if (this.input.form.elements.show_preview.checked &&
				tests.filereader === true &&
				acceptedTypes[file.type] === true)
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
		},

		_sendFile: function(file, item) {
			var self = this,
				formData,
				xhr,
				progress = item.children('progress');

			if (tests.formdata) {
				formData = new FormData();
				formData.append('Filedata', file);

				// add form elements
				$(this.input.form.elements).each(function(){
					if (!/^(file|fieldset|submit|button)$/.test(this.type)) {
						formData.append(this.name, this.value);
					}
				});

				// now post a new XHR request
				xhr = new XMLHttpRequest();
				xhr.open('POST', this.options.url);
				xhr.onload = function(event) {
					item.removeData('xhr');
					self._removeFile(item);
					if (progress) { progress.attr({value: 100}).html(100); }

					switch (event.target.status) {
						case 200:
							self.success++;
							var result = $('<li class="g-success">' +
								'<a class="close">&times;</a>' +
								'<span>' + file.name + '</span> - ' +
								self.options.messages[200] + '</li>');
							self.status.append(result);
							setTimeout(function() { result.slideUp(self.options.animationSpeed, function(){ result.remove(); }); }, 5000);
							break;

						case 500:
						case 404:
						case 400:
							self.errors++;
							self._showError(file, self.options.messages[event.target.status]);
							break;

						default:
							self.errors++;
							self._showError(file, self.options.messages['default']
													.replace('__INFO__', event.target.status)
													.replace('__TYPE__', event.target.status)); // nonsense, but I can't see where the error should come from
							break;
					}

					self._updateStatus();
				};
				xhr.onabort = function() {
					if (progress) { progress.removeAttr('value'); }
					item.prop({className: 'g-warning'});
				};

				if (tests.progress) {
					xhr.upload.onprogress = function (event) {
						if (event.lengthComputable) {
							var complete = (event.loaded / event.total * 100 | 0);
							progress.attr({value: complete}).html(complete);
						}
					};
				}

				xhr.send(formData);
				item.data('xhr', xhr);
			}
		},

		_readFiles: function(files) {
			for (var i = 0; i < files.length; i++) {
				if (files[i].size > this.options.sizeLimit) {
					this._showError(files[i], this.options.messages[400]);
				} else {
					this._addFile(files[i]);
				}
			}
		},

		_updateStatus: function() {
			// todo: handle asynchronous hickup
			this.message.load(this.options.statusUrl.replace('_S', this.success).replace('_E', this.errors));
		},

		_addFile: function(file) {
			this.queue.count++;

			var self = this,
				progress = (tests.progress) ? '<progress min="0" max="100"></progress>' : '',
				item = $('<li data-id="' + this.queue.count + '" class="g-info">' +
				'<a class="close">&times;</a>' +
				file.name + ' - ' + this._pimpMyBytes(file.size) +
				progress +
				'</li>');

			item.one('click', '.close', function(event){
				event.preventDefault();
				self._removeFile(item);
			});

			this.canvas.append(item);
			this.button.prop('disabled', false);

			this._previewFile(file, item);

			this.items = this.items.add(item);

			if (this.items.length <= this.options.requestLimit) {
				this._sendFile(file, item);
			} else {
				item.data('pending', true);
				item.data('file', file);
			}
		},

		_removeFile: function(item) {
			console.log(this.items, item);

			if (this.items.is(item)) {
				var xhr = item.data('xhr');

				if (xhr) {
					xhr.abort();
				}

				item.slideUp(this.options.animationSpeed, function(){ $(this).remove(); });
				// item.remove();

				this.items = this.items.not(item);
			}

			if (this.items.length) {
				this._next();
			} else {
				this.button.prop('disabled', true);
			}
		},

		_next: function() {
			var item = this.items.filter(':data(pending)').first();

			if (item) {
				item.removeData('pending');
				this._sendFile(item.data('file'), item);
				return true;
			}
		},

		_clearQueue: function() {
			this.items.children('progress').val(0);

			this.items.each(function(key) {
				var xhr = $(this).data('xhr');

				if (xhr) {
					xhr.abort();
				}
			});

			this.items.fadeOut(this.options.animationSpeed, function(){ $(this).remove(); });
			// this.items.remove();
			this.items = $();
			this.button.prop('disabled', true);
		},

		cancel: function() {
			// console.log('cancel');
			this._clearQueue();
		}

	});

})(jQuery);
