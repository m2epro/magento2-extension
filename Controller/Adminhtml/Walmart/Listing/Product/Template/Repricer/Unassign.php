<?php

declare(strict_types=1);

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Listing\Product\Template\Repricer;

class Unassign extends \Ess\M2ePro\Controller\Adminhtml\Walmart\Listing\Product\Template\Repricer
{
    private \Ess\M2ePro\Model\Walmart\Listing\Product\SetRepricerTemplateId $setRepricerTemplateId;
    private \Ess\M2ePro\Model\Walmart\Template\Repricer\FilterLockedProduct $filterLockedProduct;

    public function __construct(
        \Ess\M2ePro\Model\Walmart\Listing\Product\SetRepricerTemplateId $setRepricerTemplateId,
        \Ess\M2ePro\Model\Walmart\Template\Repricer\FilterLockedProduct $filterLockedProduct,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($walmartFactory, $context);
        $this->setRepricerTemplateId = $setRepricerTemplateId;
        $this->filterLockedProduct = $filterLockedProduct;
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
        $unlockerProductIds = $this->filterLockedProduct->execute($productsIds);

        if (count($unlockerProductIds) < count($productsIds)) {
            $messages[] = [
                'type' => 'warning',
                'text' => __('Repricer Policy cannot be unassigned from some Products because the Products are in Action'),
            ];
        }

        if (!empty($unlockerProductIds)) {
            $messages[] = [
                'type' => 'success',
                'text' => __('Repricer Policy was unassigned.'),
            ];

            $this->setRepricerTemplateId->execute($unlockerProductIds, null);
        }

        $this->setJsonContent(['messages' => $messages]);

        return $this->getResult();
    }
}
