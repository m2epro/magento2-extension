<?php

declare(strict_types=1);

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Listing\Product\ProductType;

class Unassign extends \Ess\M2ePro\Controller\Adminhtml\Walmart\Main
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

        if (empty($productsIds)) {
            $this->setAjaxContent('You should provide correct parameters.', false);

            return $this->getResult();
        }

        if (!is_array($productsIds)) {
            $productsIds = explode(',', $productsIds);
        }

        $messages = [];

        $nonLockedProductsIds = $this->productTypeRelationService->extractIdsOfNonLockedProducts($productsIds);
        if (count($nonLockedProductsIds) < count($productsIds)) {
            $messages[] = [
                'type' => 'warning',
                'text' => '<p>' . __('Product Type cannot be unassigned because the Products are in Action.') . '</p>',
            ];
        }

        if (!empty($nonLockedProductsIds)) {
            $this->productTypeRelationService->unassignProductType($nonLockedProductsIds);

            $messages[] = [
                'type' => 'success',
                'text' => __('Product Type was unassigned.'),
            ];
        }

        $this->setJsonContent(['messages' => $messages]);

        return $this->getResult();
    }
}
