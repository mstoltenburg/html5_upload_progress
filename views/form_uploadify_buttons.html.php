<?php defined("SYSPATH") or die("No direct script access.") ?>

<button type="submit" class="ui-state-default ui-corner-all" onclick="$(this.form).submit(); return false;">
<?= t("Done") ?>
</button>

<button name="cancel" class="ui-state-default ui-corner-all" onclick="$('#h5up-input').h5up('cancel'); return false;" disabled="disabled">
<?= t("Cancel uploads") ?>
</button>

<span id="g-add-photos-status-message" />
