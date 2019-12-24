<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Synchronization;

use Ess\M2ePro\Controller\Adminhtml\Ebay\Settings;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Ebay\Synchronization\SynchCheckProcessingNow
 */
class SynchCheckProcessingNow extends Settings
{
    //########################################

    public function execute()
    {
        $warningMessages = [];

        $synchronizationEbayOtherListingsProcessing = $this->activeRecordFactory->getObject('Lock\Item')
            ->getCollection()
            ->addFieldToFilter('nick', ['like' => 'synchronization_ebay_other_listings_update%'])
            ->getSize();

        // M2ePro_TRANSLATIONS
        // eBay 3rd Party Listings are being downloaded now. You can continue working with M2E Pro.
        if ($synchronizationEbayOtherListingsProcessing > 0) {
            $warningMessages[] = $this->__(
                'eBay 3rd Party Listings are being downloaded now. ' .
                'You can continue working with M2E Pro.'
            );
        }

        $this->setJsonContent([
            'messages' => $warningMessages
        ]);

        return $this->getResponse();
    }

    //########################################
}
