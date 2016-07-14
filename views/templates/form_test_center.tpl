<?php
use oat\tao\helpers\Template;

Template::inc('form_context.tpl', 'tao');
?>


<header class="section-header flex-container-full">
    <h2><?=get_data('formTitle')?></h2>
</header>

<div class="main-container flex-container-main-form">
    <div class="form-content">
        <?=get_data('myForm')?>
    </div>

</div>

<div class="data-container-wrapper flex-container-remainder">

    <?=get_data('administratorForm')?>

    <?=get_data('proctorForm')?>

    <?=get_data('childrenForm')?>

    <div class="eligible-deliveries clear" data-testcenter="<?=get_data('testCenter')?>">
        <div class="eligibility-table-container"></div>
        <div class="eligibility-editor-container"></div>
    </div>

</div>

<?php
Template::inc('footer.tpl', 'tao');
?>
