<?php

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Product\Add;

class Index extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Product\Add
{
    public function execute()
    {
        if (!$listingId = $this->getRequest()->getParam('id')) {
            throw new \Ess\M2ePro\Model\Exception('Listing is not defined');
        }

        if ((bool)$this->getRequest()->getParam('clear', false)) {
            $this->clear();
            $this->getRequest()->setParam('clear',null);
            return $this->_redirect('*/*/',array('_current' => true, 'step' => 1));
        }

        $listing = $this->getListing();

        $this->getHelper('Data\GlobalData')->setValue('listing_for_products_add', $listing);

        $step = (int)$this->getRequest()->getParam('step');

        switch ($step) {
            case 1:
                return $this->stepOne();
        }

        return $this->_redirect('*/*/index', array('_current' => true,'step' => 1));
    }

    //########################################

    private function stepOne()
    {
        switch ($this->getRequest()->getParam('source')) {
            case \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Product\Add\SourceMode::MODE_PRODUCT:
                return $this->stepOneSourceProducts();

            case \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Product\Add\SourceMode::MODE_CATEGORY:
                return $this->stepOneSourceCategories();
        }

        return $this->_redirect('*/*/sourceMode', array('_current' => true));
    }

    private function stepOneSourceProducts()
    {
        /** @var \Ess\M2ePro\Model\Listing $listing */
        $listing = $this->getHelper('Data\GlobalData')->getValue('listing_for_products_add');
        $ids = $listing->getChildObject()->getAddedListingProductsIds();

        if (!empty($ids)) {

            if ($this->getRequest()->isXmlHttpRequest()) {
               $this->setJsonContent(array(
                    'ajaxExpired' => 1,
                    'ajaxRedirect' => $this->getUrl('*/*/index', array('_current' => true,'step' => 1))
                ));
                return $this->getResult();
            } else {
                return $this->_redirect(
                    '*/ebay_listing_product_category_settings/',array('_current' => true,'step' => 1)
                );
            }
        }

        // Set rule model
        // ---------------------------------------
        $this->setRuleData('ebay_product_add_step_one');
        // ---------------------------------------

        // Set Hide Products In Other Listings
        // ---------------------------------------
        $prefix = $this->getHideProductsInOtherListingsPrefix();

        if ($this->getRequest()->isPost()) {
            $hideProductsOtherParam = $this->getRequest()->getPost('hide_products_others_listings', 1);
            $this->getHelper('Data\Session')->setValue($prefix, $hideProductsOtherParam);
        }

        $this->getHelper('Data\GlobalData')->setValue('hide_products_others_listings_prefix', $prefix);
        // ---------------------------------------

        if ($this->getRequest()->isXmlHttpRequest()) {
            $this->setAjaxContent(
                $this->createBlock('Ebay\Listing\Product\Add\Product\Grid')->toHtml()
            );
            return $this->getResult();
        }

        $this->setPageHelpLink('x/jgItAQ');

        $this->getResultPage()->getConfig()->getTitle()->prepend($this->__('Select Magento Products'));
        $this->addContent($this->createBlock('Ebay\Listing\Product\Add'));

        return $this->getResult();
    }

    private function stepOneSourceCategories()
    {
        $this->setWizardStep('productSelection');

        // Set rule model
        // ---------------------------------------
        $this->setRuleData('ebay_product_add_step_one');
        // ---------------------------------------

        // Set Hide Products In Other Listings
        // ---------------------------------------
        $prefix = $this->getHideProductsInOtherListingsPrefix();

        if ($this->getRequest()->isPost()) {
            $hideProductsOtherParam = $this->getRequest()->getPost('hide_products_others_listings', 1);
            $this->getHelper('Data\Session')->setValue($prefix, $hideProductsOtherParam);
        }

        $this->getHelper('Data\GlobalData')->setValue('hide_products_others_listings_prefix', $prefix);
        // ---------------------------------------

        $tempSession = $this->getSessionValue('source_categories');
        $selectedProductsIds = !isset($tempSession['products_ids']) ? array() : $tempSession['products_ids'];

        if ($this->getRequest()->isXmlHttpRequest()) {

            if ($this->getRequest()->getParam('current_category_id')) {
                $this->setSessionValue('current_category_id', $this->getRequest()->getParam('current_category_id'));
            }

            /* @var $grid \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Product\Add\Category\Grid */
            $grid = $this->createBlock('Ebay\Listing\Product\Add\Category\Grid');

            $grid->setSelectedIds($selectedProductsIds);
            $grid->setCurrentCategoryId($this->getSessionValue('current_category_id'));

            $this->setAjaxContent($grid->toHtml());

            return $this->getResult();
        }

        $this->setPageHelpLink('x/6gYtAQ');

        $this->getResultPage()->getConfig()->getTitle()->prepend($this->__('Select Magento Products'));

        $gridContainer = $this->createBlock('Ebay\Listing\Product\Add');
        $this->addContent($gridContainer);

        /* @var $treeBlock \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Product\Add\Category\Tree */
        $treeBlock = $this->createBlock('Ebay\Listing\Product\Add\Category\Tree');

        if (is_null($this->getSessionValue('current_category_id'))) {
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

    //########################################

    protected function getHideProductsInOtherListingsPrefix()
    {
        $id = $this->getRequest()->getParam('id');

        $prefix = 'ebay_hide_products_others_listings_';
        $prefix .= is_null($id) ? 'add' : $id;
        $prefix .= '_listing_product';

        return $prefix;
    }

    private function clear()
    {
        $this->getHelper('Data\Session')->getValue($this->sessionKey, true);

        $categorySettingsSessionKey = 'ebay_listing_product_category_settings';
        $this->getHelper('Data\Session')->getValue($categorySettingsSessionKey,true);

        $listingId = $this->getRequest()->getParam('id');
        $listing = $this->ebayFactory->getCachedObjectLoaded('Listing',$listingId);

        $listing->getChildObject()->setData(
            'product_add_ids', $this->getHelper('Data')->jsonEncode(array())
        )->save();
    }

    //########################################

    protected function setRuleData($prefix)
    {
        $listingData = $this->getHelper('Data\GlobalData')->getValue('listing_for_products_add');

        $storeId = isset($listingData['store_id']) ? (int)$listingData['store_id'] : 0;
        $prefix .= isset($listingData['id']) ? '_'.$listingData['id'] : '';
        $this->getHelper('Data\GlobalData')->setValue('rule_prefix', $prefix);

        $ruleModel = $this->activeRecordFactory->getObject('Magento\Product\Rule')->setData(
            [
                'prefix' => $prefix,
                'store_id' => $storeId,
            ]
        );

        $ruleParam = $this->getRequest()->getPost('rule');
        if (!empty($ruleParam)) {
            $this->getHelper('Data\Session')->setValue(
                $prefix, $ruleModel->getSerializedFromPost($this->getRequest()->getPostValue())
            );
        } elseif (!is_null($ruleParam)) {
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