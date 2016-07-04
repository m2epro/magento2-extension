<?php

namespace Ess\M2ePro\Controller\Adminhtml\Wizard\MigrationFromMagento1;

use Ess\M2ePro\Controller\Adminhtml\Wizard\MigrationFromMagento1;

class Synchronization extends MigrationFromMagento1
{
    public function execute()
    {
        $marketplaceCollection = $this->activeRecordFactory->getObject('Marketplace')
            ->getCollection()
            ->addFieldToFilter('status', \Ess\M2ePro\Model\Marketplace::STATUS_ENABLE);

        if (!$marketplaceCollection->count()) {
            //todo
        }

        $this->getHelper('Data\GlobalData')->setValue('marketplaces', $marketplaceCollection->getItems());

        $this->init();

        return $this->renderSimpleStep();
    }
}