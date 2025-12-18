<?php

declare(strict_types=1);

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Wizard\Category;

use Ess\M2ePro\Model\MarketplaceFactory;
use Ess\M2ePro\Model\ResourceModel\Marketplace as MarketplaceResource;
use Ess\M2ePro\Model\AccountFactory;
use Ess\M2ePro\Model\ResourceModel\Account as AccountResource;
use Ess\M2ePro\Helper\Component\Ebay\Category as CategoryHelper;

class GetSelectedCategoriesDetails extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Category
{
    private \Ess\M2ePro\Helper\Component\Ebay\Category $componentEbayCategory;
    private \Ess\M2ePro\Helper\Magento\Attribute $magentoAttributeHelper;
    private AccountFactory $accountFactory;
    private AccountResource $accountResource;
    private MarketplaceFactory $marketplaceModelFactory;
    private MarketplaceResource $marketplaceResource;

    public function __construct(
        \Ess\M2ePro\Helper\Magento\Attribute $magentoAttributeHelper,
        \Ess\M2ePro\Helper\Component\Ebay\Category $componentEbayCategory,
        \Ess\M2ePro\Block\Adminhtml\Magento\Product\Rule\ViewState\Manager $ruleViewStateManager,
        \Ess\M2ePro\Block\Adminhtml\Magento\Product\Rule\ViewStateFactory $viewStateFactory,
        \Ess\M2ePro\Model\Ebay\Magento\Product\RuleFactory $ebayProductRuleFactory,
        \Ess\M2ePro\Helper\Data\GlobalData $globalDataHelper,
        \Ess\M2ePro\Helper\Data\Session $sessionHelper,
        MarketplaceResource $marketplaceResource,
        MarketplaceFactory $marketplaceModelFactory,
        AccountFactory $accountFactory,
        AccountResource $accountResource,
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

        $this->magentoAttributeHelper = $magentoAttributeHelper;
        $this->componentEbayCategory = $componentEbayCategory;
        $this->marketplaceResource = $marketplaceResource;
        $this->marketplaceModelFactory = $marketplaceModelFactory;
        $this->accountFactory = $accountFactory;
        $this->accountResource = $accountResource;
    }

    public function execute()
    {
        $details = [
            'path' => '',
            'interface_path' => '',
            'template_id' => null,
            'is_custom_template' => null,
        ];

        $categoryHelper = $this->componentEbayCategory;

        $marketplaceId = $this->getRequest()->getParam('marketplace_id');
        $accountId = $this->getRequest()->getParam('account_id');
        $value = $this->getRequest()->getParam('value');
        $mode = $this->getRequest()->getParam('mode');
        $categoryType = $this->getRequest()->getParam('category_type');

        switch ($mode) {
            case \Ess\M2ePro\Model\Ebay\Template\Category::CATEGORY_MODE_EBAY:
                $details['path'] = $categoryHelper->isEbayCategoryType($categoryType)
                    ? $this->getEbayCategoryPath($value, $marketplaceId)
                    : $this->getStoreCategoryPath($value, $accountId);

                $details['interface_path'] = $details['path'] . ' (' . $value . ')';
                break;
            case \Ess\M2ePro\Model\Ebay\Template\Category::CATEGORY_MODE_ATTRIBUTE:
                $details['path'] = $this->__('Magento Attribute') . ' > ' .
                    $this->magentoAttributeHelper->getAttributeLabel($value);

                $details['interface_path'] = $details['path'];

                break;
        }

        if (in_array($categoryType, [CategoryHelper::TYPE_EBAY_MAIN, CategoryHelper::TYPE_EBAY_SECONDARY])) {
            $template = $this->activeRecordFactory->getObject('Ebay_Template_Category');
            $template->loadByCategoryValue($value, $mode, $marketplaceId, 0);
            if (!$template->isObjectNew()) {
                $details['is_custom_template'] = $template->getIsCustomTemplate();
                $details['template_id'] = $template->getId();
            }
        } elseif (in_array($categoryType, [CategoryHelper::TYPE_STORE_MAIN, CategoryHelper::TYPE_STORE_SECONDARY])) {
            $template = $this->activeRecordFactory->getObject('Ebay_Template_StoreCategory');
            $template->loadByCategoryValue($value, $mode, $marketplaceId);
            $details['id'] = $template->getId();
        }

        $this->setJsonContent($details);

        return $this->getResult();
    }

    private function getEbayCategoryPath($value, $marketplaceId, $includeTitle = true)
    {
        $marketplaceModel = $this->marketplaceModelFactory->create();

        $this->marketplaceResource->load($marketplaceModel, $marketplaceId);

        if ($marketplaceModel->getId()) {
            $category = $marketplaceModel->getChildObject()
                                     ->getCategory((int)$value);
        }

        if (!$category) {
            return '';
        }

        $category['path'] = str_replace(' > ', '>', $category['path']);

        return $category['path'] . ($includeTitle ? '>' . $category['title'] : '');
    }

    private function getStoreCategoryPath($value, $accountId, $delimiter = '>')
    {
        $accountModel = $this->accountFactory->create();
        $this->accountResource->load($accountModel, $accountId);
        if ($accountModel->getId()) {
            $categories = $accountModel->getChildObject()->getEbayStoreCategories();

            $pathData = [];

            while (true) {
                $currentCategory = null;

                foreach ($categories as $category) {
                    if ($category['category_id'] == $value) {
                        $currentCategory = $category;
                        break;
                    }
                }

                if ($currentCategory === null) {
                    break;
                }

                $pathData[] = $currentCategory['title'];

                if ($currentCategory['parent_id'] == 0) {
                    break;
                }

                $value = $currentCategory['parent_id'];
            }

            array_reverse($pathData);

            return implode($delimiter, $pathData);
        }

        return '';
    }
}
