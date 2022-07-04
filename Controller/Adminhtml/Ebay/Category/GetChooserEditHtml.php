<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Category;

use Ess\M2ePro\Model\Ebay\Template\Category as TemplateCategory;

class GetChooserEditHtml extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Category
{
    /** @var \Ess\M2ePro\Helper\Component\Ebay\Category */
    private $componentEbayCategory;

    /** @var \Ess\M2ePro\Helper\Data\GlobalData */
    private $helperDataGlobalData;

    public function __construct(
        \Ess\M2ePro\Helper\Data\GlobalData $helperDataGlobalData,
        \Ess\M2ePro\Helper\Component\Ebay\Category $componentEbayCategory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($ebayFactory, $context);

        $this->helperDataGlobalData = $helperDataGlobalData;
        $this->componentEbayCategory = $componentEbayCategory;
    }

    public function execute()
    {
        $categoryType  = $this->getRequest()->getParam('category_type');
        $selectedMode  = $this->getRequest()->getParam('selected_mode', TemplateCategory::CATEGORY_MODE_NONE);
        $selectedValue = $this->getRequest()->getParam('selected_value');
        $selectedPath  = $this->getRequest()->getParam('selected_path');
        $marketplaceId = $this->getRequest()->getParam('marketplace_id');
        $accountId     = $this->getRequest()->getParam('account_id');

        $editBlock = $this->getLayout()
                          ->createBlock(\Ess\M2ePro\Block\Adminhtml\Ebay\Template\Category\Chooser\Edit::class);
        $editBlock->setCategoryType($categoryType);

        $helper = $this->componentEbayCategory;

        if ($helper->isEbayCategoryType($categoryType)) {
            $recentCategories = $helper->getRecent($marketplaceId, $categoryType, $selectedValue);
        } else {
            $recentCategories = $helper->getRecent($accountId, $categoryType, $selectedValue);
        }

        if (empty($recentCategories)) {
            $this->helperDataGlobalData->setValue('category_chooser_hide_recent', true);
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
