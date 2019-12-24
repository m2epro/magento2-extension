<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Listing\Product\Add;

use Ess\M2ePro\Block\Adminhtml\Walmart\Listing\Product\Add\SourceMode as SourceModeBlock;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Walmart\Listing\Product\Add\Index
 */
class Index extends \Ess\M2ePro\Controller\Adminhtml\Walmart\Listing\Product\Add
{
    //########################################

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

        $this->getHelper('Data\GlobalData')->setValue('listing_for_products_add', $listing);

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
            // ....
            default:
                return $this->_redirect('*/*/index', ['_current' => true,'step' => 1]);
        }

        return $this->getResult();
    }

    //########################################

    public function sourceMode()
    {
        if ($this->getRequest()->isPost()) {
            $source = $this->getRequest()->getParam('source');

            if (!empty($source)) {
                return $this->_redirect('*/*/index', ['_current' => true, 'step' => 2, 'source' => $source]);
            }

            return $this->_redirect('*/*/index', ['clear'=>'yes']);
        }

        $this->addContent($this->createBlock('Walmart_Listing_Product_Add_SourceMode'));
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

        $this->getHelper('Data\Session')->setValue('temp_products', []);
        $this->getHelper('Data\Session')->setValue(
            'products_source',
            SourceModeBlock::MODE_PRODUCT
        );

        $this->setRuleData('walmart_rule_add_listing_product');

        $prefix = $this->getHideProductsInOtherListingsPrefix();

        if ($this->getRequest()->isPost()) {
            $hideProductsOtherParam = $this->getRequest()->getPost('hide_products_others_listings', 1);
            $this->getHelper('Data\Session')->setValue($prefix, $hideProductsOtherParam);
        }

        $this->getHelper('Data\GlobalData')->setValue('hide_products_others_listings_prefix', $prefix);

        if ($this->getRequest()->isXmlHttpRequest()) {
            $grid = $this->createBlock('Walmart_Listing_Product_Add_SourceMode_Product_Grid');

            $this->setAjaxContent($grid->toHtml());
            return;
        }

        $this->setPageHelpLink('x/PwBhAQ');

        $this->addContent($this->createBlock('Walmart_Listing_Product_Add_SourceMode_Product'));
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

        $this->getHelper('Data\Session')->setValue('temp_products', []);
        $this->getHelper('Data\Session')->setValue(
            'products_source',
            SourceModeBlock::MODE_CATEGORY
        );

        $this->setRuleData('walmart_rule_add_listing_product');

        $prefix = $this->getHideProductsInOtherListingsPrefix();

        if ($this->getRequest()->isPost()) {
            $hideProductsOtherParam = $this->getRequest()->getPost('hide_products_others_listings', 1);
            $this->getHelper('Data\Session')->setValue($prefix, $hideProductsOtherParam);
        }

        $this->getHelper('Data\GlobalData')->setValue('hide_products_others_listings_prefix', $prefix);

        $tempSession = $this->getSessionValue('source_categories');
        $selectedProductsIds = !isset($tempSession['products_ids']) ? [] : $tempSession['products_ids'];

        if ($this->getRequest()->isXmlHttpRequest()) {
            if ($this->getRequest()->getParam('current_category_id')) {
                $this->setSessionValue('current_category_id', $this->getRequest()->getParam('current_category_id'));
            }

            /** @var $grid SourceModeBlock\Category\Grid */
            $grid = $this->createBlock('Walmart_Listing_Product_Add_SourceMode_Category_Grid');

            $grid->setSelectedIds($selectedProductsIds);
            $grid->setCurrentCategoryId($this->getSessionValue('current_category_id'));

            $this->setAjaxContent($grid->toHtml());

            return;
        }

        $this->setPageHelpLink('x/PwBhAQ');

        $gridContainer = $this->createBlock('Walmart_Listing_Product_Add_SourceMode_Category');
        $this->addContent($gridContainer);

        /** @var $treeBlock SourceModeBlock\Category\Tree */
        $treeBlock = $this->createBlock('Walmart_Listing_Product_Add_SourceMode_Category_Tree', '', [
            'data' => [
                'tree_settings' => [
                    'show_products_amount' => true,
                    'hide_products_this_listing' => true
                ]
            ]
        ]);

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

        $this->setPageHelpLink('x/RQBhAQ');

        $this->getResultPage()->getConfig()->getTitle()->prepend($this->__('Set Category Policy'));

        $this->addContent($this->createBlock('Walmart_Listing_Product_Add_CategoryTemplate'));
    }

    //----------------------------------------

    protected function getAddedListingProductsIds()
    {
        $listingProductsIds = $this->getHelper('Data\Session')->getValue('temp_products');

        if (empty($listingProductsIds)) {
            $listingProductsIds = $this->getListing()->getSetting('additional_data', 'adding_listing_products_ids');
        } else {
            $this->getListing()
                ->setSetting('additional_data', 'adding_listing_products_ids', $listingProductsIds)->save();

            $this->getHelper('Data\Session')->setValue('temp_products', []);
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
        $collection->getSelect()->reset(\Zend_Db_Select::COLUMNS);
        $collection->getSelect()->columns([
            'id' => 'main_table.id'
        ]);
        $collection->getSelect()->where(
            "`main_table`.`id` IN (?) AND `second_table`.`template_category_id` IS NULL",
            $additionalData['adding_listing_products_ids']
        );

        $failedProductsIds = $collection->getColumnValues('id');
        $this->deleteListingProducts($failedProductsIds);

        //-- Remove successfully moved 3rd party items
        if (isset($additionalData['source']) && $additionalData['source'] == SourceModeBlock::MODE_OTHER) {
            $this->deleteListingOthers();
        }
        //--

        $this->getHelper('Data\Session')->setValue('products_source', '');

        $additionalData = $this->getListing()->getSettings('additional_data');

        $this->getHelper('Data\Session')->setValue(
            'added_products_ids',
            $additionalData['adding_listing_products_ids']
        );

        $this->getResultPage()->getConfig()->getTitle()->prepend($this->__('Congratulations'));

        /** @var \Ess\M2ePro\Block\Adminhtml\Walmart\Listing\Product\Add\Review $blockReview */
        $blockReview = $this->createBlock('Walmart_Listing_Product_Add_Review');

        if (isset($additionalData['source'])) {
            $blockReview->setSource($additionalData['source']);
        }

        $this->clear();

        $this->addContent($blockReview);
    }

    //########################################

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

    //########################################

    protected function getHideProductsInOtherListingsPrefix()
    {
        $id = $this->getRequest()->getParam('id');

        $prefix = 'walmart_hide_products_others_listings_';
        $prefix .= $id === null ? 'add' : $id;
        $prefix .= '_listing_product';

        return $prefix;
    }

    //########################################

    protected function filterProductsForSearch($productsIds)
    {
        $productsIds = $this->getHelper('Component_Walmart_Variation')->filterProductsByStatus($productsIds);

        $unsetProducts = $this->getLockedProductsInAction($productsIds);
        $unsetProducts = array_unique($unsetProducts);

        foreach ($unsetProducts as $id) {
            $key = array_search($id, $productsIds);
            unset($productsIds[$key]);
        }

        return $productsIds;
    }

    //########################################

    protected function getLockedProductsInAction($productsIds)
    {
        $connection = $this->resourceConnection->getConnection();
        $table = $this->getHelper('Module_Database_Structure')->getTableNameWithPrefix('m2epro_processing_lock');

        $select = $connection->select();
        $select->from(['pl' => $table], ['object_id'])
            ->where('model_name = "Listing\Product"')
            ->where('object_id IN (?)', $productsIds)
            ->where('tag = "in_action"');

        return $connection->fetchCol($select);
    }

    //########################################

    protected function setRuleData($prefix)
    {
        $listingData = $this->getHelper('Data\GlobalData')
                            ->getValue('listing_for_products_add')
                            ->getData();

        $storeId = isset($listingData['store_id']) ? (int)$listingData['store_id'] : 0;
        $prefix .= isset($listingData['id']) ? '_'.$listingData['id'] : '';
        $this->getHelper('Data\GlobalData')->setValue('rule_prefix', $prefix);

        $ruleModel = $this->activeRecordFactory->getObject('Magento_Product_Rule')->setData(
            [
                'prefix' => $prefix,
                'store_id' => $storeId,
            ]
        );

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

        $this->getHelper('Data\GlobalData')->setValue('rule_model', $ruleModel);
    }

    //########################################

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

    //########################################
}
