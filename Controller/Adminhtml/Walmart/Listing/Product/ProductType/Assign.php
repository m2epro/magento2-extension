<?php

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Listing\Product\ProductType;

use Ess\M2ePro\Model\ResourceModel\Walmart\Listing\Product as ProductResource;

class Assign extends \Ess\M2ePro\Controller\Adminhtml\Walmart\Main
{
    private \Ess\M2ePro\Model\Walmart\Listing\Product\ProductTypeRelationService $productTypeRelationService;

    public function __construct(
        \Ess\M2ePro\Model\Walmart\Listing\Product\ProductTypeRelationService $productTypeRelationService,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($walmartFactory, $context);

        $this->productTypeRelationService = $productTypeRelationService;
    }

    public function execute()
    {
        $productsIds = $this->getRequest()->getParam('products_ids');
        $productTypeId = $this->getRequest()->getParam('template_id');

        if (empty($productsIds) || empty($productTypeId)) {
            $this->setAjaxContent('You should provide correct parameters.', false);

            return $this->getResult();
        }

        if (!is_array($productsIds)) {
            $productsIds = explode(',', $productsIds);
        }

        $this->productTypeRelationService->assignProductType($productTypeId, $productsIds);

        $this->setJsonContent([
            'type' => 'success',
            'messages' => [
                __('Product Type was assigned to %count Products', ['count' => count($productsIds)]),
            ],
            'products_ids' => implode(',', $productsIds),
        ]);

        return $this->getResult();
    }
}
