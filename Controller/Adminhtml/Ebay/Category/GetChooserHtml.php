<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Category;

use \Ess\M2ePro\Model\Ebay\Template\Category as TemplateCategory;

class GetChooserHtml extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Category
{
    /** @var \Ess\M2ePro\Helper\Component\Ebay\Category */
    private $componentEbayCategory;

    public function __construct(
        \Ess\M2ePro\Helper\Component\Ebay\Category $componentEbayCategory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($ebayFactory, $context);

        $this->componentEbayCategory = $componentEbayCategory;
    }

    public function execute()
    {
        $marketplaceId = $this->getRequest()->getParam('marketplace_id');
        $accountId     = $this->getRequest()->getParam('account_id');
        $categoryMode  = $this->getRequest()->getParam('category_mode');
        $isEditAllowed = $this->getRequest()->getParam('is_edit_category_allowed', true);

        $selectedCategories = [];
        if ($categoriesJson = $this->getRequest()->getParam('selected_categories')) {
            $selectedCategories = $this->getHelper('Data')->jsonDecode($categoriesJson);
        }

        /** @var \Ess\M2ePro\Block\Adminhtml\Ebay\Template\Category\Chooser $chooserBlock */
        $chooserBlock = $this->getLayout()
                             ->createBlock(\Ess\M2ePro\Block\Adminhtml\Ebay\Template\Category\Chooser::class);
        $marketplaceId && $chooserBlock->setMarketplaceId($marketplaceId);
        $accountId && $chooserBlock->setAccountId($accountId);
        $chooserBlock->setCategoryMode($categoryMode);
        $chooserBlock->setIsEditCategoryAllowed($isEditAllowed);

        if (!empty($selectedCategories)) {
            /** @var \Ess\M2ePro\Model\Ebay\Template\Category\Chooser\Converter $converter */
            $converter = $this->modelFactory->getObject('Ebay_Template_Category_Chooser_Converter');
            $marketplaceId && $converter->setMarketplaceId($marketplaceId);
            $accountId && $converter->setAccountId($accountId);

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

    //########################################
}
