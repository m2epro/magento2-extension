<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\MigrationFromMagento1\PreconditionsChecker;

use Ess\M2ePro\Model\Wizard\MigrationFromMagento1;

/**
 * Class \Ess\M2ePro\Setup\MigrationFromMagento1\PreconditionsChecker\Factory
 */
class Factory
{
    /** @var \Magento\Framework\ObjectManagerInterface */
    protected $objectManager;

    //########################################

    public function __construct(\Magento\Framework\ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    //########################################

    /**
     * @param $wizardStatus
     * @return AbstractModel
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function create($wizardStatus)
    {
        switch ($wizardStatus) {
            case MigrationFromMagento1::STATUS_PREPARED:
                return $this->objectManager->create(Prepared::class);

            case MigrationFromMagento1::STATUS_UNEXPECTEDLY_COPIED:
                return $this->objectManager->create(UnexpectedlyCopied::class);

            default:
                throw new \Ess\M2ePro\Model\Exception\Logic('Incorrect wizard status.');
        }
    }

    //########################################
}
