<?php
use oat\tao\helpers\Template;
use oat\tao\helpers\Layout;
use oat\tao\model\theme\Theme;
use tao_helpers_Date as DateHelper;

$reports = get_data("reports");
?>
<!doctype html>
<html class="no-version-warning" lang="<?= tao_helpers_I18n::getLangCode() ?>">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title><?= Layout::getTitle() ?></title>
        <link rel="stylesheet" href="<?= Template::css('tao-main-style.css', 'tao') ?>" />
        <link rel="stylesheet" href="<?= Template::css('tao-3.css', 'tao') ?>" />
        <link rel="stylesheet" href="<?= Template::css('proctoring.css', 'taoProctoring') ?>"/>
        <link rel="stylesheet" href="<?= Template::css('printReport.css', 'taoProctoring') ?>"/>
        <link rel="stylesheet" href="<?= Layout::getThemeStylesheet(Theme::CONTEXT_FRONTOFFICE) ?>" />
        <link rel="shortcut icon" href="<?= Template::img('favicon.ico', 'tao') ?>"/>
    </head>
    <body class="proctoring-scope">
        <div class="assessment_results">
            <?php foreach($reports as $report): ?>
                <header>
                    <?php if (isset($report['testData']['Label']) && !empty($report['testData']['Label'])): ?>
                    <h1>
                        <?= $report['testData']['Label'] ?> <?=__('results')?>
                    </h1>
                    <?php endif; ?>
                    <h2>
                        <?= DateHelper::displayeDate($report['deliveryData']['start'], DateHelper::FORMAT_LONG); ?> - <?= DateHelper::displayeDate($report['deliveryData']['end'], DateHelper::FORMAT_LONG); ?>
                    </h2>
                </header>
                <hr>
                <div>
                    <p class="title">
                        <?=__('Test taker')?>
                    </p>
                    <div class="avoid-page-break table-container clearfix">
                        <table>
                            <?php foreach($report['testTakerData'] as $key => $val): ?>
                            <tr>
                                <td><?= $key ?></td>
                                <td><?= $val ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </table>
                    </div>
                </div>
                <hr>
                <div>
                    <p class="title">
                        <?= __('Test Variables') ?>
                    </p>
                    <div class="avoid-page-break table-container clearfix">
                        <table>
                            <?php if (isset($report['testData']['Label']) && !empty($report['testData']['Label'])): ?>
                            <tr>
                                <td><?= __('Label') ?></td>
                                <td><?= $report['testData']['Label'] ?></td>
                            </tr>
                            <?php endif; ?>
                            <tr>
                                <td><?= __('Outcome') ?></td>
                                <td><?= $report['testData']['LtiOutcome'] ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
                <hr>
                <div>
                    <p class="title">
                        <?= __('Item Variables') ?>
                    </p>
                    <?php foreach($report['resultsData'] as $itemResult): ?>
                        <p class="table-title"><?= $itemResult['label'] ?></p>
                        <div class="avoid-page-break table-container clearfix">
                            <table>
                                <?php if (isset($itemResult['duration'])): ?>
                                <tr>
                                    <td><?= __('Duration') ?></td>
                                    <td><?= $itemResult['duration'] ?></td>
                                </tr>
                                <?php endif; ?>
                                <?php if (isset($itemResult['completionStatus'])): ?>
                                <tr>
                                    <td><?= __('Status') ?></td>
                                    <td><?= $itemResult['completionStatus'] ?></td>
                                </tr>
                                <?php endif; ?>
                                <?php if (isset($itemResult['numAttempts'])): ?>
                                <tr>
                                    <td><?= __('Attempts') ?></td>
                                    <td><?= $itemResult['numAttempts'] ?></td>
                                </tr>
                                <?php endif; ?>
                                <?php if (isset($itemResult['SCORE'])): ?>
                                <tr>
                                    <td><?= __('Score') ?></td>
                                    <td><?= $itemResult['SCORE'] ?></td>
                                </tr>
                                <?php endif; ?>
                            </table>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="end-page"></div>
            <?php endforeach; ?>
        </div>
        <script>
            window.print();
            setTimeout(window.close, 500);
        </script>
    </body>
</html>
