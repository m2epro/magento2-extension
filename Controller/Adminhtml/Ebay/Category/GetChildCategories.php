<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Category;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Ebay\Category\GetChildCategories
 */
class GetChildCategories extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Category
{

    //########################################

    public function execute()
    {
        $marketplaceId  = $this->getRequest()->getParam('marketplace_id');
        $accountId = $this->getRequest()->getParam('account_id');
        $parentCategoryId  = $this->getRequest()->getParam('parent_category_id');
        $categoryType = $this->getRequest()->getParam('category_type');

        $ebayCategoryTypes = $this->getHelper('Component_Ebay_Category')->getEbayCategoryTypes();
        $storeCategoryTypes = $this->getHelper('Component_Ebay_Category')->getStoreCategoryTypes();

        $data = [];

        if ((in_array($categoryType, $ebayCategoryTypes) && $marketplaceId === null) ||
            (in_array($categoryType, $storeCategoryTypes) && $accountId === null)
        ) {
            $this->setJsonContent($data);
            return $this->getResult();
        }

        if (in_array($categoryType, $ebayCategoryTypes)) {
            $data = $this->ebayFactory->getCachedObjectLoaded('Marketplace', $marketplaceId)
                ->getChildObject()
                ->getChildCategories($parentCategoryId);
        } elseif (in_array($categoryType, $storeCategoryTypes)) {
            $connection = $this->resourceConnection->getConnection();
            $tableAccountStoreCategories = $this->getHelper('Module_Database_Structure')->getTableNameWithPrefix(
                'm2epro_ebay_account_store_category'
            );

            $dbSelect = $connection->select()
                ->from($tableAccountStoreCategories, '*')
                ->where('`account_id` = ?', (int)$accountId)
                ->where('`parent_id` = ?', $parentCategoryId)
                ->order(['sorder ASC']);

            $data = $connection->fetchAll($dbSelect);
        }

        $this->setJsonContent($data);

        return $this->getResult();
    }

    //########################################
}
