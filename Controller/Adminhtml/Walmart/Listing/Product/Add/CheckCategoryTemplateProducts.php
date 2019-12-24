<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Listing\Product\Add;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Walmart\Listing\Product\Add\CheckCategoryTemplateProducts
 */
class CheckCategoryTemplateProducts extends \Ess\M2ePro\Controller\Adminhtml\Walmart\Listing\Product\Add
{
    public function execute()
    {
        $listingProductsIds = $this->getListing()->getSetting('additional_data', 'adding_listing_products_ids');

        /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\Collection $collection */
        $collection = $this->walmartFactory->getObject('Listing\Product')->getCollection();
        $collection->getSelect()->reset(\Zend_Db_Select::COLUMNS);
        $collection->getSelect()->columns([
            'id' => 'main_table.id'
        ]);
        $collection->getSelect()->where(
            "`main_table`.`id` IN (?) AND `second_table`.`template_category_id` IS NULL",
            $listingProductsIds
        );

        $failedProductsIds = $collection->getColumnValues('id');

        $popup = $this->createBlock('Walmart_Listing_Product_Add_CategoryTemplate_WarningPopup');

        $this->setJsonContent([
            'validation'      => count($failedProductsIds) == 0,
            'total_count'     => count($listingProductsIds),
            'failed_count'    => count($failedProductsIds),
            'failed_products' => $failedProductsIds,
            'html' => $popup->toHtml(),
            'next_step_url' => $this->getUrl('*/*/index', ['id'=>$this->getRequest()->getParam('id'), 'step' => 4])
        ]);

        return $this->getResult();
    }
}
