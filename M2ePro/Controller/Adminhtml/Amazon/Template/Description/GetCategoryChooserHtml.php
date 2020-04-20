<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Template\Description;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Template\Description;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Amazon\Template\Description\GetCategoryChooserHtml
 */
class GetCategoryChooserHtml extends Description
{
    //########################################

    public function execute()
    {
        /** @var \Ess\M2ePro\Block\Adminhtml\Amazon\Template\Description\Category\Chooser\Edit $editBlock */
        $editBlock = $this->createBlock('Amazon_Template_Description_Category_Chooser_Edit');

        $editBlock->setMarketplaceId($this->getRequest()->getPost('marketplace_id'));

        $browseNodeId = $this->getRequest()->getPost('browsenode_id');
        $categoryPath = $this->getRequest()->getPost('category_path');

        $recentlySelectedCategories = $this->getHelper('Component_Amazon_Category')->getRecent(
            $this->getRequest()->getPost('marketplace_id'),
            ['browsenode_id' => $browseNodeId, 'path' => $categoryPath]
        );

        if (empty($recentlySelectedCategories)) {
            $this->getHelper('Data\GlobalData')->setValue('category_chooser_hide_recent', true);
        }

        if ($browseNodeId && $categoryPath) {
            $editBlock->setSelectedCategory([
                'browseNodeId' => $browseNodeId,
                'categoryPath' => $categoryPath
            ]);
        }

        $this->setAjaxContent($editBlock->toHtml());
        return $this->getResult();
    }

    //########################################
}
