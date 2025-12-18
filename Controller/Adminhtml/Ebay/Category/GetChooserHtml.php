<?php

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Category;

use Ess\M2ePro\Model\Ebay\Template\Category as TemplateCategory;

class GetChooserHtml extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Category
{
    private \Ess\M2ePro\Helper\Component\Ebay\Category $componentEbayCategory;

    public function __construct(
        \Ess\M2ePro\Helper\Component\Ebay\Category $componentEbayCategory,
        \Ess\M2ePro\Block\Adminhtml\Magento\Product\Rule\ViewState\Manager $ruleViewStateManager,
        \Ess\M2ePro\Block\Adminhtml\Magento\Product\Rule\ViewStateFactory $viewStateFactory,
        \Ess\M2ePro\Model\Ebay\Magento\Product\RuleFactory $ebayProductRuleFactory,
        \Ess\M2ePro\Helper\Data\GlobalData $globalDataHelper,
        \Ess\M2ePro\Helper\Data\Session $sessionHelper,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct(
            $ruleViewStateManager,
            $viewStateFactory,
            $ebayProductRuleFactory,
            $globalDataHelper,
            $sessionHelper,
            $ebayFactory,
            $context
        );

        $this->componentEbayCategory = $componentEbayCategory;
    }

    public function execute()
    {
        $marketplaceId = $this->getRequest()->getParam('marketplace_id');
        $accountId = $this->getRequest()->getParam('account_id');
        $categoryMode = $this->getRequest()->getParam('category_mode');
        $isEditAllowed = $this->getRequest()->getParam('is_edit_category_allowed', true);

        $selectedCategories = [];
        if ($categoriesJson = $this->getRequest()->getParam('selected_categories')) {
            $selectedCategories = \Ess\M2ePro\Helper\Json::decode($categoriesJson);
        }

        /** @var \Ess\M2ePro\Block\Adminhtml\Ebay\Template\Category\Chooser $chooserBlock */
        $chooserBlock = $this
            ->getLayout()
            ->createBlock(\Ess\M2ePro\Block\Adminhtml\Ebay\Template\Category\Chooser::class);

        if ($marketplaceId) {
            $chooserBlock->setMarketplaceId($marketplaceId);
        }

        if ($accountId) {
            $chooserBlock->setAccountId($accountId);
        }
        $chooserBlock->setCategoryMode($categoryMode);
        $chooserBlock->setIsEditCategoryAllowed($isEditAllowed);

        if (!empty($selectedCategories)) {
            $converter = $this->modelFactory
                ->getObjectByClass(\Ess\M2ePro\Model\Ebay\Template\Category\Chooser\Converter::class);

            if ($marketplaceId) {
                $converter->setMarketplaceId($marketplaceId);
            }

            if ($accountId) {
                $converter->setAccountId($accountId);
            }

            $helper = $this->componentEbayCategory;
            foreach ($selectedCategories as $type => $selectedCategory) {
                if (empty($selectedCategory)) {
                    continue;
                }
                $converter->setCategoryDataFromChooser($selectedCategory, $type);

                if ($selectedCategory['mode'] == TemplateCategory::CATEGORY_MODE_EBAY) {
                    $helper->isEbayCategoryType($type)
                        ? $helper->addRecent($selectedCategory['value'], $marketplaceId, $type)
                        : $helper->addRecent($selectedCategory['value'], $accountId, $type);
                }
            }

            $chooserBlock->setCategoriesData($converter->getCategoryDataForChooser());
        }

        $this->setAjaxContent($chooserBlock->toHtml());

        return $this->getResult();
    }
}
