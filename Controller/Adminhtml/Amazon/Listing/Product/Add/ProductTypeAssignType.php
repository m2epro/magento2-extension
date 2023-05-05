<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Add;

class ProductTypeAssignType extends \Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Add
{
    //########################################

    /** @var \Ess\M2ePro\Model\Amazon\Template\ProductTypeFactory */
    private $productTypeFactory;
    /** @var \Ess\M2ePro\Model\ResourceModel\Amazon\Template\ProductType */
    private $productTypeResource;

    /**
     * @param \Ess\M2ePro\Helper\Component\Amazon\Variation $variationHelper
     * @param \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory
     * @param \Ess\M2ePro\Model\Amazon\Template\ProductTypeFactory $productTypeFactory
     * @param \Ess\M2ePro\Model\ResourceModel\Amazon\Template\ProductType $productTypeResource
     * @param \Ess\M2ePro\Controller\Adminhtml\Context $context
     */
    public function __construct(
        \Ess\M2ePro\Helper\Component\Amazon\Variation $variationHelper,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Model\Amazon\Template\ProductTypeFactory $productTypeFactory,
        \Ess\M2ePro\Model\ResourceModel\Amazon\Template\ProductType $productTypeResource,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($variationHelper, $amazonFactory, $context);
        $this->productTypeFactory = $productTypeFactory;
        $this->productTypeResource = $productTypeResource;
    }

    public function execute()
    {
        $listingId = $this->getRequest()->getParam('id');
        $listingProductsIds = $this->getRequest()->getParam('products_ids');

        $mode = $this->getRequest()->getParam('mode');
        $productTypeId = $this->getRequest()->getParam('product_type_id');

        if (empty($listingId) || empty($mode)) {
            $this->_forward('index');

            return;
        }

        if (!is_array($listingProductsIds)) {
            $listingProductsIds = explode(',', $listingProductsIds);
        }

        $listing = $this->amazonFactory->getObjectLoaded('Listing', $listingId);
        $listingAdditionalData = $listing->getData('additional_data');
        $listingAdditionalData = \Ess\M2ePro\Helper\Json::decode($listingAdditionalData);

        $listingAdditionalData['new_asin_mode'] = $mode;

        $listing->setData(
            'additional_data',
            \Ess\M2ePro\Helper\Json::encode($listingAdditionalData)
        )->save();

        if ($mode == 'same' && !empty($productTypeId)) {
            $productTypeTemplate = $this->productTypeFactory->create();
            $this->productTypeResource->load($productTypeTemplate, $productTypeId);

            if (!$productTypeTemplate->isEmpty()) {
                if (!empty($listingProductsIds)) {
                    $this->setProductTypeTemplate($listingProductsIds, $productTypeId);
                    $this->_forward('mapToNewAsin', 'amazon_listing_product');
                }

                return $this->_redirect('*/amazon_listing_product_add/index', [
                    '_current' => true,
                    'step' => 5,
                ]);
            }

            unset($listingAdditionalData['new_asin_mode']);

            $listing->setData(
                'additional_data',
                \Ess\M2ePro\Helper\Json::encode($listingAdditionalData)
            )->save();
        } elseif ($mode == 'category') {
            return $this->_redirect('*/*/productTypeAssignByMagentoCategory', [
                '_current' => true,
            ]);
        } elseif ($mode == 'manually') {
            return $this->_redirect('*/*/productTypeAssignManually', [
                '_current' => true,
            ]);
        }

        $this->_forward('index');
    }

    //########################################
}
