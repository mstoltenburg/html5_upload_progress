<?php defined("SYSPATH") or die("No direct script access.") ?>

<input type="submit" value="<?= t("Done")->for_html_attr() ?>" class="submit ui-state-default ui-corner-all" />

<button name="cancel" class="ui-state-default ui-corner-all" onclick="$('#h5up-input').h5up('cancel'); return false;" disabled="disabled">
<?= t("Cancel uploads") ?>
</button>

<span id="g-add-photos-status-message" />
