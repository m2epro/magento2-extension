<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Wizard\MigrationFromMagento1;

use Ess\M2ePro\Controller\Adminhtml\Wizard\MigrationFromMagento1;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Wizard\MigrationFromMagento1\Synchronization
 */
class Synchronization extends MigrationFromMagento1
{
    public function execute()
    {
        $marketplaceCollection = $this->activeRecordFactory->getObject('Marketplace')
            ->getCollection()
            ->addFieldToFilter('status', \Ess\M2ePro\Model\Marketplace::STATUS_ENABLE)
            ->setOrder('sorder', 'ASC')
            ->setOrder('title', 'ASC');

        if (!$marketplaceCollection->count()) {
            $this->setStep($this->getNextStep());
        } else {
            $this->getHelper('Data\GlobalData')->setValue('marketplaces', $marketplaceCollection->getItems());
        }

        $this->init();

        return $this->renderSimpleStep();
    }
}
