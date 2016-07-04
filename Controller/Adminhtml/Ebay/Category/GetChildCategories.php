<?php

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Category;

class GetChildCategories extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Category
{

    //########################################

    public function execute()
    {
        $marketplaceId  = $this->getRequest()->getParam('marketplace_id');
        $accountId = $this->getRequest()->getParam('account_id');
        $parentCategoryId  = $this->getRequest()->getParam('parent_category_id');
        $categoryType = $this->getRequest()->getParam('category_type');

        $ebayCategoryTypes = $this->getHelper('Component\Ebay\Category')->getEbayCategoryTypes();
        $storeCategoryTypes = $this->getHelper('Component\Ebay\Category')->getStoreCategoryTypes();

        $data = array();

        if ((in_array($categoryType, $ebayCategoryTypes) && is_null($marketplaceId)) ||
            (in_array($categoryType, $storeCategoryTypes) && is_null($accountId))
        ) {
            $this->setJsonContent($data);
            return $this->getResult();
        }

        if (in_array($categoryType, $ebayCategoryTypes)) {
            $data = $this->ebayFactory->getCachedObjectLoaded('Marketplace',$marketplaceId)
                ->getChildObject()
                ->getChildCategories($parentCategoryId);
        } elseif (in_array($categoryType, $storeCategoryTypes)) {

            $connection = $this->resourceConnection->getConnection();
            $tableAccountStoreCategories = $this->resourceConnection->getTableName(
                'm2epro_ebay_account_store_category'
            );

            $dbSelect = $connection->select()
                ->from($tableAccountStoreCategories,'*')
                ->where('`account_id` = ?',(int)$accountId)
                ->where('`parent_id` = ?', $parentCategoryId)
                ->order(array('sorder ASC'));

            $data = $connection->fetchAll($dbSelect);
        }

        $this->setJsonContent($data);

        return $this->getResult();
    }

    //########################################
}