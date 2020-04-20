<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Category;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Ebay\Category\GetChooserEditHtml
 */
class GetChooserEditHtml extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Category
{

    //########################################

    public function execute()
    {
        // ---------------------------------------
        $categoryType = $this->getRequest()->getParam('category_type');
        $selectedMode = $this->getRequest()->getParam(
            'selected_mode',
            \Ess\M2ePro\Model\Ebay\Template\Category::CATEGORY_MODE_NONE
        );
        $selectedValue = $this->getRequest()->getParam('selected_value');
        $selectedPath = $this->getRequest()->getParam('selected_path');
        $marketplaceId = $this->getRequest()->getParam('marketplace_id');
        $accountId = $this->getRequest()->getParam('account_id');
        // ---------------------------------------

        $this->getHelper('Data\GlobalData')->setValue('chooser_category_type', $categoryType);

        // ---------------------------------------
        $editBlock = $this->createBlock('Ebay_Listing_Product_Category_Settings_Chooser_Edit');
        $editBlock->setCategoryType($categoryType);
        // ---------------------------------------

        $ebayCategoryTypes = $this->getHelper('Component_Ebay_Category')->getEbayCategoryTypes();

        if (in_array($categoryType, $ebayCategoryTypes)) {
            $recentCategories = $this->getHelper('Component_Ebay_Category')->getRecent(
                $marketplaceId,
                $categoryType,
                $selectedValue
            );
        } else {
            $recentCategories = $this->getHelper('Component_Ebay_Category')->getRecent(
                $accountId,
                $categoryType,
                $selectedValue
            );
        }

        if (empty($recentCategories)) {
            $this->getHelper('Data\GlobalData')->setValue('category_chooser_hide_recent', true);
        }

        if ($selectedMode != \Ess\M2ePro\Model\Ebay\Template\Category::CATEGORY_MODE_NONE) {
            if (empty($selectedPath)) {
                switch ($selectedMode) {
                    case \Ess\M2ePro\Model\Ebay\Template\Category::CATEGORY_MODE_EBAY:
                        if (in_array($categoryType, $ebayCategoryTypes)) {
                            $selectedPath = $this->getHelper('Component_Ebay_Category_Ebay')->getPath(
                                $selectedValue,
                                $marketplaceId
                            );

                            $selectedPath .= ' (' . $selectedValue . ')';
                        } else {
                            $selectedPath = $this->getHelper('Component_Ebay_Category_Store')->getPath(
                                $selectedValue,
                                $accountId
                            );
                        }

                        break;
                    case \Ess\M2ePro\Model\Ebay\Template\Category::CATEGORY_MODE_ATTRIBUTE:
                        $attributeLabel = $this->getHelper('Magento\Attribute')->getAttributeLabel($selectedValue);
                        $selectedPath = $this->__('Magento Attribute') . ' > ' . $attributeLabel;

                        break;
                }
            }

            $editBlock->setSelectedCategory([
                'mode' => $selectedMode,
                'value' => $selectedValue,
                'path' => $selectedPath
            ]);
        }

        $this->setAjaxContent($editBlock->toHtml());

        return $this->getResult();
    }

    //########################################
}
