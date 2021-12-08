<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Listing\Product\Variation\Manage;

use Ess\M2ePro\Controller\Adminhtml\Walmart\Main;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Walmart\Listing\Product\Variation\Manage\CreateNewChild
 */
class CreateNewChild extends Main
{
    public function execute()
    {
        $productId = $this->getRequest()->getParam('product_id');
        $newChildProductData = $this->getRequest()->getParam('new_child_product');

        if (empty($productId) || empty($newChildProductData)) {
            $this->setAjaxContent('You should provide correct parameters.', false);

            return $this->getResult();
        }

        /** @var \Ess\M2ePro\Model\Listing\Product $parentListingProduct */
        $parentListingProduct = $this->walmartFactory->getObjectLoaded('Listing\Product', $productId);

        /** @var \Ess\M2ePro\Model\Walmart\Listing\Product $parentWalmartListingProduct */
        $parentWalmartListingProduct = $parentListingProduct->getChildObject();

        $parentTypeModel = $parentWalmartListingProduct->getVariationManager()->getTypeModel();

        $productOptions = array_combine(
            $newChildProductData['product']['attributes'],
            $newChildProductData['product']['options']
        );

        if ($parentTypeModel->isProductsOptionsRemoved($productOptions)) {
            $parentTypeModel->restoreRemovedProductOptions($productOptions);
        }

        /** @var \Ess\M2ePro\Model\Listing\Product $childListingProduct */
        $childListingProduct = $parentTypeModel->createChildListingProduct($productOptions, []);

        $addedProductOptions = $childListingProduct->getChildObject()->getVariationManager()
            ->getTypeModel()->getProductOptions();

        // Don't use $childListingProduct anymore, because it might be removed after calling the following method
        $parentTypeModel->getProcessor()->process();

        $isProductOptionWasAdded = false;
        foreach ($addedProductOptions as $addedProductOption) {
            if ($productOptions == $addedProductOption) {
                $isProductOptionWasAdded = true;
            }
        }

        if (!$isProductOptionWasAdded) {
            $parentListingProduct->logProductMessage(
                'New Child Product cannot be created. There is no correspondence between the Magento Attribute
                 value of a new Child Product and available Magento Attribute values of the Parent Product.',
                \Ess\M2ePro\Helper\Data::INITIATOR_USER,
                \Ess\M2ePro\Model\Listing\Log::ACTION_ADD_NEW_CHILD_LISTING_PRODUCT,
                \Ess\M2ePro\Model\Log\AbstractModel::TYPE_ERROR
            );

            $message = $this->__(
                'New Child Product was not created.
 Please view <a target="_blank" href="%url%">Listing Logs</a> for details.',
                $this->getUrl(
                    '*/walmart_log_listing_product/index',
                    ['listing_product_id' => $parentListingProduct->getId()]
                )
            );

            $this->setJsonContent([
                'type' => 'error',
                'msg'  => $message
            ]);
        } else {
            $this->setJsonContent([
                'type' => 'success',
                'msg'  => $this->__('New Walmart Child Product was created.')
            ]);
        }

        return $this->getResult();
    }
}
