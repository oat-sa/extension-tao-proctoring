<?php
use oat\tao\helpers\Template;
use oat\tao\helpers\Layout;
?>
<div class="section-container">
    <div class="clear content-wrapper content-panel">
        <section class="content-container awaiting-authorization authorization-in-progress">
        </section>
    </div>
</div>
<link rel="stylesheet" href="<?= Template::css('deliveryServer.css', 'taoProctoring') ?>"/>
<?= Layout::getAmdLoader(
       Template::js('loader/taoProctoring.min.js', 'taoProctoring'),
        'taoProctoring/controller/DeliveryServer/awaiting',
        [
            'returnUrl'         => get_data('returnUrl'),
            'cancelUrl'         => get_data('cancelUrl'),
            'cancelable'        => get_data('cancelable'),
            'deliveryExecution' => get_data('deliveryExecution'),
            'deliveryLabel'     => get_data('deliveryLabel'),
            'runDeliveryUrl'    => get_data('runDeliveryUrl')
        ]
    )?>
