<?php
use oat\taoProctoring\model\authorization\TestTakerAuthorizationService;

return new TestTakerAuthorizationService(
    [TestTakerAuthorizationService::PROCTORED_BY_DEFAULT => false]
);