<div class="container <?= get_data('cls'); ?>"<?php foreach(get_data('data') as $name => $value): ?>
 data-<?= $name; ?>="<?= _dh($value); ?>"
<?php endforeach; ?>>
    <div class="header"></div>
    <div class="content">
<?php if(get_data('title')): ?>
        <h1><?= get_data('title'); ?></h1>
<?php endif; ?>
        <div class="panel"></div>
        <div class="filters"></div>
        <div class="list clearfix"></div>
    </div>
</div>