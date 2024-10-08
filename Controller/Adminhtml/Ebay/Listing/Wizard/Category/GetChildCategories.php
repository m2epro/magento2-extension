<?php

declare(strict_types=1);

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Wizard\Category;

use Ess\M2ePro\Model\ResourceModel\Marketplace as MarketplaceResource;
use Ess\M2ePro\Model\MarketplaceFactory;

//@todo copy-paste from the legacy controller, consider refactoring
class GetChildCategories extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Category
{
    /** @var \Ess\M2ePro\Helper\Component\Ebay\Category */
    private $componentEbayCategory;

    private MarketplaceFactory $marketplaceModelFactory;

    private MarketplaceResource $marketplaceResource;

    public function __construct(
        \Ess\M2ePro\Helper\Component\Ebay\Category $componentEbayCategory,
        \Ess\M2ePro\Block\Adminhtml\Magento\Product\Rule\ViewState\Manager $ruleViewStateManager,
        \Ess\M2ePro\Block\Adminhtml\Magento\Product\Rule\ViewStateFactory $viewStateFactory,
        \Ess\M2ePro\Model\Ebay\Magento\Product\RuleFactory $ebayProductRuleFactory,
        \Ess\M2ePro\Helper\Data\GlobalData $globalDataHelper,
        MarketplaceFactory $marketplaceModelFactory,
        MarketplaceResource $marketplaceResource,
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
        $this->marketplaceModelFactory = $marketplaceModelFactory;
        $this->marketplaceResource = $marketplaceResource;
    }

    public function execute()
    {
        $marketplaceId = $this->getRequest()->getParam('marketplace_id');
        $accountId = $this->getRequest()->getParam('account_id');
        $parentCategoryId = $this->getRequest()->getParam('parent_category_id');
        $categoryType = $this->getRequest()->getParam('category_type');

        $ebayCategoryTypes = $this->componentEbayCategory->getEbayCategoryTypes();
        $storeCategoryTypes = $this->componentEbayCategory->getStoreCategoryTypes();

        $data = [];

        if (
            (in_array($categoryType, $ebayCategoryTypes) && $marketplaceId === null) ||
            (in_array($categoryType, $storeCategoryTypes) && $accountId === null)
        ) {
            $this->setJsonContent($data);

            return $this->getResult();
        }

        if (in_array($categoryType, $ebayCategoryTypes)) {
            $marketplaceModel = $this->marketplaceModelFactory->create();

            $this->marketplaceResource->load($marketplaceModel, $marketplaceId);

            if ($marketplaceModel->getId()) {
                $data = $marketplaceModel->getChildObject()
                                         ->getChildCategories($parentCategoryId);
            }
        } elseif (in_array($categoryType, $storeCategoryTypes)) {
            $connection = $this->resourceConnection->getConnection();
            $tableAccountStoreCategories = $this->getHelper('Module_Database_Structure')->getTableNameWithPrefix(
                'm2epro_ebay_account_store_category'
            );

            $dbSelect = $connection->select()
                                   ->from($tableAccountStoreCategories, '*')
                                   ->where('`account_id` = ?', (int)$accountId)
                                   ->where('`parent_id` = ?', $parentCategoryId)
                                   ->order(['sorder ASC']);

            $data = $connection->fetchAll($dbSelect);
        }

        $this->setJsonContent($data);

        return $this->getResult();
    }
}
