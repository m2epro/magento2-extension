<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Listing\Product\Add;

use Ess\M2ePro\Block\Adminhtml\Walmart\Listing\Product\Add\SourceMode as SourceModeBlock;
use Ess\M2ePro\Controller\Adminhtml\Context;

class Index extends \Ess\M2ePro\Controller\Adminhtml\Walmart\Listing\Product\Add
{
    /** @var \Ess\M2ePro\Helper\Data\Session */
    private $sessionHelper;

    /** @var \Ess\M2ePro\Helper\Data\GlobalData */
    private $globalData;

    /** @var \Ess\M2ePro\Model\ResourceModel\Walmart\Listing\Product\CollectionFactory */
    private $listingProductCollectionFactory;

    public function __construct(
        \Ess\M2ePro\Helper\Data\Session $sessionHelper,
        \Ess\M2ePro\Helper\Data\GlobalData $globalData,
        \Ess\M2ePro\Model\ResourceModel\Walmart\Listing\Product\CollectionFactory $listingProductCollectionFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory,
        Context $context
    ) {
        parent::__construct($walmartFactory, $context);

        $this->sessionHelper = $sessionHelper;
        $this->globalData = $globalData;
        $this->listingProductCollectionFactory = $listingProductCollectionFactory;
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
                        return $this->_redirect('*/*/index', ['_current' => true,'step' => 1]);
                }
                break;
            case 3:
                $this->addCategoryTemplateView();
                break;
            case 4:
                $this->review();
                break;
            default:
                return $this->_redirect('*/*/index', ['_current' => true,'step' => 1]);
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

            return $this->_redirect('*/*/index', ['clear'=>'yes']);
        }

        $this->addContent(
            $this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\Walmart\Listing\Product\Add\SourceMode::class)
        );
        $this->setPageHelpLink('x/PQBhAQ');
    }

    //########################################

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

        $this->setRuleData('walmart_rule_add_listing_product');

        $prefix = $this->getHideProductsInOtherListingsPrefix();

        if ($this->getRequest()->isPost()) {
            $hideProductsOtherParam = $this->getRequest()->getPost('hide_products_others_listings', 1);
            $this->sessionHelper->setValue($prefix, $hideProductsOtherParam);
        }

        $this->globalData->setValue('hide_products_others_listings_prefix', $prefix);

        if ($this->getRequest()->isXmlHttpRequest()) {
            $grid = $this->getLayout()
                 ->createBlock(\Ess\M2ePro\Block\Adminhtml\Walmart\Listing\Product\Add\SourceMode\Product\Grid::class);

            $this->setAjaxContent($grid->toHtml());
            return;
        }

        $this->setPageHelpLink('x/PwBhAQ');

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

        $this->setRuleData('walmart_rule_add_listing_product');

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
                 ->createBlock(\Ess\M2ePro\Block\Adminhtml\Walmart\Listing\Product\Add\SourceMode\Category\Grid::class);

            $grid->setSelectedIds($selectedProductsIds);
            $grid->setCurrentCategoryId($this->getSessionValue('current_category_id'));

            $this->setAjaxContent($grid->toHtml());

            return;
        }

        $this->setPageHelpLink('x/PwBhAQ');

        $gridContainer = $this->getLayout()
                      ->createBlock(\Ess\M2ePro\Block\Adminhtml\Walmart\Listing\Product\Add\SourceMode\Category::class);
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
                                      'hide_products_this_listing' => true
                                      ]
                                  ]
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

    //########################################

    protected function addCategoryTemplateView()
    {
        $listingProductsIds = $this->getAddedListingProductsIds();

        if (empty($listingProductsIds)) {
            $this->_redirect('*/walmart_listing/view', ['id' => $this->getRequest()->getParam('id')]);
            return;
        }

        $this->setPageHelpLink('x/bf1IB');

        $this->getResultPage()->getConfig()->getTitle()->prepend($this->__('Set Category Policy'));

        $this->addContent(
            $this->getLayout()
                 ->createBlock(\Ess\M2ePro\Block\Adminhtml\Walmart\Listing\Product\Add\CategoryTemplate::class)
        );
    }

    //----------------------------------------

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

    //########################################

    protected function review()
    {
        $listingId = $this->getRequest()->getParam('id');
        $additionalData = $this->getListing()->getSettings('additional_data');

        if (empty($additionalData['adding_listing_products_ids'])) {
            return $this->_redirect('*/walmart_listing/view', ['id' => $listingId]);
        }

        /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\Collection $collection */
        $collection = $this->walmartFactory->getObject('Listing\Product')->getCollection();
        $collection->getSelect()->reset(\Magento\Framework\DB\Select::COLUMNS);
        $collection->getSelect()->columns([
            'id' => 'main_table.id'
        ]);
        $collection->getSelect()->where(
            "`main_table`.`id` IN (?) AND `second_table`.`template_category_id` IS NULL",
            $additionalData['adding_listing_products_ids']
        );

        $failedProductsIds = $collection->getColumnValues('id');
        $this->deleteListingProducts($failedProductsIds);

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
        $listingProductCollection = $this->listingProductCollectionFactory->create();
        $listingProductCollection->addFieldToFilter('listing_product_id', ['in' => $productsIds]);
        $listingProductCollection->addFieldToFilter('is_variation_product', 1);

        /** @var \Ess\M2ePro\Model\Walmart\Listing\Product $listingProduct */
        foreach ($listingProductCollection as $listingProduct) {
            $listingProduct->addVariationAttributes();
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

    protected function setRuleData($prefix)
    {
        $listingData = $this->globalData
                            ->getValue('listing_for_products_add')
                            ->getData();

        $storeId = isset($listingData['store_id']) ? (int)$listingData['store_id'] : 0;
        $prefix .= isset($listingData['id']) ? '_'.$listingData['id'] : '';
        $this->globalData->setValue('rule_prefix', $prefix);

        $ruleModel = $this->activeRecordFactory->getObject('Magento_Product_Rule')->setData(
            [
                'prefix' => $prefix,
                'store_id' => $storeId,
            ]
        );

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

        $this->globalData->setValue('rule_model', $ruleModel);
    }

    public function clear()
    {
        $this->clearSession();

        if ($additionalData = $this->getListing()->getSettings('additional_data')) {
            $additionalData['adding_listing_products_ids'] = [];
            unset($additionalData['source']);
            unset($additionalData['adding_category_templates_data']);
            $this->getListing()->setSettings('additional_data', $additionalData)->save();
        }
    }
}
