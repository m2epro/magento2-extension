<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Category;

use Ess\M2ePro\Model\Ebay\Template\Category as TemplateCategory;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Ebay\Category\GetChooserEditHtml
 */
class GetChooserEditHtml extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Category
{

    //########################################

    public function execute()
    {
        $categoryType  = $this->getRequest()->getParam('category_type');
        $selectedMode  = $this->getRequest()->getParam('selected_mode', TemplateCategory::CATEGORY_MODE_NONE);
        $selectedValue = $this->getRequest()->getParam('selected_value');
        $selectedPath  = $this->getRequest()->getParam('selected_path');
        $marketplaceId = $this->getRequest()->getParam('marketplace_id');
        $accountId     = $this->getRequest()->getParam('account_id');

        $editBlock = $this->createBlock('Ebay_Template_Category_Chooser_Edit');
        $editBlock->setCategoryType($categoryType);

        $helper = $this->getHelper('Component_Ebay_Category');

        if ($helper->isEbayCategoryType($categoryType)) {
            $recentCategories = $helper->getRecent($marketplaceId, $categoryType, $selectedValue);
        } else {
            $recentCategories = $helper->getRecent($accountId, $categoryType, $selectedValue);
        }

        if (empty($recentCategories)) {
            $this->getHelper('Data\GlobalData')->setValue('category_chooser_hide_recent', true);
        }

        if ($selectedMode != TemplateCategory::CATEGORY_MODE_NONE) {
            $editBlock->setSelectedCategory([
                'mode'  => $selectedMode,
                'value' => $selectedValue,
                'path'  => $selectedPath
            ]);
        }

        $this->setAjaxContent($editBlock->toHtml());

        return $this->getResult();
    }

    //########################################
}
