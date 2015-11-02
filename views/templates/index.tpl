<div class="container <?= get_data('cls'); ?>"<?php
    foreach(get_data('data') as $name => $value) {
        echo ' data-' . $name . '="' .(is_string($value) ? $value : _dh(json_encode($value))) . '"';
    }
?>>
    <div class="header"></div>
    <div class="content"></div>
</div>
