<?php
$reasonCategories = get_data('reasonCategories');
$data = get_data('activity_data');
?>
<div class="activity-dashboard">
    <div class="grid-row">
        <div class="col-9">
            <div class="grid-row">
               <h2><?= __('User Activity') ?></h2>
            </div>
            <div class="grid-row user-activity">
                <div class="col-4 dashboard-block">
                    <span class="dashboard-block-number"><?= $data['active_test_takers'] ?></span>
                    <h3><span class="icon icon-test-takers"></span> <?= __('Active test-takers') ?></h3>
                </div>
                <div class="col-4 dashboard-block">
                    <span class="dashboard-block-number"><?= $data['active_proctors'] ?></span>
                    <h3><span class="icon icon-test-taker"></span> <?= __('Active proctors') ?></h3>
                </div>
            </div>
            <div class="grid-row">
                <h2><?= __('Current Assessment Activity') ?></h2>
            </div>
            <div class="grid-row assessment-activity">
                <div class="col-4 dashboard-block">
                    <h4><span class="icon icon-play"></span> <?= __('Total Current Assessment Activity') ?></h4>
                    <span class="dashboard-block-number"><?= $data['total_current_assessments'] ?></span>
                </div>
                <div class="col-2 dashboard-block">
                    <h4><span class="icon icon-play"></span> <?= __('In Progress') ?></h4>
                    <span class="dashboard-block-number"><?= $data['in_progress_assessments'] ?></span>
                </div>
                <div class="col-2 dashboard-block">
                    <h4><span class="icon icon-time"></span> <?= __('Awaiting') ?></h4>
                    <span class="dashboard-block-number"><?= $data['awaiting_assessments'] ?></span>
                </div>
                <div class="col-2 dashboard-block">
                    <h4><span class="icon icon-continue"></span> <?= __('Authorized') ?></h4>
                    <span class="dashboard-block-number"><?= $data['authorized_but_not_started_assessments'] ?></span>
                </div>
                <div class="col-2 dashboard-block">
                    <h4><span class="icon icon-pause"></span> <?= __('Paused\' state') ?></h4>
                    <span class="dashboard-block-number"><?= $data['paused_assessments'] ?></span>
                </div>
            </div>
            <div class="grid-row">
                <div class="col-12">
                    <div class="js-delivery-list"></div>
                </div>
            </div>
            <div class="grid-row">
                <div class="col-12">
                    <div class="js-completed-assessments activity-chart"></div>
                </div>
            </div>
        </div>
        <div class="col-3">
            <div class="grid-row">
                <h2><?= __('Actions') ?></h2>
            </div>
            <div class="js-pause-active-executions-container" data-reasonCategories="<?= _dh(json_encode($reasonCategories)) ?>">
                <button class="js-pause btn-warning" type="button"><?= __('Pause Active Test Sessions') ?></button>
            </div>
        </div>
    </div>
</div>
