<?php defined("SYSPATH") or die("No direct script access.") ?>

<div>
	<div id="h5up-action-status" class="g-message-block">
		<? if ($suhosin_session_encrypt): ?>
		<p class="g-warning">
			<?= t("Error: your server is configured to use the <a href=\"%encrypt_url\"><code>suhosin.session.encrypt</code></a> setting from <a href=\"%suhosin_url\">Suhosin</a>.  You must disable this setting to upload photos.",
					array("encrypt_url" => "http://www.hardened-php.net/suhosin/configuration.html#suhosin.session.encrypt",
			"suhosin_url" => "http://www.hardened-php.net/suhosin/")) ?>
		</p>
		<? endif ?>

		<? if (identity::active_user()->admin && !$movies_allowed): ?>
		<p class="g-warning">
			<?= t("Movie uploading is disabled on your system. <a href=\"%help_url\">Help!</a>", array("help_url" => url::site("admin/movies"))) ?>
		</p>
		<? endif ?>

		<p class="g-warning" id="h5up-dragdrop"><?= t("Drag & drop is not supported") ?></p>
		<p class="g-warning" id="h5up-filereader"><?= t("File API & FileReader API is not supported") ?></p>
		<p class="g-warning" id="h5up-formdata"><?= t("XHR2's FormData is not supported") ?></p>
		<p class="g-warning" id="h5up-progress"><?= t("XHR2's upload progress is not supported") ?></p>
	</div>

	<div>
		<ul class="g-breadcrumbs ui-helper-clearfix">
			<? foreach ($album->parents() as $i => $parent): ?>
			<li<? if ($i == 0) print " class=\"g-first\"" ?>> <?= html::clean($parent->title) ?> </li>
			<? endforeach ?>
			<li class="g-active"> <?= html::purify($album->title) ?> </li>
		</ul>
	</div>

	<label for="h5up-input"><?= t("Select photos (%size max per file)...", array("size" => html5_upload_progress::pimp_my_bytes($size_limit_bytes))) ?></label>
	<input id="h5up-input" type="file" name="files[]" multiple="multiple" />
	<div id="h5up-dropzone">
		<p>Or drag an image from your desktop on to the drop zone.</p>
	</div>

	<ul id="g-add-photos-canvas" class="g-message-block"></ul>

	<ul id="g-add-photos-status" class="g-message-block"></ul>

</div>

<script>

(function ($) {
	'use strict';

	var dropzone = document.getElementById('h5up-dropzone'),
		input = document.getElementById('h5up-input'),
		canvas = $('#g-add-photos-canvas'),
		status = $('#g-add-photos-status'),
		messages = $('#h5up-action-status'),
		tests = {
			filereader: typeof FileReader != 'undefined',
			dragdrop: 'draggable' in document.createElement('span'),
			formdata: !!window.FormData,
			progress: "upload" in new XMLHttpRequest
		},
		alerts = {
			filereader: $('#h5up-filereader'),
			dragdrop: $('#h5up-dragdrop'),
			formdata: $('#h5up-formdata'),
			progress: $('#h5up-progress')
		},
		acceptedTypes = {
			'image/png': true,
			'image/jpeg': true,
			'image/gif': true
		};

	for (var api in alerts) {
		if (tests[api] !== false) {
			alerts[api].remove();
		}
	}

	if (!messages.children().length) {
		messages.remove();
	}

	var pimpMyBytes = function (bytes) {
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

		return float + ' ' + unit;
	};

	var showError = function (file, msg) {
		var result = $('<li class="g-error">'
			+ '<a class="close">&times;</a>'
			+ '<span>' + file.name + '</span> - '
			+ '<a target="_blank" href="http://codex.galleryproject.org/Gallery3:Troubleshooting:Uploading">' + msg + '</a>'
			+ '</li>');
		status.append(result);
	};

	var previewFile = function (file, item) {

		if (tests.filereader === true && acceptedTypes[file.type] === true) {
			var reader = new FileReader();
			reader.onload = function (event) {
				var image = new Image();
				image.src = event.target.result;
				image.height = 24; // a fake resize
				item.prepend(image);
			};

			reader.readAsDataURL(file);
		}

	};

	var sendFile = function (file) {
		var formData,
			xhr,
			item,
			progress,
			result;

		item = $('<li class="h5up-item g-info">'
			+ '<a class="close">&times;</a>'
			+ file.name + ' - ' + pimpMyBytes(file.size)
			+ '</li>');
		progress = $('<progress min="0" max="100" value="0">0</progress>');
		item.append(progress);
		canvas.append(item);

		previewFile(file, item);

		return true;

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
			xhr.open('POST', '<?= url::site("uploader/add_photo/{$album->id}") ?>');
			xhr.onload = function(event) {
				// console.log(event);
				progress.attr({value: 100}).html(100);

				switch (event.target.status) {
					case 200:
						item.slideUp("slow");
						result = $('<li class="g-success">'
							+ '<a class="close">&times;</a>'
							+ '<span>' + file.name + '</span> - '
							+ <?= t("Completed")->for_js() ?> + '</li>');
						status.append(result);
						setTimeout(function() { result.slideUp("slow"); }, 5000);
						break;

					case 500:
						showError(file, <?= t("Unable to process this photo")->for_js() ?>);
						break;

					case 404:
						showError(file, <?= t("The upload script was not found")->for_js() ?>);
						break;

					case 400:
						showError(file, <?= t("This photo is too large (max is %size bytes)", array("size" => $size_limit))->for_js() ?>);
						break;

					default:
						showError(file, <?= t("Server error: __INFO__ (__TYPE__)")->for_js() ?>
	                        .replace("__INFO__", event.target.status)
	                        .replace("__TYPE__", event.target.status)); // nonsense, but I can't see where the error should come from
						break;
				}

			};
			// xhr.abort();

			if (tests.progress) {
				xhr.upload.onprogress = function (event) {
					if (event.lengthComputable) {
						// console.log(event);
						var complete = (event.loaded / event.total * 100 | 0);
						progress.attr({value: complete}).html(complete);
					}
				}
			}

			xhr.send(formData);
		}
	}

	// var queue = {};
	// var addToQueue = function (file) {

	// };

	var readFiles = function (files) {
		for (var i = 0; i < files.length; i++) {
			if (files[i].size > <?= $size_limit_bytes ?>) {
				showError(files[i], <?= t("This photo is too large (max is %size bytes)", array("size" => $size_limit))->for_js() ?>);
			} else {
				sendFile(files[i]);
			}
		}
	};

	if (tests.dragdrop) {
		dropzone.ondragover = function () { this.className = 'hover'; return false; };
		dropzone.ondragend = function () { this.className = ''; return false; };
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

	status.click('.close', function(event){
		event.preventDefault();
		$(event.target).parent().remove();
	});

})(jQuery);


</script>