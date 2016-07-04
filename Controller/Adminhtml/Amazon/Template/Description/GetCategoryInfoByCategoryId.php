<?php

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Template\Description;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Template\Description;

class GetCategoryInfoByCategoryId extends Description
{
    //########################################

    public function execute()
    {
        $category = $this->resourceConnection->getConnection()->select()
            ->from($this->resourceConnection->getTableName('m2epro_amazon_dictionary_category'))
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