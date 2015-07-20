<?php
use oat\tao\helpers\Template;

print Template::inc('TaoProctoring/blocks/header.tpl');
?>
<div class="deliveries-listing">
    <h1><?= __("My Deliveries"); ?></h1>

    <?php if (count(get_data('deliveries')) > 0) : ?>
    <h2>
        <?= __("Available") ?>: <?= count($deliveries); ?>
    </h2>
    <ul class="entry-point-box plain">
        <?php foreach ($deliveries as $delivery) : ?>
        <?php $url = empty($delivery['disabled']) ? _url('delivery', 'TaoProctoring', null, array('uri' => $delivery['uri'])) : '#'?>
        <li>
            <a class="block entry-point<?= !empty($delivery['disabled']) ? ' disabled' : '' ?>" href="<?= $url ?>">
            <h3><?= _dh($delivery['label']) ?></h3>

            <div class="clearfix">
                <span class="text-link" href="<?= $url ?>"><span class="icon-play"></span> <?= __('Manage') ?> </span>
            </div>
            </a>
        </li>
        <?php endforeach; ?>
    </ul>
    <?php endif; ?>
</div>
<?= Template::inc('TaoProctoring/blocks/footer.tpl'); ?>
