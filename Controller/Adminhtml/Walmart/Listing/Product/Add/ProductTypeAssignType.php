<?php

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Listing\Product\Add;

class ProductTypeAssignType extends \Ess\M2ePro\Controller\Adminhtml\Walmart\Listing\Product\AbstractAdd
{
    private \Ess\M2ePro\Model\Walmart\ProductType\Repository $productTypeRepository;

    public function __construct(
        \Ess\M2ePro\Model\Walmart\ProductType\Repository $productTypeRepository,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($walmartFactory, $context);
        $this->productTypeRepository = $productTypeRepository;
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

        $listing = $this->walmartFactory->getObjectLoaded('Listing', $listingId);
        $listingAdditionalData = $listing->getData('additional_data');
        $listingAdditionalData = \Ess\M2ePro\Helper\Json::decode($listingAdditionalData);

        $listingAdditionalData['product_type_mode'] = $mode;

        $listing->setData(
            'additional_data',
            \Ess\M2ePro\Helper\Json::encode($listingAdditionalData)
        )->save();

        if ($mode == 'same' && !empty($productTypeId)) {
            $productType = $this->productTypeRepository->find((int)$productTypeId);

            if ($productType !== null) {
                if (!empty($listingProductsIds)) {
                    $this->setProductType($listingProductsIds, $productTypeId);
                }

                return $this->_redirect('*/walmart_listing_product_add/index', [
                    '_current' => true,
                    'step' => 4,
                ]);
            }
            unset($listingAdditionalData['product_type_mode']);

            $listing->setData(
                'additional_data',
                \Ess\M2ePro\Helper\Json::encode($listingAdditionalData)
            )->save();
        }

        if ($mode == 'category') {
            return $this->_redirect('*/*/productTypeAssignByMagentoCategory', [
                '_current' => true,
            ]);
        }

        if ($mode == 'manually') {
            return $this->_redirect('*/*/productTypeAssignManually', [
                '_current' => true,
            ]);
        }

        $this->_forward('index');
    }
}
