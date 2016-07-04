<?php

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Template\Description;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Template\Description;

class SaveRecentCategory extends Description
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

        $this->getHelper('Component\Amazon\Category')->addRecent(
            $marketplaceId, $browseNodeId, $categoryPath
        );

        $this->setJsonContent(['result' => true]);
        return $this->getResult();
    }

    //########################################
}