<?php
use oat\tao\helpers\Template;
?>
<div class="section-container">
    <div class="clear content-wrapper content-panel">
        <section class="content-container awaiting-authorization authorization-in-progress">
        </section>
    </div>
</div>
<link rel="stylesheet" href="<?= Template::css('deliveryServer.css', 'taoProctoring') ?>"/>
<script src="<?=Template::js('lib/require.js', 'tao')?>"></script>
<script>
    (function (){
        require(['<?=get_data('client_config_url')?>'], function (){
            require(['taoProctoring/controller/DeliveryServer/awaiting'], function (awaiting){
                awaiting.start({
                    returnUrl : '<?=get_data('returnUrl')?>',
                    cancelUrl : '<?=get_data('cancelUrl')?>',
                    cancelable : '<?=get_data('cancelable')?>',
                    deliveryExecution : '<?=get_data('deliveryExecution')?>',
                    deliveryLabel : '<?=get_data('deliveryLabel')?>',
                    runDeliveryUrl : '<?=get_data('runDeliveryUrl')?>'
                });
            });
        });
    }());
</script>
