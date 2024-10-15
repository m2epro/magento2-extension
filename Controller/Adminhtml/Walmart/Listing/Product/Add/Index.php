<?php

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Listing\Product\Add;

use Ess\M2ePro\Block\Adminhtml\Walmart\Listing\Product\Add\SourceMode as SourceModeBlock;

class Index extends \Ess\M2ePro\Controller\Adminhtml\Walmart\Listing\Product\AbstractAdd
{
    /** @var \Ess\M2ePro\Block\Adminhtml\Magento\Product\Rule\ViewStateFactory */
    private $viewStateFactory;
    /** @var \Ess\M2ePro\Block\Adminhtml\Magento\Product\Rule\ViewState\Manager */
    private $viewStateManager;
    /** @var \Ess\M2ePro\Model\Magento\Product\RuleFactory */
    private $ruleFactory;
    /** @var \Ess\M2ePro\Helper\Data\Session */
    private $sessionHelper;
    /** @var \Ess\M2ePro\Helper\Data\GlobalData */
    private $globalData;
    /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\Collection\Factory */
    private $listingProductCollectionFactory;

    public function __construct(
        \Ess\M2ePro\Block\Adminhtml\Magento\Product\Rule\ViewStateFactory $viewStateFactory,
        \Ess\M2ePro\Block\Adminhtml\Magento\Product\Rule\ViewState\Manager $viewStateManager,
        \Ess\M2ePro\Model\Magento\Product\RuleFactory $ruleFactory,
        \Ess\M2ePro\Helper\Data\Session $sessionHelper,
        \Ess\M2ePro\Helper\Data\GlobalData $globalData,
        \Ess\M2ePro\Model\ResourceModel\Listing\Product\Collection\Factory $listingProductCollectionFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($walmartFactory, $context);

        $this->sessionHelper = $sessionHelper;
        $this->globalData = $globalData;
        $this->listingProductCollectionFactory = $listingProductCollectionFactory;
        $this->viewStateFactory = $viewStateFactory;
        $this->viewStateManager = $viewStateManager;
        $this->ruleFactory = $ruleFactory;
    }

    public function execute()
    {
        if ($this->getRequest()->getParam('id') === null) {
            return $this->_redirect('*/walmart_listing/index');
        }

        if ($this->getRequest()->getParam('clear')) {
            $this->clearSession();
            $this->getRequest()->setParam('clear', null);

            return $this->_redirect('*/*/*', ['_current' => true]);
        }

        $listing = $this->getListing();

        if ($source = $this->getRequest()->getParam('source')) {
            $listing->setSetting('additional_data', 'source', $source)->save();
        }

        $this->globalData->setValue('listing_for_products_add', $listing);

        $step = (int)$this->getRequest()->getParam('step');

        switch ($step) {
            case 1:
                $this->getResultPage()->getConfig()->getTitle()->prepend($this->__('Add Magento Products'));
                $this->sourceMode();
                break;
            case 2:
                $this->getResultPage()->getConfig()->getTitle()->prepend($this->__('Select Magento Products'));

                switch ($this->getRequest()->getParam('source')) {
                    case SourceModeBlock::MODE_PRODUCT:
                        $this->stepOneSourceProducts();
                        break;

                    case SourceModeBlock::MODE_CATEGORY:
                        $this->stepOneSourceCategories();
                        break;
                    default:
                        return $this->_redirect('*/*/index', ['_current' => true, 'step' => 1]);
                }
                break;
            case 3:
                $this->processStep3($listing->getMarketplace());
                break;
            case 4:
                $this->review($listing->getMarketplace());
                break;
            default:
                return $this->_redirect('*/*/index', ['_current' => true, 'step' => 1]);
        }

        return $this->getResult();
    }

    public function sourceMode()
    {
        if ($this->getRequest()->isPost()) {
            $source = $this->getRequest()->getParam('source');

            if (!empty($source)) {
                return $this->_redirect('*/*/index', ['_current' => true, 'step' => 2, 'source' => $source]);
            }

            return $this->_redirect('*/*/index', ['clear' => 'yes']);
        }

        $this->addContent(
            $this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\Walmart\Listing\Product\Add\SourceMode::class)
        );
        $this->setPageHelpLink('adding-magento-products-to-listing');
    }

    public function stepOneSourceProducts()
    {
        if ($this->getRequest()->getParam('id') === null) {
            return $this->_redirect('*/walmart_listing/index');
        }

        if ($this->getRequest()->getParam('clear')) {
            $this->clearSession();
            $this->getRequest()->setParam('clear', null);

            return $this->_redirect('*/*/*', ['_current' => true]);
        }

        $this->sessionHelper->setValue('temp_products', []);
        $this->sessionHelper->setValue(
            'products_source',
            SourceModeBlock::MODE_PRODUCT
        );

        $this->setRuleModel();

        $prefix = $this->getHideProductsInOtherListingsPrefix();

        if ($this->getRequest()->isPost()) {
            $hideProductsOtherParam = $this->getRequest()->getPost('hide_products_others_listings', 1);
            $this->sessionHelper->setValue($prefix, $hideProductsOtherParam);
        }

        $this->globalData->setValue('hide_products_others_listings_prefix', $prefix);

        if ($this->getRequest()->isXmlHttpRequest()) {
            $grid = $this->getLayout()
                         ->createBlock(
                             \Ess\M2ePro\Block\Adminhtml\Walmart\Listing\Product\Add\SourceMode\Product\Grid::class
                         );

            $this->setAjaxContent($grid->toHtml());

            return;
        }

        $this->setPageHelpLink('adding-products-manually');

        $this->addContent(
            $this->getLayout()
                 ->createBlock(\Ess\M2ePro\Block\Adminhtml\Walmart\Listing\Product\Add\SourceMode\Product::class)
        );
    }

    public function stepOneSourceCategories()
    {
        if ($this->getRequest()->getParam('id') === null) {
            return $this->_redirect('*/walmart_listing/index');
        }

        if ($this->getRequest()->getParam('clear')) {
            $this->clearSession();
            $this->getRequest()->setParam('clear', null);

            return $this->_redirect('*/*/*', ['_current' => true]);
        }

        $this->sessionHelper->setValue('temp_products', []);
        $this->sessionHelper->setValue(
            'products_source',
            SourceModeBlock::MODE_CATEGORY
        );

        $this->setRuleModel();

        $prefix = $this->getHideProductsInOtherListingsPrefix();

        if ($this->getRequest()->isPost()) {
            $hideProductsOtherParam = $this->getRequest()->getPost('hide_products_others_listings', 1);
            $this->sessionHelper->setValue($prefix, $hideProductsOtherParam);
        }

        $this->globalData->setValue('hide_products_others_listings_prefix', $prefix);

        $tempSession = $this->getSessionValue('source_categories');
        $selectedProductsIds = !isset($tempSession['products_ids']) ? [] : $tempSession['products_ids'];

        if ($this->getRequest()->isXmlHttpRequest()) {
            if ($this->getRequest()->getParam('current_category_id')) {
                $this->setSessionValue('current_category_id', $this->getRequest()->getParam('current_category_id'));
            }

            /** @var SourceModeBlock\Category\Grid $grid */
            $grid = $this->getLayout()
                         ->createBlock(
                             \Ess\M2ePro\Block\Adminhtml\Walmart\Listing\Product\Add\SourceMode\Category\Grid::class
                         );

            $grid->setSelectedIds($selectedProductsIds);
            $grid->setCurrentCategoryId($this->getSessionValue('current_category_id'));

            $this->setAjaxContent($grid->toHtml());

            return;
        }

        $this->setPageHelpLink('adding-products-manually');

        $gridContainer = $this->getLayout()
                              ->createBlock(
                                  \Ess\M2ePro\Block\Adminhtml\Walmart\Listing\Product\Add\SourceMode\Category::class
                              );
        $this->addContent($gridContainer);

        /** @var SourceModeBlock\Category\Tree $treeBlock */
        $treeBlock = $this->getLayout()
                          ->createBlock(
                              \Ess\M2ePro\Block\Adminhtml\Walmart\Listing\Product\Add\SourceMode\Category\Tree::class,
                              '',
                              [
                                  'data' => [
                                      'tree_settings' => [
                                          'show_products_amount' => true,
                                          'hide_products_this_listing' => true,
                                      ],
                                  ],
                              ]
                          );

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
    }

    private function processStep3(\Ess\M2ePro\Model\Marketplace $marketplace): void
    {
        if (
            !$marketplace->getChildObject()
                         ->isSupportedProductType()
        ) {
            $this->review($marketplace);

            return;
        }

        $this->addProductTypeView();
    }

    private function addProductTypeView(): void
    {
        $listingProductsIds = $this->getAddedListingProductsIds();

        if (empty($listingProductsIds)) {
            $this->_redirect('*/walmart_listing/view', ['id' => $this->getRequest()->getParam('id')]);

            return;
        }

        $this->getResultPage()->getConfig()->getTitle()->prepend((string)__('Set Product Type'));

        $this->addContent(
            $this->getLayout()
                 ->createBlock(\Ess\M2ePro\Block\Adminhtml\Walmart\Listing\Product\Add\ProductType::class)
        );
    }

    protected function getAddedListingProductsIds()
    {
        $listingProductsIds = $this->sessionHelper->getValue('temp_products');

        if (empty($listingProductsIds)) {
            $listingProductsIds = $this->getListing()->getSetting('additional_data', 'adding_listing_products_ids');
        } else {
            $this->getListing()
                 ->setSetting('additional_data', 'adding_listing_products_ids', $listingProductsIds)->save();

            $this->sessionHelper->setValue('temp_products', []);
        }

        return $listingProductsIds;
    }

    private function review(\Ess\M2ePro\Model\Marketplace $marketplace): void
    {
        $listingId = $this->getRequest()->getParam('id');
        $additionalData = $this->getListing()->getSettings('additional_data');

        if (empty($additionalData['adding_listing_products_ids'])) {
            $this->_redirect('*/walmart_listing/view', ['id' => $listingId]);

            return;
        }

        if ($marketplace->getChildObject()->isSupportedProductType()) {
            $this->removeProductsWithoutProductTypes($additionalData['adding_listing_products_ids']);
        }

        //-- Remove successfully moved Unmanaged items
        if (isset($additionalData['source']) && $additionalData['source'] == SourceModeBlock::MODE_OTHER) {
            $this->deleteListingOthers();
        }
        //--

        $this->sessionHelper->setValue('products_source', '');

        $additionalData = $this->getListing()->getSettings('additional_data');

        $this->addVariationAttributes($additionalData['adding_listing_products_ids']);

        $this->sessionHelper->setValue(
            'added_products_ids',
            $additionalData['adding_listing_products_ids']
        );

        $this->getResultPage()->getConfig()->getTitle()->prepend($this->__('Congratulations'));

        /** @var \Ess\M2ePro\Block\Adminhtml\Walmart\Listing\Product\Add\Review $blockReview */
        $blockReview = $this->getLayout()
                            ->createBlock(\Ess\M2ePro\Block\Adminhtml\Walmart\Listing\Product\Add\Review::class);

        if (isset($additionalData['source'])) {
            $blockReview->setSource($additionalData['source']);
        }

        $this->clear();

        $this->addContent($blockReview);
    }

    private function removeProductsWithoutProductTypes(array $addingListingProductsIds): void
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\Collection $collection */
        $collection = $this->listingProductCollectionFactory
            ->create(['childMode' => \Ess\M2ePro\Helper\Component\Walmart::NICK]);
        $collection->getSelect()->reset(\Magento\Framework\DB\Select::COLUMNS);
        $collection->getSelect()->columns([
            'id' => 'main_table.id',
        ]);
        $collection->getSelect()->where(
            "`main_table`.`id` IN (?) AND `second_table`.`product_type_id` IS NULL",
            $addingListingProductsIds
        );

        $failedProductsIds = $collection->getColumnValues('id');
        $this->deleteListingProducts($failedProductsIds);
    }

    private function deleteListingOthers()
    {
        $listingProductsIds = $this->getListing()->getSetting('additional_data', 'adding_listing_products_ids');
        if (empty($listingProductsIds)) {
            return;
        }

        $otherProductsIds = [];

        /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\Collection $collection */
        $collection = $this->walmartFactory->getObject('Listing\Product')->getCollection();
        $collection->addFieldToFilter('id', ['in' => $listingProductsIds]);
        foreach ($collection->getItems() as $listingProduct) {
            /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */
            $otherProductsIds[] = (int)$listingProduct->getSetting(
                'additional_data',
                $listingProduct::MOVING_LISTING_OTHER_SOURCE_KEY
            );
        }

        if (empty($otherProductsIds)) {
            return;
        }

        /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Other\Collection $collection */
        $collection = $this->walmartFactory->getObject('Listing\Other')->getCollection();
        $collection->addFieldToFilter('id', ['in' => $otherProductsIds]);
        foreach ($collection->getItems() as $listingOther) {
            /** @var \Ess\M2ePro\Model\Listing\Other $listingOther */
            $listingOther->moveToListingSucceed();
        }
    }

    private function addVariationAttributes($productsIds)
    {
        $listingProductCollection = $this->listingProductCollectionFactory
            ->create(['childMode' => \Ess\M2ePro\Helper\Component\Walmart::NICK]);
        $listingProductCollection->addFieldToFilter('listing_product_id', ['in' => $productsIds]);
        $listingProductCollection->addFieldToFilter('is_variation_product', 1);

        /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */
        foreach ($listingProductCollection as $listingProduct) {
            $listingProduct->getChildObject()->addVariationAttributes();
        }
    }

    protected function getHideProductsInOtherListingsPrefix()
    {
        $id = $this->getRequest()->getParam('id');

        $prefix = 'walmart_hide_products_others_listings_';
        $prefix .= $id === null ? 'add' : $id;
        $prefix .= '_listing_product';

        return $prefix;
    }

    private function setRuleModel(): void
    {
        $viewKey = $this->buildPrefix(
            'walmart_rule_add_listing_product_' . \Ess\M2ePro\Model\Magento\Product\Rule::NICK
        );
        $getRuleBySessionData = function () {
            return $this->createRuleBySessionData();
        };
        $ruleModel = $this->viewStateManager->getRuleWithViewState(
            $this->viewStateFactory->create($viewKey),
            \Ess\M2ePro\Model\Magento\Product\Rule::NICK,
            $getRuleBySessionData,
            $this->getStoreId()
        );

        $this->globalData->setValue('rule_model', $ruleModel);
    }

    private function createRuleBySessionData(): \Ess\M2ePro\Model\Magento\Product\Rule
    {
        $prefix = $this->buildPrefix('walmart_rule_add_listing_product');
        $this->globalData->setValue('rule_prefix', $prefix);

        $ruleModel = $this->ruleFactory->create($prefix, $this->getStoreId());

        $ruleParam = $this->getRequest()->getPost('rule');
        if (!empty($ruleParam)) {
            $this->sessionHelper->setValue(
                $prefix,
                $ruleModel->getSerializedFromPost($this->getRequest()->getPostValue())
            );
        } elseif ($ruleParam !== null) {
            $this->sessionHelper->setValue($prefix, []);
        }

        $sessionRuleData = $this->sessionHelper->getValue($prefix);
        if (!empty($sessionRuleData)) {
            $ruleModel->loadFromSerialized($sessionRuleData);
        }

        return $ruleModel;
    }

    private function buildPrefix(string $root): string
    {
        $listing = $this->getListingDataFromGlobalData();

        return $root . (isset($listing['id']) ? '_' . $listing['id'] : '');
    }

    private function getStoreId(): int
    {
        $listing = $this->getListingDataFromGlobalData();

        if (empty($listing['store_id'])) {
            return 0;
        }

        return (int)$listing['store_id'];
    }

    private function getListingDataFromGlobalData(): array
    {
        return $this->globalData
            ->getValue('listing_for_products_add')
            ->getData();
    }

    /**
     * @param array $additionalData
     *
     * @return void
     */
    protected function cancelProductsAdding(array $additionalData)
    {
        $this->endWizard();
        $this->sessionHelper->setValue('products_source', '');
        $this->sessionHelper->setValue('added_products_ids', []);

        if (
            !empty($additionalData['adding_listing_products_ids'])
            && is_array($additionalData['adding_listing_products_ids'])
        ) {
            $this->deleteListingProducts($additionalData['adding_listing_products_ids']);
        }
    }

    public function clear()
    {
        $this->clearSession();

        if ($additionalData = $this->getListing()->getSettings('additional_data')) {
            $additionalData['adding_listing_products_ids'] = [];
            unset($additionalData['source']);
            unset($additionalData['adding_product_type_data']);
            $this->getListing()->setSettings('additional_data', $additionalData)->save();
        }
    }
}
