<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Category;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Ebay\Category\Search
 */
class Search extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Category
{

    //########################################

    public function execute()
    {
        $query = $this->getRequest()->getParam('query');
        $categoryType = $this->getRequest()->getParam('category_type');
        $marketplaceId  = $this->getRequest()->getParam('marketplace_id');
        $accountId  = $this->getRequest()->getParam('account_id');
        $result = [];

        $ebayCategoryTypes = $this->getHelper('Component_Ebay_Category')->getEbayCategoryTypes();
        $storeCategoryTypes = $this->getHelper('Component_Ebay_Category')->getStoreCategoryTypes();

        if ($query === null
            || (in_array($categoryType, $ebayCategoryTypes) && $marketplaceId === null)
            || (in_array($categoryType, $storeCategoryTypes) && $accountId === null)
        ) {
            $this->setJsonContent($result);

            return $this->getResult();
        }

        $connection = $this->resourceConnection->getConnection();
        if (in_array($categoryType, $ebayCategoryTypes)) {
            $tableName = $this->getHelper('Module_Database_Structure')
                ->getTableNameWithPrefix('m2epro_ebay_dictionary_category');
        } else {
            $tableName = $this->getHelper('Module_Database_Structure')
                ->getTableNameWithPrefix('m2epro_ebay_account_store_category');
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
                $treePath = $this->getHelper('Component_Ebay_Category_Ebay')->getPath(
                    $categoryId['category_id'],
                    $marketplaceId
                );
            } else {
                $treePath = $this->getHelper('Component_Ebay_Category_Store')->getPath(
                    $categoryId['category_id'],
                    $accountId
                );
            }

            $result[] = [
                'titles' => $treePath,
                'id' => $categoryId['category_id']
            ];
        }

        $this->setJsonContent($result);

        return $this->getResult();
    }

    //########################################
}
