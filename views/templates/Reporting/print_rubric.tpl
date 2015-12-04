<?php
use oat\tao\helpers\Template;
use oat\tao\helpers\Layout;
use oat\tao\model\theme\Theme;
use tao_helpers_Date as DateHelper;

$rubrics = get_data("rubrics");
?>
<!doctype html>
<html class="no-js no-version-warning" lang="<?= tao_helpers_I18n::getLangCode() ?>">
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
            <?php foreach($rubrics as $rubricNum => $rubric): ?>
                <?php if ($rubricNum > 0): ?>
                    <div class="end-page"></div>
                <?php endif; ?>
                <header>
                    <h1>
                        <?= $rubric['testData']['Label'] ?> <?=__('results')?>
                    </h1>
                    <h2>
                        <?= DateHelper::displayeDate($rubric['deliveryData']['start'], DateHelper::FORMAT_LONG); ?> - <?= DateHelper::displayeDate($rubric['deliveryData']['end'], DateHelper::FORMAT_LONG); ?>
                    </h2>
                </header>
                <hr>
                <div>
                    <p class="title">
                        <?=__('Test taker')?>
                    </p>
                    <div class="avoid-page-break table-container clearfix">
                        <table>
                            <?php foreach($rubric['testTakerData'] as $key => $val): ?>
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
                        <?= __('Score Report') ?>
                    </p>
                    <div class="clearfix rubric-container">
                        <?php foreach($rubric['rubricContent'] as $rubricContent): ?>
                            <?= $rubricContent ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <script>
            window.print();
        </script>
    </body>
</html>
