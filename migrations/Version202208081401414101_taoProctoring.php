<?php

declare(strict_types=1);

namespace oat\taoProctoring\migrations;

use Doctrine\DBAL\Schema\Schema;
use oat\tao\scripts\tools\migrations\AbstractMigration;
use oat\taoDelivery\model\execution\AbstractStateService;
use oat\taoDelivery\model\execution\StateServiceInterface;
use oat\taoProctoring\model\implementation\DeliveryExecutionStateService;
use Pimple\Psr11\ServiceLocator;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version202208081401414101_taoProctoring extends AbstractMigration
{

    public function getDescription(): string
    {
        return 'Set proctor time modification strategy to "timer_adjustment"';
    }

    public function up(Schema $schema): void
    {
        /** @var StateServiceInterface|DeliveryExecutionStateService $stateService */
        $stateService = $this->getServiceLocator()->get(StateServiceInterface::SERVICE_ID);
        $stateService->setOption(
            DeliveryExecutionStateService::OPTION_TIME_HANDLING,
            DeliveryExecutionStateService::TIME_HANDLING_TIMER_ADJUSTMENT
        );
        $this->getServiceLocator()->register(StateServiceInterface::SERVICE_ID, $stateService);
    }

    public function down(Schema $schema): void
    {
        /** @var StateServiceInterface|DeliveryExecutionStateService $stateService */
        $stateService = $this->getServiceLocator()->get(StateServiceInterface::SERVICE_ID);
        $stateService->setOption(
            DeliveryExecutionStateService::OPTION_TIME_HANDLING,
            DeliveryExecutionStateService::TIME_HANDLING_EXTRA_TIME
        );
        $this->getServiceLocator()->register(StateServiceInterface::SERVICE_ID, $stateService);
    }
}
