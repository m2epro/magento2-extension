<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Template\Category;

use Ess\M2ePro\Controller\Adminhtml\Walmart\Template\Category;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Walmart\Template\Category\GetCategoryInfoByCategoryId
 */
class GetCategoryInfoByCategoryId extends Category
{
    //########################################

    public function execute()
    {
        $category = $this->resourceConnection->getConnection()->select()
            ->from(
                $this->getHelper('Module_Database_Structure')
                    ->getTableNameWithPrefix('m2epro_walmart_dictionary_category')
            )
            ->where('marketplace_id = ?', $this->getRequest()->getPost('marketplace_id'))
            ->where('category_id = ?', $this->getRequest()->getPost('category_id'))
            ->query()
            ->fetch();

        if (!$category) {
            $this->setAjaxContent(null, false);
            return $this->getResult();
        }

        $this->formatCategoryRow($category);
        $this->setJsonContent($category);
        return $this->getResult();
    }

    //########################################
}
