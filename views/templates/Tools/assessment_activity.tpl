<?php

$config           = get_data('config');
$reasonCategories = get_data('reasonCategories');

?>

<div class="activity-dashboard" data-config="<?= _dh(json_encode($config)) ?>">

    <div class="grid-row">

        <div class="col-9">
            <div class="grid-row">
               <h2><?= __('User Activity') ?></h2>
            </div>
            <div class="grid-row user-activity"></div>

            <div class="grid-row">
                <h2><?= __('Current Assessment Activity') ?></h2>
            </div>
            <div class="grid-row assessment-activity"></div>

            <div class="grid-row">
                <div class="col-12 activity-chart">
                    <select class="select2 js-activity-chart-interval" data-has-search="false">
                        <option value="day"><?= __('Last Day') ?></option>
                        <option value="week"><?= __('Last Week') ?></option>
                        <option value="month"><?= __('Last Month') ?></option>
                        <option value="prevmonth"><?= __('Previous Month') ?></option>
                    </select>
                    <div class="js-completed-assessments"></div>
                </div>
            </div>

            <div class="grid-row">
                <div class="col-12">
                    <div class="js-delivery-list"></div>
                </div>
            </div>
        </div>

        <div class="col-3">
            <div class="grid-row">
                <h2><?= __('Actions') ?></h2>
            </div>
            <div class="js-pause-active-executions-container" data-reasonCategories="<?= _dh(json_encode($reason_categories)) ?>">
                <button class="js-pause btn-warning" type="button"><?= __('Pause Active Test Sessions') ?></button>
            </div>
        </div>

    </div>

</div>