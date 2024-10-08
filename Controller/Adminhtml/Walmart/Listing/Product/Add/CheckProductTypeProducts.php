<?php

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Listing\Product\Add;

class CheckProductTypeProducts extends \Ess\M2ePro\Controller\Adminhtml\Walmart\Listing\Product\AbstractAdd
{
    public function execute()
    {
        $listingProductsIds = $this->getListing()->getSetting('additional_data', 'adding_listing_products_ids');

        /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\Collection $collection */
        $collection = $this->walmartFactory->getObject('Listing\Product')->getCollection();
        $collection->getSelect()->reset(\Magento\Framework\DB\Select::COLUMNS);
        $collection->getSelect()->columns([
            'id' => 'main_table.id',
        ]);
        $collection->getSelect()->where(
            "`main_table`.`id` IN (?) AND `second_table`.`product_type_id` IS NULL",
            $listingProductsIds
        );

        $failedProductsIds = $collection->getColumnValues('id');

        $popup = $this->getLayout()
                      ->createBlock(
                          \Ess\M2ePro\Block\Adminhtml\Walmart\Listing\Product\Add\ProductType\WarningPopup::class
                      );

        $this->setJsonContent([
            'validation' => count($failedProductsIds) == 0,
            'total_count' => count($listingProductsIds),
            'failed_count' => count($failedProductsIds),
            'failed_products' => $failedProductsIds,
            'html' => $popup->toHtml(),
            'next_step_url' => $this->getUrl('*/*/index', ['id' => $this->getRequest()->getParam('id'), 'step' => 4]),
        ]);

        return $this->getResult();
    }
}
