<?php
use oat\tao\helpers\Template;
use oat\tao\helpers\Layout;
use oat\tao\model\theme\Theme;

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
        <?php Template::inc(get_data('content-template')); ?>
    </body>
</html>
