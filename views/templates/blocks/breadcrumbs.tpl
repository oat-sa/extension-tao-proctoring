<?php if (count(get_data('breadcrumbs'))): ?>
<ul class="breadcrumbs">
    <?php foreach (get_data('breadcrumbs') as $breadcrumb): ?>
    <li data-breadcrumb="<?= $breadcrumb['id'] ?>">
        <?php $label = $breadcrumb['label'] . (!empty($breadcrumb['data']) ? ' - ' . $breadcrumb['data']: ''); ?>
        <?php if (!empty($breadcrumb['url'])): ?>
        <a href="<?= $breadcrumb['url'] ?>"><?= $label ?></a>
        <?php else: ?>
        <span class="a"><?= $label ?></span>
        <?php endif; ?>
    <?php endforeach; ?>
</ul>
<?php endif; ?>
