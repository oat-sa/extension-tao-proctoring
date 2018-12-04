<?php
use oat\tao\helpers\Template;
Template::inc('form_context.tpl', 'tao');
?>
<link rel="stylesheet" type="text/css" href="<?= Template::css('report.css','tao') ?>" media="screen"/>
<?= tao_helpers_Scriptloader::render() ?>
<header class="section-header flex-container-full"
        data-select-node="<?= get_data('selectNode'); ?>"
>
    <h2><?=get_data('formTitle')?></h2>
</header>

<div class="print-form main-container flex-container-main-form" data-purpose="form">
    <div class="form-content">
        <?=get_data('myForm')?>
    </div>
</div>

<?php Template::inc('footer.tpl', 'tao'); ?>