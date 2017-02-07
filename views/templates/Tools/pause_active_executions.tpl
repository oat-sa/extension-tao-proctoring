<?php
$reasonCategories = get_data('reasonCategories');
?>
<div class="js-pause-active-executions-container" data-reasonCategories="<?= _dh(json_encode($reasonCategories)) ?>">
    <button class="js-pause"><?= __('Pause Active Test Sessions') ?></button>
</div>
