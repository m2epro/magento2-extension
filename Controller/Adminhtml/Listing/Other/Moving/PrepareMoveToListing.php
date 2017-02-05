<?php

namespace Ess\M2ePro\Controller\Adminhtml\Listing\Other\Moving;

use Ess\M2ePro\Controller\Adminhtml\Context;
use Ess\M2ePro\Controller\Adminhtml\Listing;

class PrepareMoveToListing extends Listing
{
    public function execute()
    {
        $componentMode = $this->getRequest()->getParam('componentMode');
        $selectedProducts = (array)$this->getHelper('Data')->jsonDecode(
            $this->getRequest()->getParam('selectedProducts')
        );

        $selectedProductsParts = array_chunk($selectedProducts, 1000);

        foreach ($selectedProductsParts as $selectedProductsPart) {
            $listingOtherCollection = $this->parentFactory
                ->getObject($componentMode, 'Listing\Other')
                ->getCollection();

            $listingOtherCollection->addFieldToFilter('main_table.id', array('in' => $selectedProductsPart));
            $tempData = $listingOtherCollection
                ->getSelect()
                ->query()
                ->fetchAll();

            foreach ($tempData as $data) {
                if (!$data['product_id']) {
                    $this->setAjaxContent('1', false);
                    return $this->getResult();
                }
            }

            $listingOtherCollection->getSelect()->join(
                array('cpe'=>$this->resourceConnection->getTableName('catalog_product_entity')),
                '`main_table`.`product_id` = `cpe`.`entity_id`'
            );

            $tempData = $listingOtherCollection
                ->getSelect()
                ->group(array('main_table.account_id','main_table.marketplace_id'))
                ->query()
                ->fetchAll();

            if (count($tempData) > 1) {
                $this->setAjaxContent('2', false);
                return $this->getResult();
            }
        }

        $marketplaceId = $tempData[0]['marketplace_id'];
        $accountId = $tempData[0]['account_id'];

        $response = array(
            'accountId' => $accountId,
            'marketplaceId' => $marketplaceId,
        );

        $this->setJsonContent($response);
        return $this->getResult();
    }
}