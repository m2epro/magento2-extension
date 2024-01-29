<?php

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Product\Add;

class Index extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Product\Add
{
    /** @var \Ess\M2ePro\Block\Adminhtml\Magento\Product\Rule\ViewStateFactory */
    private $viewStateFactory;
    /** @var \Ess\M2ePro\Block\Adminhtml\Magento\Product\Rule\ViewState\Manager */
    private $viewStateManager;
    /** @var \Ess\M2ePro\Model\Magento\Product\RuleFactory */
    private $ruleFactory;
    /** @var \Ess\M2ePro\Helper\Data\GlobalData */
    private $globalData;

    public function __construct(
        \Ess\M2ePro\Block\Adminhtml\Magento\Product\Rule\ViewStateFactory $viewStateFactory,
        \Ess\M2ePro\Block\Adminhtml\Magento\Product\Rule\ViewState\Manager $viewStateManager,
        \Ess\M2ePro\Model\Magento\Product\RuleFactory $ruleFactory,
        \Ess\M2ePro\Helper\Data\GlobalData $globalData,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($ebayFactory, $context);

        $this->viewStateFactory = $viewStateFactory;
        $this->viewStateManager = $viewStateManager;
        $this->globalData = $globalData;
        $this->ruleFactory = $ruleFactory;
    }

    public function execute()
    {
        if (!$listingId = $this->getRequest()->getParam('id')) {
            throw new \Ess\M2ePro\Model\Exception('Listing is not defined');
        }

        if ((bool)$this->getRequest()->getParam('clear', false)) {
            $this->clear();
            $this->getRequest()->setParam('clear', null);

            return $this->_redirect('*/*/', ['_current' => true, 'step' => 1]);
        }

        $listing = $this->getListing();

        $this->globalData->setValue('listing_for_products_add', $listing);

        $step = (int)$this->getRequest()->getParam('step');

        switch ($step) {
            case 1:
                return $this->stepOne();
        }

        return $this->_redirect('*/*/index', ['_current' => true, 'step' => 1]);
    }

    private function stepOne()
    {
        switch ($this->getRequest()->getParam('source')) {
            case \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Product\Add\SourceMode::MODE_PRODUCT:
                return $this->stepOneSourceProducts();

            case \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Product\Add\SourceMode::MODE_CATEGORY:
                return $this->stepOneSourceCategories();
        }

        return $this->_redirect('*/*/sourceMode', ['_current' => true]);
    }

    private function stepOneSourceProducts()
    {
        /** @var \Ess\M2ePro\Model\Listing $listing */
        $listing = $this->globalData->getValue('listing_for_products_add');
        $ids = $listing->getChildObject()->getAddedListingProductsIds();

        if (!empty($ids)) {
            if ($this->getRequest()->isXmlHttpRequest()) {
                $this->setJsonContent([
                    'ajaxExpired' => 1,
                    'ajaxRedirect' => $this->getUrl('*/*/index', ['_current' => true, 'step' => 1]),
                ]);

                return $this->getResult();
            } else {
                return $this->_redirect(
                    '*/ebay_listing_product_category_settings/',
                    ['_current' => true, 'step' => 1]
                );
            }
        }

        $this->setRuleModel();

        // Set Hide Products In Other Listings
        // ---------------------------------------
        $prefix = $this->getHideProductsInOtherListingsPrefix();

        if ($this->getRequest()->isPost()) {
            $hideProductsOtherParam = $this->getRequest()->getPost('hide_products_others_listings', 1);
            $this->getHelper('Data\Session')->setValue($prefix, $hideProductsOtherParam);
        }

        $this->globalData->setValue('hide_products_others_listings_prefix', $prefix);
        // ---------------------------------------

        if ($this->getRequest()->isXmlHttpRequest()) {
            $this->setAjaxContent(
                $this->getLayout()
                     ->createBlock(\Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Product\Add\Product\Grid::class)
                     ->toHtml()
            );

            return $this->getResult();
        }

        $this->setPageHelpLink('display/AmazonMagentoV6X/Add+Magento+Products');

        $this->getResultPage()->getConfig()->getTitle()->prepend($this->__('Select Magento Products'));
        $this->addContent($this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Product\Add::class));

        return $this->getResult();
    }

    private function stepOneSourceCategories()
    {
        $this->setWizardStep('productSelection');

        $this->setRuleModel();

        // Set Hide Products In Other Listings
        // ---------------------------------------
        $prefix = $this->getHideProductsInOtherListingsPrefix();

        if ($this->getRequest()->isPost()) {
            $hideProductsOtherParam = $this->getRequest()->getPost('hide_products_others_listings', 1);
            $this->getHelper('Data\Session')->setValue($prefix, $hideProductsOtherParam);
        }

        $this->globalData->setValue('hide_products_others_listings_prefix', $prefix);
        // ---------------------------------------

        $tempSession = $this->getSessionValue('source_categories');
        $selectedProductsIds = !isset($tempSession['products_ids']) ? [] : $tempSession['products_ids'];

        if ($this->getRequest()->isXmlHttpRequest()) {
            if ($this->getRequest()->getParam('current_category_id')) {
                $this->setSessionValue('current_category_id', $this->getRequest()->getParam('current_category_id'));
            }

            /** @var \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Product\Add\Category\Grid $grid */
            $grid = $this->getLayout()
                         ->createBlock(\Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Product\Add\Category\Grid::class);

            $grid->setSelectedIds($selectedProductsIds);
            $grid->setCurrentCategoryId($this->getSessionValue('current_category_id'));

            $this->setAjaxContent($grid->toHtml());

            return $this->getResult();
        }

        $this->setPageHelpLink('display/AmazonMagentoV6X/Add+Magento+Products');

        $this->getResultPage()->getConfig()->getTitle()->prepend($this->__('Select Magento Products'));

        $gridContainer = $this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Product\Add::class);
        $this->addContent($gridContainer);

        /** @var \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Product\Add\Category\Tree $treeBlock */
        $treeBlock = $this->getLayout()
                          ->createBlock(\Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Product\Add\Category\Tree::class);

        if ($this->getSessionValue('current_category_id') === null) {
            $currentNode = $treeBlock->getRoot()->getChildren()->getIterator()->current();
            if (!$currentNode) {
                throw new \Ess\M2ePro\Model\Exception('No Categories found');
            }
            $this->setSessionValue('current_category_id', $currentNode->getId());
        }

        $treeBlock->setGridId($gridContainer->getChildBlock('grid')->getId());
        $treeBlock->setSelectedIds($selectedProductsIds);
        $treeBlock->setCurrentNodeById($this->getSessionValue('current_category_id'));

        $gridContainer->getChildBlock('grid')->setTreeBlock($treeBlock);
        $gridContainer->getChildBlock('grid')->setSelectedIds($selectedProductsIds);
        $gridContainer->getChildBlock('grid')->setCurrentCategoryId($this->getSessionValue('current_category_id'));

        return $this->getResult();
    }

    // ---------------------------------------

    protected function getHideProductsInOtherListingsPrefix()
    {
        $id = $this->getRequest()->getParam('id');

        $prefix = 'ebay_hide_products_others_listings_';
        $prefix .= $id === null ? 'add' : $id;
        $prefix .= '_listing_product';

        return $prefix;
    }

    private function clear()
    {
        $this->getHelper('Data\Session')->getValue($this->sessionKey, true);

        $categorySettingsSessionKey = 'ebay_listing_product_category_settings';
        $this->getHelper('Data\Session')->getValue($categorySettingsSessionKey, true);

        $listingId = $this->getRequest()->getParam('id');
        $listing = $this->ebayFactory->getCachedObjectLoaded('Listing', $listingId);

        $listing->getChildObject()->setData(
            'product_add_ids',
            \Ess\M2ePro\Helper\Json::encode([])
        )->save();
    }

    // ---------------------------------------

    private function setRuleModel(): void
    {
        $viewKey = $this->buildPrefix('ebay_product_add_step_one_' . \Ess\M2ePro\Model\Magento\Product\Rule::NICK);
        $getRuleBySessionData = function () {
            return $this->createRuleBySessionData();
        };
        $ruleModel = $this->viewStateManager->getRuleWithViewState(
            $this->viewStateFactory->create($viewKey),
            \Ess\M2ePro\Model\Magento\Product\Rule::NICK,
            $this->getStoreId(),
            $getRuleBySessionData
        );

        $this->globalData->setValue('rule_model', $ruleModel);
    }

    private function createRuleBySessionData(): \Ess\M2ePro\Model\Magento\Product\Rule
    {
        $prefix = $this->buildPrefix('ebay_product_add_step_one');
        $this->globalData->setValue('rule_prefix', $prefix);

        $ruleModel = $this->ruleFactory->create($prefix, $this->getStoreId());

        $ruleParam = $this->getRequest()->getPost('rule');
        if (!empty($ruleParam)) {
            $this->getHelper('Data\Session')->setValue(
                $prefix,
                $ruleModel->getSerializedFromPost($this->getRequest()->getPostValue())
            );
        } elseif ($ruleParam !== null) {
            $this->getHelper('Data\Session')->setValue($prefix, []);
        }

        $sessionRuleData = $this->getHelper('Data\Session')->getValue($prefix);
        if (!empty($sessionRuleData)) {
            $ruleModel->loadFromSerialized($sessionRuleData);
        }

        return $ruleModel;
    }

    private function buildPrefix(string $root): string
    {
        $listing = $this->getListingFromGlobalData();

        return $root . (isset($listing['id']) ? '_' . $listing['id'] : '');
    }

    private function getStoreId(): int
    {
        $listing = $this->getListingFromGlobalData();

        if (empty($listing['store_id'])) {
            return 0;
        }

        return (int)$listing['store_id'];
    }

    private function getListingFromGlobalData(): ?\Ess\M2ePro\Model\Listing
    {
        return $this->globalData->getValue('listing_for_products_add');
    }
}
