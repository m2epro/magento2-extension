<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Account\PickupStore;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Ebay\Account\PickupStore\GetRegions
 */
class GetRegions extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Account
{
    /** @var \Ess\M2ePro\Helper\Magento */
    private $helperMagento;

    public function __construct(
        \Ess\M2ePro\Helper\Magento $helperMagento,
        \Ess\M2ePro\Model\Ebay\Account\Store\Category\Update $storeCategoryUpdate,
        \Ess\M2ePro\Helper\Component\Ebay\Category\Store $componentEbayCategoryStore,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($storeCategoryUpdate, $componentEbayCategoryStore, $ebayFactory, $context);

        $this->helperMagento = $helperMagento;
    }

    //########################################

    public function execute()
    {
        $regions = [];

        if ($countryCode = $this->getRequest()->getParam('country_code')) {
            $regions = $this->helperMagento->getRegionsByCountryCode($countryCode);
        }

        $this->setJsonContent($regions);

        return $this->getResult();
    }

    //########################################
}
