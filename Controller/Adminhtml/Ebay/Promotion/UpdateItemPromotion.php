<?php

declare(strict_types=1);

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Promotion;

class UpdateItemPromotion extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Main
{
    private const ACTION_TYPE_ADD = 'add';
    private const ACTION_TYPE_REPLACE = 'replace';

    private \Ess\M2ePro\Model\Ebay\Promotion\Update $promotionUpdate;
    private \Ess\M2ePro\Model\Ebay\PromotionFactory $promotionFactory;
    private \Ess\M2ePro\Model\ResourceModel\Listing\Product\CollectionFactory $listingProductCollectionFactory;
    private \Ess\M2ePro\Model\ResourceModel\Ebay\Item $ebayItemResource;
    private \Ess\M2ePro\Model\ResourceModel\Ebay\Listing\Product $ebayListingProductResource;

    public function __construct(
        \Ess\M2ePro\Model\Ebay\Promotion\Update $promotionUpdate,
        \Ess\M2ePro\Model\Ebay\PromotionFactory $promotionFactory,
        \Ess\M2ePro\Model\ResourceModel\Listing\Product\CollectionFactory $listingProductCollectionFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context,
        \Ess\M2ePro\Model\ResourceModel\Ebay\Item $ebayItemResource,
        \Ess\M2ePro\Model\ResourceModel\Ebay\Listing\Product $ebayListingProductResource
    ) {
        parent::__construct($ebayFactory, $context);

        $this->promotionUpdate = $promotionUpdate;
        $this->promotionFactory = $promotionFactory;
        $this->listingProductCollectionFactory = $listingProductCollectionFactory;
        $this->ebayItemResource = $ebayItemResource;
        $this->ebayListingProductResource = $ebayListingProductResource;
    }

    public function execute(): \Magento\Framework\App\ResponseInterface
    {
        $promotionId = (int)$this->getRequest()->getParam('promotion_id');
        $listingProductIdsString = $this->getRequest()->getParam('listing_product_ids');
        $action = $this->getRequest()->getParam('action');

        if (
            empty($promotionId)
            || empty($listingProductIdsString)
            || !in_array($action, [self::ACTION_TYPE_ADD, self::ACTION_TYPE_REPLACE])
        ) {
            $this->messageManager->addErrorMessage(__('You should provide correct parameters.'));

            return $this->_redirect($this->redirect->getRefererUrl());
        }

        $promotion = $this->promotionFactory->create()->load($promotionId);

        $listingProductIds = explode(',', $listingProductIdsString);

        $listingProducts = $this->filterListingProducts($listingProductIds);

        if (empty($listingProducts)) {
            $this->messageManager->addNoticeMessage(__('None of the selected products are eligible for discount.
            Please ensure that products are in ‘Listed’ status'));

            return $this->_redirect($this->redirect->getRefererUrl());
        }

        try {
            if ($action === self::ACTION_TYPE_ADD) {
                $this->promotionUpdate->add($promotion, $listingProducts);
            } else {
                $this->promotionUpdate->replace($promotion, $listingProducts);
            }

            $this->messageManager->addSuccessMessage(__('Discount was updated.'));
        } catch (\Exception $exception) {
            $this->messageManager->addErrorMessage(
                __(
                    "Discount was not updated. Reason: %reason.",
                    [
                        'reason' => $exception->getMessage(),
                    ]
                )
            );
        }

        return $this->_redirect($this->redirect->getRefererUrl());
    }

    /**
     * @param array $listingProductIds
     *
     * @return \Ess\M2ePro\Model\Listing\Product[]
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function filterListingProducts(array $listingProductIds): array
    {
        $collection = $this->listingProductCollectionFactory->createWithEbayChildMode();

        $collection->getSelect()->join(
            ['elp' => $this->ebayListingProductResource->getMainTable()],
            '`main_table`.`id` = `elp`.`listing_product_id`',
            []
        );
        $collection->getSelect()->join(
            ['ei' => $this->ebayItemResource->getMainTable()],
            '`elp`.`ebay_item_id` = `ei`.`id`',
            []
        );

        $collection->addFieldToFilter(
            'main_table.status',
            ['eq' => \Ess\M2ePro\Model\Listing\Product::STATUS_LISTED]
        );
        $collection->addFieldToFilter('main_table.id', ['in' => $listingProductIds]);

        return $collection->getItems();
    }
}
