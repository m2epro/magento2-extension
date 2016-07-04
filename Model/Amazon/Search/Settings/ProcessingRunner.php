<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Search\Settings;

class ProcessingRunner extends \Ess\M2ePro\Model\Connector\Command\Pending\Processing\Runner\Single
{
    /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */
    private $listingProduct = NULL;

    //########################################

    protected function eventBefore()
    {
        parent::eventBefore();

        $params = $this->getParams();

        $amazonListingProduct = $this->getListingProduct()->getChildObject();

        $amazonListingProduct->setData(
            'search_settings_status', \Ess\M2ePro\Model\Amazon\Listing\Product::SEARCH_SETTINGS_STATUS_IN_PROGRESS
        );
        $amazonListingProduct->setSettings(
            'search_settings_data', array('type' => $params['type'], 'value' => $params['value'])
        );
        $amazonListingProduct->save();
    }

    protected function setLocks()
    {
        parent::setLocks();

        $this->getListingProduct()->addProcessingLock(NULL, $this->getId());
        $this->getListingProduct()->addProcessingLock('in_action', $this->getId());
        $this->getListingProduct()->addProcessingLock('search_action', $this->getId());

        $this->getListingProduct()->getListing()->addProcessingLock(NULL, $this->getId());
    }

    protected function unsetLocks()
    {
        parent::unsetLocks();

        $this->getListingProduct()->deleteProcessingLocks(NULL, $this->getId());
        $this->getListingProduct()->getListing()->deleteProcessingLocks(NULL, $this->getId());
    }

    //########################################

    private function getListingProduct()
    {
        if (!is_null($this->listingProduct)) {
            return $this->listingProduct;
        }

        $params = $this->getParams();

        $this->parentFactory->getObjectLoaded(
            \Ess\M2ePro\Helper\Component\Amazon::NICK,
            'Listing\Product', $params['listing_product_id']
        );

        return $this->listingProduct;
    }

    //########################################
}