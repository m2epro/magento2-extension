<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Search;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Main;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Search\SuggestedAsinGrid
 */
class SuggestedAsinGrid extends Main
{
    /** @var \Ess\M2ePro\Helper\Data\GlobalData */
    private $helperDataGlobalData;

    public function __construct(
        \Ess\M2ePro\Helper\Data\GlobalData $helperDataGlobalData,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($amazonFactory, $context);

        $this->helperDataGlobalData = $helperDataGlobalData;
    }

    public function execute()
    {
        $productId = $this->getRequest()->getParam('product_id');

        if (empty($productId)) {
            $this->setAjaxContent('ERROR: No Product ID!', false);

            return $this->getResult();
        }

        /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */
        $listingProduct = $this->amazonFactory->getObjectLoaded('Listing\Product', $productId);

        $marketplaceId = $listingProduct->getListing()->getMarketplaceId();

        $searchSettingsData = $listingProduct->getChildObject()->getSettings('search_settings_data');
        if (!empty($searchSettingsData['data'])) {
            $this->helperDataGlobalData->setValue('product_id', $productId);
            $this->helperDataGlobalData->setValue('marketplace_id', $marketplaceId);
            $this->helperDataGlobalData->setValue('search_data', $searchSettingsData);

            $this->setAjaxContent(
                $this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Product\Search\Grid::class)
            );
        } else {
            $this->setAjaxContent($this->__('NO DATA'), false);
        }

        return $this->getResult();
    }
}
