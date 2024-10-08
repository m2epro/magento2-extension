<?php

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Listing\Product\Add;

class ProductTypeAssignManually extends \Ess\M2ePro\Controller\Adminhtml\Walmart\Listing\Product\AbstractAdd
{
    private \Ess\M2ePro\Helper\Data\GlobalData $globalData;

    public function __construct(
        \Ess\M2ePro\Helper\Data\GlobalData $globalData,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($walmartFactory, $context);

        $this->globalData = $globalData;
    }

    public function execute()
    {
        $listingProductsIds = $this->getListing()->getSetting('additional_data', 'adding_listing_products_ids');

        if (empty($listingProductsIds)) {
            $this->_forward('index');

            return;
        }

        $listing = $this->getListing();

        $this->globalData->setValue('listing_for_products_add', $listing);

        if ($this->getRequest()->isXmlHttpRequest()) {
            $grid = $this->getLayout()
                         ->createBlock(
                             \Ess\M2ePro\Block\Adminhtml\Walmart\Listing\Product\Add\ProductType\Manual\Grid::class
                         );

            $this->setAjaxContent($grid);

            return $this->getResult();
        }

        $this->setPageHelpLink('walmart-integration');
        $this->getResultPage()->getConfig()->getTitle()->prepend(
            $this->__('Set Product Type')
        );

        $this->addContent(
            $this->getLayout()
                 ->createBlock(\Ess\M2ePro\Block\Adminhtml\Walmart\Listing\Product\Add\ProductType\Manual::class)
        );

        return $this->getResult();
    }
}
