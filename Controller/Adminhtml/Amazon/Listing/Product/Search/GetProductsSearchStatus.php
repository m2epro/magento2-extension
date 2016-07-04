<?php

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Search;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Main;

class GetProductsSearchStatus extends Main
{
    public function execute()
    {
        $productsIds = $this->getRequest()->getParam('products_ids');

        if (empty($productsIds)) {
            return $this->getResponse()->setBody('You should select one or more Products');
        }

        if (!is_array($productsIds)) {
            $productsIds = explode(',', $productsIds);
        }

        $connRead = $this->resourceConnection->getConnection();

        $tableListingProduct = $this->activeRecordFactory->getObject('Listing\Product')->getResource()->getMainTable();
        $tableAmazonListingProduct = $this->activeRecordFactory->getObject('Amazon\Listing\Product')
            ->getResource()->getMainTable();

        $itemsForSearchSelect = $connRead->select();
        $itemsForSearchSelect->from(array('lp' => $tableListingProduct), array('id'))
            ->join(
                array('alp' => $tableAmazonListingProduct),
                'lp.id = alp.listing_product_id',
                array()
            )
            ->where('lp.id IN (?)', $productsIds)
            ->where('lp.status = ?',(int)\Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED)
            ->where('alp.general_id IS NULL')
            ->where('alp.is_general_id_owner = 0');

        $selectWarnings = clone $itemsForSearchSelect;
        $selectError    = clone $itemsForSearchSelect;

        $searchStatusActionRequired = \Ess\M2ePro\Model\Amazon\Listing\Product::SEARCH_SETTINGS_STATUS_ACTION_REQUIRED;
        $searchStatusInProgress = \Ess\M2ePro\Model\Amazon\Listing\Product::SEARCH_SETTINGS_STATUS_IN_PROGRESS;
        $selectWarnings->where(
            'alp.search_settings_status = ' . $searchStatusActionRequired .
            ' OR alp.search_settings_status = ' . $searchStatusInProgress
        );

        $warningsCount = $this->resourceConnection->getConnection()->fetchCol($selectWarnings);

        $messages = array();

        if (count($warningsCount) > 0) {
            $messages[] = array(
                'type' => 'warning',
                'text' => $this->__(
                    'For %count% Items it is necessary to choose manually one of the found Amazon Products
                     or these Items are in process of Search and results for them will be available later.',
                    count($warningsCount)
                )
            );
        }

        $searchStatusNotFound = \Ess\M2ePro\Model\Amazon\Listing\Product::SEARCH_SETTINGS_STATUS_NOT_FOUND;
        $selectError->where(
            'alp.search_settings_status = ' . $searchStatusNotFound
        );

        $errorsCount = $this->resourceConnection->getConnection()->fetchCol($selectError);

        if (count($errorsCount) > 0) {
            $messages[] = array(
                'type' => 'error',
                'text' => $this->__(
                    'For %count% Items no Amazon Products were found. Please use Manual Search
                     or create New ASIN/ISBN.',
                    count($errorsCount)
                )
            );
        }

        if (empty($messages)) {
            $messages[] = array(
                'type' => 'success',
                'text' => $this->__(
                    'ASIN(s)/ISBN(s) were found and assigned for selected Items.'
                )
            );
        }

        $this->setJsonContent([
            'messages' => $messages
        ]);

        return $this->getResult();
    }
}