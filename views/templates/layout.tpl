<?php
use oat\tao\helpers\Template;

print Template::inc('blocks/header.tpl');

print Template::inc('blocks/breadcrumbs.tpl');

print Template::inc(get_data('template'));

print Template::inc('blocks/footer.tpl');
