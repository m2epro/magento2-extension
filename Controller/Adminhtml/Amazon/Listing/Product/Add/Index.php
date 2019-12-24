<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Add;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Add\Index
 */
class Index extends \Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Add
{
    //########################################

    public function execute()
    {
        if ($this->getRequest()->getParam('id') === null) {
            return $this->_redirect('*/amazon_listing/index');
        }

        if ($this->getRequest()->getParam('clear')) {
            $this->clearSession();
            $this->getRequest()->setParam('clear', null);
            return $this->_redirect('*/*/*', ['_current' => true]);
        }

        $listing = $this->getListing();

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
                    case \Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Product\Add\SourceMode::MODE_PRODUCT:
                        $this->stepOneSourceProducts();
                        break;

                    case \Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Product\Add\SourceMode::MODE_CATEGORY:
                        $this->stepOneSourceCategories();
                        break;
                    default:
                        return $this->_redirect('*/*/index', ['_current' => true,'step' => 1]);
                }
                break;
            case 3:
                $this->asinSearchView();
                break;
            case 4:
                $this->addNewAsinView();
                break;
            case 5:
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
        $this->setWizardStep('sourceMode');

        if ($this->getRequest()->isPost()) {
            $source = $this->getRequest()->getParam('source');

            if (!empty($source)) {
                return $this->_redirect('*/*/index', ['_current' => true, 'step' => 2, 'source' => $source]);
            }

            return $this->_redirect('*/*/index', ['clear'=>'yes']);
        }

        $this->addContent($this->createBlock('Amazon_Listing_Product_Add_SourceMode'));
        $this->setPageHelpLink('x/jgYtAQ');
    }

    //########################################

    public function stepOneSourceProducts()
    {
        $this->setWizardStep('productSelection');

        if ($this->getRequest()->getParam('id') === null) {
            return $this->_redirect('*/amazon_listing/index');
        }

        if ($this->getRequest()->getParam('clear')) {
            $this->clearSession();
            $this->getRequest()->setParam('clear', null);
            return $this->_redirect('*/*/*', ['_current' => true]);
        }

        $this->getHelper('Data\Session')->setValue('temp_products', []);
        $this->getHelper('Data\Session')->setValue(
            'products_source',
            \Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Product\Add\SourceMode::MODE_PRODUCT
        );

        $this->setRuleData('amazon_rule_add_listing_product');

        $prefix = $this->getHideProductsInOtherListingsPrefix();

        if ($this->getRequest()->isPost()) {
            $hideProductsOtherParam = $this->getRequest()->getPost('hide_products_others_listings', 1);
            $this->getHelper('Data\Session')->setValue($prefix, $hideProductsOtherParam);
        }

        $this->getHelper('Data\GlobalData')->setValue('hide_products_others_listings_prefix', $prefix);

        if ($this->getRequest()->isXmlHttpRequest()) {
            $grid = $this->createBlock('Amazon_Listing_Product_Add_SourceMode_Product_Grid');

            $this->setAjaxContent($grid->toHtml());
            return;
        }

        $this->setPageHelpLink('x/4wYtAQ');

        $this->addContent($this->createBlock('Amazon_Listing_Product_Add_SourceMode_Product'));
    }

    public function stepOneSourceCategories()
    {
        $this->setWizardStep('productSelection');

        if ($this->getRequest()->getParam('id') === null) {
            return $this->_redirect('*/amazon_listing/index');
        }

        if ($this->getRequest()->getParam('clear')) {
            $this->clearSession();
            $this->getRequest()->setParam('clear', null);
            return $this->_redirect('*/*/*', ['_current' => true]);
        }

        $this->getHelper('Data\Session')->setValue('temp_products', []);
        $this->getHelper('Data\Session')->setValue(
            'products_source',
            \Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Product\Add\SourceMode::MODE_CATEGORY
        );

        $this->setRuleData('amazon_rule_add_listing_product');

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

            /** @var $grid \Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Product\Add\SourceMode\Category\Grid */
            $grid = $this->createBlock('Amazon_Listing_Product_Add_SourceMode_Category_Grid');

            $grid->setSelectedIds($selectedProductsIds);
            $grid->setCurrentCategoryId($this->getSessionValue('current_category_id'));

            $this->setAjaxContent($grid->toHtml());

            return;
        }

        $this->setPageHelpLink('x/6gYtAQ');

        $gridContainer = $this->createBlock('Amazon_Listing_Product_Add_SourceMode_Category');
        $this->addContent($gridContainer);

        /** @var $treeBlock \Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Product\Add\SourceMode\Category\Tree */
        $treeBlock = $this->createBlock('Amazon_Listing_Product_Add_SourceMode_Category_Tree', '', [
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

    protected function asinSearchView()
    {
        $this->setWizardStep('searchAsin');

        $listingProductsIds = $this->getAddedListingProductsIds();

        if (empty($listingProductsIds)) {
            $this->_redirect('*/amazon_listing/view', ['id' => $this->getRequest()->getParam('id')]);
            return;
        }

        if ($this->getRequest()->isXmlHttpRequest()) {
            $grid = $this->createBlock('Amazon_Listing_Product_Add_SearchAsin_Grid');
            $this->setAjaxContent($grid);

            return;
        }

        $this->setPageHelpLink('x/NQctAQ');

        $this->getResultPage()->getConfig()->getTitle()->prepend(
            $this->__('Search Existing Amazon Products (ASIN/ISBN)')
        );

        $this->setPageHelpLink('x/NQctAQ');

        $this->addContent($this->createBlock('Amazon_Listing_Product_Add_SearchAsin'));
    }

    protected function addNewAsinView()
    {
        $this->setWizardStep('newAsin');

        $listingProductsIds = $this->getAddedListingProductsIds();

        if (empty($listingProductsIds)) {
            $this->_redirect('*/amazon_listing/view', ['id' => $this->getRequest()->getParam('id')]);
            return;
        }

        $this->setPageHelpLink('x/SwctAQ');

        $this->getResultPage()->getConfig()->getTitle()->prepend($this->__('New ASIN/ISBN Creation'));

        $this->addContent($this->createBlock('Amazon_Listing_Product_Add_NewAsin'));
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
        $this->endWizard();

        $this->getHelper('Data\Session')->setValue('products_source', '');

        $listing = $this->getListing();

        $this->getHelper('Data\Session')->setValue(
            'added_products_ids',
            $listing->getSetting('additional_data', 'adding_listing_products_ids')
        );

        $listing->setSetting('additional_data', 'adding_listing_products_ids', []);
        $listing->setSetting('additional_data', 'adding_new_asin_listing_products_ids', []);
        $listing->setSetting('additional_data', 'auto_search_was_performed', 0);
        $listing->save();

        $this->getResultPage()->getConfig()->getTitle()->prepend($this->__('Congratulations'));

        $this->addContent($this->createBlock('Amazon_Listing_Product_Add_Review'));
    }

    //########################################

    protected function getHideProductsInOtherListingsPrefix()
    {
        $id = $this->getRequest()->getParam('id');

        $prefix = 'amazon_hide_products_others_listings_';
        $prefix .= $id === null ? 'add' : $id;
        $prefix .= '_listing_product';

        return $prefix;
    }

    //########################################

    protected function filterProductsForSearch($productsIds)
    {
        $productsIds = $this->getHelper('Component_Amazon_Variation')->filterProductsByStatus($productsIds);

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
}
