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
		<p><?= t("Or drag images from your desktop on to this drop zone.") ?></p>
	</div>

	<ul id="g-add-photos-canvas" class="g-message-block"></ul>

	<ul id="g-add-photos-status" class="g-message-block"></ul>

</div>

<script>

$('#h5up-input').h5up({
	url: <?= html::js_string(url::site("uploader/add_photo/{$album->id}")) ?>,
	statusUrl: <?= html::js_string(url::site("uploader/status/_S/_E")) ?>,
	messages: {
		200: <?= t("Completed")->for_js() ?>,
		400: <?= str_replace(' bytes)', ')', t("This photo is too large (max is %size bytes)", array("size" => html5_upload_progress::pimp_my_bytes($size_limit_bytes)))->for_js()) ?>,
		404: <?= t("The upload script was not found")->for_js() ?>,
		500: <?= t("Unable to process this photo")->for_js() ?>,
		'default': <?= t("Server error: __INFO__ (__TYPE__)")->for_js() ?>
	},
	sizeLimit: <?= (int) $size_limit_bytes ?>,
	requestLimit: <?= (int) $simultaneous_upload_limit ?>
});

</script>
