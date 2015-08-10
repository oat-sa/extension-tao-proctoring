<div class="deliveries-listing">
    <h1><?= __("My Deliveries"); ?></h1>
    <h2>
        <span class="empty-list hidden"><?= __("No deliveries available") ?></span>
        <span class="available-list hidden"><?= __("Available") ?>: <span class="count"></span></span>
        <span class="loading"><?= __("Loading") ?>...</span>
    </h2>
    <div class="list" data-list="<?= count(get_data('deliveries')) ? _dh(json_encode(get_data('deliveries'))) : ''; ?>"></div>
</div>
