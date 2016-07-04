<?php

namespace Ess\M2ePro\Controller\Adminhtml\Listing\Moving;

class PrepareMoveToListing extends \Ess\M2ePro\Controller\Adminhtml\Listing\Moving
{
    //########################################

    public function execute()
    {
        $componentMode = $this->getRequest()->getParam('componentMode');
        $selectedProducts = (array)json_decode($this->getRequest()->getParam('selectedProducts'));

        $listingProductCollection = $this->parentFactory
            ->getObject($componentMode, 'Listing\Product')
            ->getCollection();

        $listingProductCollection->addFieldToFilter('main_table.id', array('in' => $selectedProducts));
        $tempData = $listingProductCollection
            ->getSelect()
            ->join(array('listing'=>$this->activeRecordFactory->getObject('Listing')->getResource()->getMainTable()),
                '`main_table`.`listing_id` = `listing`.`id`' )
            ->join(array('cpe'=>$this->resourceConnection->getTableName('catalog_product_entity')),
                '`main_table`.`product_id` = `cpe`.`entity_id`' )
            ->group(array('listing.account_id','listing.marketplace_id'))
            ->reset(\Zend_Db_Select::COLUMNS)
            ->columns(array('marketplace_id', 'account_id'), 'listing')
            ->query()
            ->fetchAll();

        $this->setAjaxContent(json_encode(array(
            'accountId' => $tempData[0]['account_id'],
            'marketplaceId' => $tempData[0]['marketplace_id'],
        )), false);
        return $this->getResult();
    }

    //########################################
}