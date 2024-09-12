<?php

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Marketplace;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Marketplace;

/** deprecated for migration MigrationToInnodb */
class SynchGetExecutingInfo extends Marketplace
{
    public function execute()
    {
        $this->setJsonContent(['mode' => 'done']);

        return $this->getResult();
    }
}
