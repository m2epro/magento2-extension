<?php

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Category;

class GetChooserHtml extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Category
{

    //########################################

    public function execute()
    {
        // ---------------------------------------
        $selectedCategoriesJson = $this->getRequest()->getParam('selected_categories');
        $marketplaceId = $this->getRequest()->getParam('marketplace_id');
        $accountId = $this->getRequest()->getParam('account_id');
        $divId = $this->getRequest()->getParam('div_id');
        $isShowEditLinks = $this->getRequest()->getParam('is_show_edit_links');
        $isSingleCategoryMode = $this->getRequest()->getParam('is_single_category_mode');
        $singleCategoryType = $this->getRequest()->getParam('single_category_type');
        $selectCallback = $this->getRequest()->getParam('select_callback');
        $unSelectCallback = $this->getRequest()->getParam('unselect_callback');

        $selectedCategories = array();
        if (!is_null($selectedCategoriesJson)) {
            $selectedCategories = $this->getHelper('Data')->jsonDecode($selectedCategoriesJson);
        }
        // ---------------------------------------

        $ebayCategoryTypes = $this->getHelper('Component\Ebay\Category')->getEbayCategoryTypes();
        $storeCategoryTypes = $this->getHelper('Component\Ebay\Category')->getStoreCategoryTypes();

        foreach ($selectedCategories as $type => &$selectedCategory) {
            if (!empty($selectedCategory['path'])) {
                continue;
            }

            switch ($selectedCategory['mode']) {
                case \Ess\M2ePro\Model\Ebay\Template\Category::CATEGORY_MODE_EBAY:
                    if (in_array($type, $ebayCategoryTypes)) {
                        $selectedCategory['path'] = $this->getHelper('Component\Ebay\Category\Ebay')
                            ->getPath(
                                $selectedCategory['value'],
                                $marketplaceId
                            );

                        $selectedCategory['path'] .= ' (' . $selectedCategory['value'] . ')';

                        $this->getHelper('Component\Ebay\Category')
                            ->addRecent(
                                $selectedCategory['value'],
                                $marketplaceId,
                                $type
                            );
                    } elseif (in_array($type, $storeCategoryTypes)) {
                        $selectedCategory['path'] = $this->getHelper('Component\Ebay\Category\Store')
                            ->getPath(
                                $selectedCategory['value'],
                                $accountId
                            );

                        $selectedCategory['path'] .= ' (' . $selectedCategory['value'] . ')';

                        $this->getHelper('Component\Ebay\Category')
                            ->addRecent(
                                $selectedCategory['value'],
                                $accountId,
                                $type
                            );
                    }

                    break;

                case \Ess\M2ePro\Model\Ebay\Template\Category::CATEGORY_MODE_ATTRIBUTE:
                    $attributeLabel = $this->getHelper('Magento\Attribute')
                        ->getAttributeLabel($selectedCategory['value']);

                    $selectedCategory['path'] = $this->__('Magento Attribute');
                    $selectedCategory['path'] .= ' > ' . $attributeLabel;
                    break;
            }
        }

        // ---------------------------------------
        /** @var \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Product\Category\Settings\Chooser $chooserBlock */
        $chooserBlock = $this->createBlock('Ebay\Listing\Product\Category\Settings\Chooser');
        $chooserBlock->setMarketplaceId($marketplaceId);
        $chooserBlock->setDivId($divId);
        if (!empty($accountId)) {
            $chooserBlock->setAccountId($accountId);
        }
        if (!empty($selectedCategories)) {
            $chooserBlock->setConvertedInternalData($selectedCategories);
        }
        if (!empty($isShowEditLinks)) {
            $chooserBlock->setShowEditLinks($isShowEditLinks);
        }
        if ($isSingleCategoryMode === 'true') {
            $chooserBlock->setSingleCategoryMode();
            $chooserBlock->setSingleCategoryType($singleCategoryType);
        }
        if (!empty($selectCallback)) {
            $chooserBlock->setSelectCallback($selectCallback);
        }
        if (!empty($unselectCallback)) {
            $chooserBlock->setUnselectCallback($unSelectCallback);
        }
        // ---------------------------------------

        $this->setAjaxContent($chooserBlock->toHtml());

        return $this->getResult();
    }

    //########################################
}