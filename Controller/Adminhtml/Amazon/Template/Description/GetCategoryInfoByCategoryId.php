<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Template\Description;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Template\Description;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Amazon\Template\Description\GetCategoryInfoByCategoryId
 */
class GetCategoryInfoByCategoryId extends Description
{
    //########################################

    public function execute()
    {
        $category = $this->resourceConnection->getConnection()->select()
            ->from(
                $this->getHelper('Module_Database_Structure')
                    ->getTableNameWithPrefix('m2epro_amazon_dictionary_category')
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
