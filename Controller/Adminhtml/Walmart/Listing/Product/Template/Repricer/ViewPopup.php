<?php

declare(strict_types=1);

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Listing\Product\Template\Repricer;

class ViewPopup extends \Ess\M2ePro\Controller\Adminhtml\Walmart\Listing\Product\Template\Repricer
{
    private \Ess\M2ePro\Model\Walmart\Template\Repricer\FilterLockedProduct $filterLockedProduct;

    public function __construct(
        \Ess\M2ePro\Model\Walmart\Template\Repricer\FilterLockedProduct $filterLockedProduct,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($walmartFactory, $context);
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
        $unlockedProductIds = $this->filterLockedProduct->execute($productsIds);

        if (count($unlockedProductIds) < count($productsIds)) {
            $messages[] = [
                'type' => 'warning',
                'text' => __('The Repricer Policy was not assigned because the Products have In Action Status.'),
            ];
        }

        if (empty($unlockedProductIds)) {
            $this->setJsonContent([
                'messages' => $messages,
            ]);

            return $this->getResult();
        }

        $mainBlock = $this
            ->getLayout()
            ->createBlock(\Ess\M2ePro\Block\Adminhtml\Walmart\Listing\Product\Template\Repricer::class);

        if (!empty($messages)) {
            $mainBlock->setMessages($messages);
        }

        $this->setJsonContent([
            'html' => $mainBlock->toHtml(),
            'messages' => $messages,
            'products_ids' => implode(',', $unlockedProductIds),
        ]);

        return $this->getResult();
    }
}
