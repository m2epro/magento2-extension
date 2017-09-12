<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Synchronization;

use Ess\M2ePro\Controller\Adminhtml\Ebay\Settings;

class SynchCheckProcessingNow extends Settings
{
    //########################################

    public function execute()
    {
        $warningMessages = array();

        $synchronizationEbayOtherListingsProcessing = $this->activeRecordFactory->getObject('Lock\Item')
            ->getCollection()
            ->addFieldToFilter('nick', array('like' => 'synchronization_ebay_other_listings_update%'))
            ->getSize();

        // M2ePro_TRANSLATIONS
        // eBay 3rd Party Listings are being downloaded now. You can continue working with M2E Pro.
        if ($synchronizationEbayOtherListingsProcessing > 0) {
            $warningMessages[] = $this->__(
                'eBay 3rd Party Listings are being downloaded now. ' .
                'You can continue working with M2E Pro.'
            );
        }

        $this->setJsonContent(array(
            'messages' => $warningMessages
        ));

        return $this->getResponse();
    }

    //########################################
}