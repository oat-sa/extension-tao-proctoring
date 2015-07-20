<?php
use oat\tao\helpers\Template;

print Template::inc('TaoProctoring/blocks/header.tpl');
print Template::inc('TaoProctoring/blocks/breadcrumbs.tpl', 'TaoProctoring', array('breadcrumbs' => get_data('breadcrumbs')));
?>
<div class="delivery-manager">

</div>
<?= Template::inc('TaoProctoring/blocks/footer.tpl'); ?>
