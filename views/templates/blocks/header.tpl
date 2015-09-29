<?php
use oat\tao\helpers\Template;
use oat\tao\helpers\Layout;
use oat\tao\model\theme\Theme;

$releaseMsgData = Layout::getReleaseMsgData();
?><!doctype html>
<html class="no-js no-version-warning" lang="<?=tao_helpers_I18n::getLangCode()?>">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= Layout::getTitle() ?></title>
<?= tao_helpers_Scriptloader::render() ?>
    <link rel="stylesheet" href="<?= Template::css('proctoring.css', 'taoProctoring') ?>"/>
    <link rel="stylesheet" href="<?= Layout::getThemeStylesheet(Theme::CONTEXT_FRONTOFFICE) ?>" />
    <link rel="shortcut icon" href="<?= Template::img('img/favicon.ico') ?>"/>
    <script src="<?= Template::js('lib/modernizr-2.8/modernizr.js', 'tao') ?>"></script>
    <?= Layout::getAmdLoader() ?>

</head>
<body class="proctoring-scope">
<!-- content wrap -->
<div class="content-wrap">
    <header class="dark-bar clearfix">
        <a href="<?= $releaseMsgData['link'] ?>" title="<?=$releaseMsgData['msg'] ?>" class="lft" target="_blank">
            <img src="<?= $releaseMsgData['logo']?>" alt="TAO Logo" id="tao-main-logo">
        </a>
        <div class="lft title-box"></div>
        <nav class="rgt">
            <!-- snippet: dark bar left menu -->

            <div class="settings-menu">
                <!-- Hamburger -->
                <span class="reduced-menu-trigger">
                    <span class="icon-mobile-menu"></span>
                    <?= __('More')?>
                </span>

                <ul class="clearfix plain">
                    <li data-control="home">
                        <a id="home" href="<?=_url('index', 'TaoProctoring')?>">
                            <span class="icon-home"></span>
                        </a>
                    </li>
                    <li class="infoControl sep-before">
                        <span class="a">
                            <span class="icon-user"></span>
                            <span><?= get_data('userLabel'); ?></span>
                        </span>
                    </li>
                    <li class="infoControl sep-before" data-control="logout">
                        <a id="logout" class="" href="<?=_url('logout', 'TaoProctoring')?>">
                            <span class="icon-logout"></span>
                            <span class="text"><?= __("Logout"); ?></span>
                        </a>
                    </li>
                </ul>
            </div>
        </nav>
    </header>
    <div id="feedback-box"></div>
</div>