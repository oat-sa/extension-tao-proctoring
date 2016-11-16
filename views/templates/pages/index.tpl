<div class="container <?= get_data('cls'); ?>"<?php foreach(get_data('data') as $name => $value): ?>
 data-<?= $name; ?>="<?= _dh($value); ?>"
<?php endforeach; ?>>
    <div class="header"></div>
    <div class="content">
<?php if(has_data('title')): ?>
        <h1><?= get_data('title'); ?></h1>
<?php endif; ?>
<?php if(has_data('deliveries')): ?>
        <?php foreach(get_data('deliveries') as $delivery):?>
            <a href="<?=$delivery['url']?>" target="_blank"><?=$delivery['name']?></a>
        <?php endforeach;?>
<?php endif; ?>
        <div class="panel"></div>
        <div class="list"></div>
    </div>
</div>
