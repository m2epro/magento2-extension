<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Template\Category;

use Ess\M2ePro\Controller\Adminhtml\Walmart\Template\Category;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Walmart\Template\Category\GetChildCategories
 */
class GetChildCategories extends Category
{
    //########################################

    public function execute()
    {
        $select = $this->resourceConnection->getConnection()->select()
            ->from(
                $this->getHelper('Module_Database_Structure')
                    ->getTableNameWithPrefix('m2epro_walmart_dictionary_category')
            )
            ->where('marketplace_id = ?', $this->getRequest()->getPost('marketplace_id'))
            ->order('title ASC');

        $parentCategoryId = $this->getRequest()->getPost('parent_category_id');
        empty($parentCategoryId) ? $select->where('parent_category_id IS NULL')
            : $select->where('parent_category_id = ?', $parentCategoryId);

        $queryStmt = $select->query();
        $tempCategories = [];

        $sortIndex = 0;
        while ($row = $queryStmt->fetch()) {
            $this->formatCategoryRow($row);
            $this->isItOtherCategory($row) ? $tempCategories[10000] = $row
                : $tempCategories[$sortIndex++] = $row;
        }

        ksort($tempCategories);
        $this->setJsonContent(array_values($tempCategories));
        return $this->getResult();
    }

    // ---------------------------------------

    private function isItOtherCategory($row)
    {
        $parentTitle = explode('>', $row['path']);
        $parentTitle = array_pop($parentTitle);

        return preg_match("/^.* \({$parentTitle}\)$/i", $row['title']);
    }

    //########################################
}
