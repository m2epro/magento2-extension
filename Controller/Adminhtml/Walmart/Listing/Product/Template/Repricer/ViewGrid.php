<?php

declare(strict_types=1);

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Listing\Product\Template\Repricer;

class ViewGrid extends \Ess\M2ePro\Controller\Adminhtml\Walmart\Main
{
    private \Ess\M2ePro\Model\ResourceModel\Listing\Product\CollectionFactory $listingProductCollectionFactory;

    public function __construct(
        \Ess\M2ePro\Model\ResourceModel\Listing\Product\CollectionFactory $listingProductCollectionFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($walmartFactory, $context);
        $this->listingProductCollectionFactory = $listingProductCollectionFactory;
    }

    public function execute()
    {
        $productsIds = $this->getRequest()->getParam('products_ids');
        $accountId = $this->getRequest()->getParam('account_id');

        if (empty($productsIds)) {
            $this->setRawContent('You should provide correct parameters.');

            return $this->getResult();
        }

        if (!is_array($productsIds)) {
            $productsIds = explode(',', $productsIds);
        }

        if (empty($accountId)) {
            $firstProductId = (int)reset($productsIds);
            $accountId = $this->getAccountIdFromProduct($firstProductId);
        }

        $grid = $this
            ->getLayout()
            ->createBlock(
                \Ess\M2ePro\Block\Adminhtml\Walmart\Listing\Product\Template\Repricer\Grid::class,
                '',
                [
                    'accountId' => $accountId,
                    'productsIds' => $productsIds,
                ]
            );

        $this->setRawContent($grid->getHtml());

        return $this->getResult();
    }

    private function getAccountIdFromProduct(int $productId): int
    {
        $collection = $this->listingProductCollectionFactory->createWithWalmartChildMode();
        $collection->addFieldToFilter(
            'id',
            ['eq' => $productId]
        );

        return $collection->getFirstItem()->getListing()->getAccountId();
    }
}
