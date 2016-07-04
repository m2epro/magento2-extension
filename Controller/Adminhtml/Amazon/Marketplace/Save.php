<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Marketplace;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Marketplace;

class Save extends Marketplace
{
    //########################################

    public function execute()
    {
        $marketplaces = $this->activeRecordFactory->getObject('Marketplace')->getCollection();

        foreach ($marketplaces as $marketplace) {
            $newStatus = $this->getRequest()->getParam('status_'.$marketplace->getId());

            if (is_null($newStatus)) {
                continue;
            }
            if ($marketplace->getStatus() == $newStatus) {
                continue;
            }
            $marketplace->setData('status', $newStatus)->save();
        }
    }

    //########################################
}