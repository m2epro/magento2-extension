<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Template\Description;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Template\Description;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Amazon\Template\Description\GetCategoryInfoByBrowseNodeId
 */
class GetCategoryInfoByBrowseNodeId extends Description
{
    //########################################

    public function execute()
    {
        $queryStmt = $this->resourceConnection->getConnection()->select()
            ->from(
                $this->getHelper('Module_Database_Structure')
                    ->getTableNameWithPrefix('m2epro_amazon_dictionary_category')
            )
            ->where('marketplace_id = ?', $this->getRequest()->getPost('marketplace_id'))
            ->where('browsenode_id = ?', $this->getRequest()->getPost('browsenode_id'))
            ->query();

        $tempCategories = [];

        while ($row = $queryStmt->fetch()) {
            $this->formatCategoryRow($row);
            $tempCategories[] = $row;
        }

        if (empty($tempCategories)) {
            $this->setAjaxContent(null, false);
            return $this->getResult();
        }

        $dbCategoryPath = str_replace(' > ', '>', $this->getRequest()->getPost('category_path'));

        foreach ($tempCategories as $category) {
            $tempCategoryPath = $category['path'] !== null ? $category['path'] .'>'. $category['title']
                : $category['title'];
            if ($tempCategoryPath == $dbCategoryPath) {
                $this->setJsonContent($category);
                return $this->getResult();
            }
        }

        $this->setJsonContent($tempCategories[0]);
        return $this->getResult();
    }

    //########################################
}
