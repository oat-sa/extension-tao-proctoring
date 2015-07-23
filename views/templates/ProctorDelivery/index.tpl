<?php
use oat\tao\helpers\Template;

print Template::inc('TaoProctoring/blocks/header.tpl');
print Template::inc('TaoProctoring/blocks/breadcrumbs.tpl');
?>
<div class="delivery-manager">
    <h1><?= get_data('delivery')->getLabel() ?></h1>
    <aside class="action-bar">
        <button class="small assign"><span class="icon icon-add"></span><?= __('Add test taker'); ?></button>
    </aside>
    <aside class="stats-panel">

    </aside>
    <section class="delivery">
        <div class="data">
            <table class="matrix">
                <colgroup>
                    <col class="cell-selection" />
                    <col class="cell-name" span="3">
                    <col class="cell-status">
                    <col class="cell-actions">
                </colgroup>
                <thead>
                    <tr>
                        <th></th>
                        <th><?= __('First name') ?></th>
                        <th><?= __('Last name') ?></th>
                        <th><?= __('Company name') ?></th>
                        <th><?= __('Status') ?></th>
                        <th><?= __('Actions') ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php if (count(get_data('testTakers'))): ?>
                    <?php foreach($testTakers as $testTaker): ?>
                    <tr>
                        <td><input type="checkbox" name="testtaker[<?= $testTaker['uri'] ?>]" value="1" /></td>
                        <td><?= $testTaker['firstname'] ?></td>
                        <td><?= $testTaker['lastname'] ?></td>
                        <td><?= $testTaker['company'] ?></td>
                        <td><?= $testTaker['status'] ?></td>
                        <td>
                            <button class="small validate" title="<?= __('Validate the request') ?>"><span class="icon icon-validate"></span></button>
                            <button class="small lock" title="<?= __('Lock the test taker') ?>"><span class="icon icon-lock"></span></button>
                            <button class="small comment" title="<?= __('Write comment') ?>"><span class="icon icon-comment"></span></button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="message"><?= __('No test takers assigned to this delivery!'); ?></td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>

        </div>
        <div class="pagination">

        </div>
    </section>
</div>
<?= Template::inc('TaoProctoring/blocks/footer.tpl'); ?>
