<div class="assign-test-takers">
    <h1><?= __('Assign test takers to %s', get_data('delivery')->getLabel()) ?></h1>

    <section class="delivery">
        <div class="list" data-id="<?= get_data('delivery')->getUri() ?>" data-set="<?= count(get_data('testTakers')) ? _dh(json_encode(get_data('testTakers'))) : ''; ?>">
            <h2>
                <span class="loading"><?= __("Loading") ?>...</span>
            </h2>
        </div>
    </section>
</div>

