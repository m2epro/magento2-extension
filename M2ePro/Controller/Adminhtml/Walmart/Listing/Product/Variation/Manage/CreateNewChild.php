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

        $parentTypeModel->createChildListingProduct($productOptions, []);
        $parentTypeModel->getProcessor()->process();

        $this->setJsonContent([
            'type' => 'success',
            'msg'  => $this->__('New Walmart Child Product was successfully created.')
        ]);

        return $this->getResult();
    }
}
