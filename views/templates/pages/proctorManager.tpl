<div class="container <?= get_data('cls'); ?>"<?php foreach(get_data('data') as $name => $value): ?>
 data-<?= $name; ?>="<?= _dh($value); ?>"
<?php endforeach; ?>>
    <div class="header"></div>
    <div class="content">
<?php if(get_data('title')): ?>
        <h1><?= get_data('title'); ?></h1>
<?php endif; ?>
        <div class="panel flex-grid">
            <div class="flex-col-4 test-center-panel"></div>
            <div class="flex-col-8 proctor-panel">
                <div class="proctor-list hidden"></div>
                <div class="proctor-create hidden"></div>
                <div class="proctor-default">
                    <div class="message"><?= get_data('select-message'); ?></div>
                </div>
            </div>
        </div>
        <div class="list"></div>
    </div>
</div>
