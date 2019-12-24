<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Search\Settings;

/**
 * Class \Ess\M2ePro\Model\Amazon\Search\Settings\ProcessingRunner
 */
class ProcessingRunner extends \Ess\M2ePro\Model\Connector\Command\Pending\Processing\Runner\Single
{
    /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */
    private $listingProduct = null;

    //########################################

    protected function eventBefore()
    {
        parent::eventBefore();

        $params = $this->getParams();

        $amazonListingProduct = $this->getListingProduct()->getChildObject();

        $amazonListingProduct->setData(
            'search_settings_status',
            \Ess\M2ePro\Model\Amazon\Listing\Product::SEARCH_SETTINGS_STATUS_IN_PROGRESS
        );
        $amazonListingProduct->setSettings(
            'search_settings_data',
            ['type' => $params['type'], 'value' => $params['value']]
        );
        $amazonListingProduct->save();
    }

    protected function setLocks()
    {
        parent::setLocks();

        $this->getListingProduct()->addProcessingLock(null, $this->getId());
        $this->getListingProduct()->addProcessingLock('in_action', $this->getId());
        $this->getListingProduct()->addProcessingLock('search_action', $this->getId());

        $this->getListingProduct()->getListing()->addProcessingLock(null, $this->getId());
    }

    protected function unsetLocks()
    {
        parent::unsetLocks();

        $this->getListingProduct()->deleteProcessingLocks(null, $this->getId());
        $this->getListingProduct()->getListing()->deleteProcessingLocks(null, $this->getId());
    }

    //########################################

    private function getListingProduct()
    {
        if ($this->listingProduct !== null) {
            return $this->listingProduct;
        }

        $params = $this->getParams();

        $this->parentFactory->getObjectLoaded(
            \Ess\M2ePro\Helper\Component\Amazon::NICK,
            'Listing\Product',
            $params['listing_product_id']
        );

        return $this->listingProduct;
    }

    //########################################
}
