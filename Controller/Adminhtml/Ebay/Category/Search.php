<?php

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Category;

class Search extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Category
{

    //########################################

    public function execute()
    {
        $query = $this->getRequest()->getParam('query');
        $categoryType = $this->getRequest()->getParam('category_type');
        $marketplaceId  = $this->getRequest()->getParam('marketplace_id');
        $accountId  = $this->getRequest()->getParam('account_id');
        $result = array();

        $ebayCategoryTypes = $this->getHelper('Component\Ebay\Category')->getEbayCategoryTypes();
        $storeCategoryTypes = $this->getHelper('Component\Ebay\Category')->getStoreCategoryTypes();

        if (is_null($query)
            || (in_array($categoryType, $ebayCategoryTypes) && is_null($marketplaceId))
            || (in_array($categoryType, $storeCategoryTypes) && is_null($accountId))
        ) {
            $this->setJsonContent($result);

            return $this->getResult();
        }

        $connection = $this->resourceConnection->getConnection();
        if (in_array($categoryType, $ebayCategoryTypes)) {
            $tableName = $this->resourceConnection->getTableName('m2epro_ebay_dictionary_category');
        } else {
            $tableName = $this->resourceConnection->getTableName('m2epro_ebay_account_store_category');
        }

        $dbSelect = $connection->select();
        $dbSelect->from($tableName, 'category_id')
            ->where('is_leaf = ?', 1);
        if (in_array($categoryType, $ebayCategoryTypes)) {
            $dbSelect->where('marketplace_id = ?', (int)$marketplaceId);
        } else {
            $dbSelect->where('account_id = ?', (int)$accountId);
        }

        $tempDbSelect = clone $dbSelect;
        $isSearchById = false;

        if (is_numeric($query)) {
            $dbSelect->where('category_id = ?', $query);
            $isSearchById = true;
        } else {
            $dbSelect->where('title like ?', '%' . $query . '%');
        }

        $ids = $connection->fetchAll($dbSelect);
        if (empty($ids) && $isSearchById) {
            $tempDbSelect->where('title like ?', '%' . $query . '%');
            $ids = $connection->fetchAll($tempDbSelect);
        }

        foreach ($ids as $categoryId) {
            if (in_array($categoryType, $ebayCategoryTypes)) {
                $treePath = $this->getHelper('Component\Ebay\Category\Ebay')->getPath(
                    $categoryId['category_id'], $marketplaceId
                );
            } else {
                $treePath = $this->getHelper('Component\Ebay\Category\Store')->getPath(
                    $categoryId['category_id'], $accountId
                );
            }

            $result[] = array(
                'titles' => $treePath,
                'id' => $categoryId['category_id']
            );
        }

        $this->setJsonContent($result);

        return $this->getResult();
    }

    //########################################
}