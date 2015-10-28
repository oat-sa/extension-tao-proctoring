<div class="deliveries-listing" data-list="<?= count(get_data('deliveries')) ? _dh(json_encode(get_data('deliveries'))) : ''; ?>">
    <div class="entries">
        <h2>
            <span class="loading"><?= __('Loading'); ?>...</span>
        </h2>
    </div>
</div>
