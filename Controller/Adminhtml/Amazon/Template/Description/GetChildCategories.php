<?php

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Template\Description;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Template\Description;

class GetChildCategories extends Description
{
    //########################################

    public function execute()
    {
        $select = $this->resourceConnection->getConnection()->select()
            ->from($this->resourceConnection->getTableName('m2epro_amazon_dictionary_category'))
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