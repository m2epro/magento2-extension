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

        $synchronizationEbayOtherListingsProcessing = $this->activeRecordFactory->getObject('LockItem')
            ->getCollection()
            ->addFieldToFilter('nick', array('like' => 'synchronization_ebay_other_listings_update%'))
            ->getSize();

        // M2ePro_TRANSLATIONS
        // eBay 3rd Party Listings are being downloaded now. They will be available soon in %menu_root%. You can continue working with M2E Pro.
        if ($synchronizationEbayOtherListingsProcessing > 0) {
            $warningMessages[] = $this->__(
                'eBay 3rd Party Listings are being downloaded now. ' .
                'They will be available soon in %menu_root%. ' .
                'You can continue working with M2E Pro.'
//              todo  Mage::helper('M2ePro/View_Ebay')->getPageNavigationPath('listings', '3rd Party')
            );
        }

        $this->setAjaxContent(json_encode(array(
            'messages' => $warningMessages
        ), false));

        return $this->getResponse();
    }

    //########################################
}