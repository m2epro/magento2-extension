<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Template\Category;

use Ess\M2ePro\Controller\Adminhtml\Walmart\Template\Category;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Walmart\Template\Category\SaveRecentCategory
 */
class SaveRecentCategory extends Category
{
    //########################################

    public function execute()
    {
        $marketplaceId = $this->getRequest()->getPost('marketplace_id');
        $browseNodeId  = $this->getRequest()->getPost('browsenode_id');
        $categoryPath  = $this->getRequest()->getPost('category_path');

        if (!$marketplaceId || !$browseNodeId || !$categoryPath) {
            $this->setJsonContent(['result' => false]);
            return $this->getResult();
        }

        $this->getHelper('Component_Walmart_Category')->addRecent(
            $marketplaceId,
            $browseNodeId,
            $categoryPath
        );

        $this->setJsonContent(['result' => true]);
        return $this->getResult();
    }

    //########################################
}
