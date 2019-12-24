<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Listing\Moving;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Listing\Moving\PrepareMoveToListing
 */
class PrepareMoveToListing extends \Ess\M2ePro\Controller\Adminhtml\Listing\Moving
{
    //########################################

    public function execute()
    {
        $componentMode = $this->getRequest()->getParam('componentMode');
        $selectedProducts = (array)$this->getHelper('Data')->jsonDecode(
            $this->getRequest()->getParam('selectedProducts')
        );

        $listingProductCollection = $this->parentFactory
            ->getObject($componentMode, 'Listing\Product')
            ->getCollection();

        $listingProductCollection->addFieldToFilter('main_table.id', ['in' => $selectedProducts]);
        $tempData = $listingProductCollection
            ->getSelect()
            ->join(
                ['listing'=>$this->activeRecordFactory->getObject('Listing')->getResource()->getMainTable()],
                '`main_table`.`listing_id` = `listing`.`id`'
            )
            ->join(
                [
                'cpe'=>$this->getHelper('Module_Database_Structure')
                    ->getTableNameWithPrefix('catalog_product_entity')
                ],
                '`main_table`.`product_id` = `cpe`.`entity_id`'
            )
            ->group(['listing.account_id','listing.marketplace_id'])
            ->reset(\Zend_Db_Select::COLUMNS)
            ->columns(['marketplace_id', 'account_id'], 'listing')
            ->query()
            ->fetchAll();

        $this->setJsonContent([
            'accountId' => $tempData[0]['account_id'],
            'marketplaceId' => $tempData[0]['marketplace_id'],
        ]);
        return $this->getResult();
    }

    //########################################
}
