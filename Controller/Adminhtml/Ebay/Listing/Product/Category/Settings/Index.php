<?php

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Product\Category\Settings;

use \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Product\Category\Settings;

class Index extends Settings
{
    //########################################

    public function execute()
    {
        if (!$listingId = $this->getRequest()->getParam('id')) {
            throw new \Ess\M2ePro\Model\Exception('Listing is not defined');
        }

        $listing = $this->getListing();

        if (count($listing->getChildObject()->getAddedListingProductsIds()) === 0) {
            return $this->_redirect('*/ebay_listing_product_add',array('id' => $listingId,
                '_current' => true));
        }

        $this->getHelper('Data\GlobalData')->setValue('listing_for_products_category_settings', $listing);

        $step = (int)$this->getRequest()->getParam('step');

        if (is_null($this->getSessionValue('mode'))) {
            $step = 1;
        }

        switch ($step) {
            case 1:
                return $this->stepOne();
            case 2:
                $action = 'stepTwo';
                break;
            case 3:
                $action = 'stepThree';
                break;
            // ....
            default:
                return $this->_redirect('*/*/', array('_current' => true,'step' => 1));
        }

        $action .= 'Mode'. ucfirst($this->getSessionValue('mode'));

        return $this->$action();
    }

    //########################################

    private function stepOne()
    {
        if ($builderData = $this->getListing()->getSetting('additional_data','mode_same_category_data')) {

            $categoryTemplate = $this->modelFactory->getObject('Ebay\Template\Category\Builder')->build($builderData);
            $otherCategoryTemplate = $this->modelFactory->getObject('Ebay\Template\OtherCategory\Builder')
                ->build($builderData);

            $this->saveModeSame($categoryTemplate, $otherCategoryTemplate, false);
            return $this->_redirect(
                '*/ebay_listing/review', array('id' => $this->getRequest()->getParam('id'))
            );
        }

        if ($this->getRequest()->isPost()) {
            $mode = $this->getRequest()->getParam('mode');

            $this->setSessionValue('mode', $mode);

            if ($mode == 'same') {
                $temp = $this->getSessionValue($this->getSessionDataKey());
                $temp['remember'] = (bool)$this->getRequest()->getParam('mode_same_remember_checkbox', false);
                $this->setSessionValue($this->getSessionDataKey(),$temp);
            }

            if ($source = $this->getRequest()->getParam('source')) {
                $this->getListing()->setSetting(
                    'additional_data',
                    array('ebay_category_settings_mode',$source),
                    $mode
                )->save();
            }

            return $this->_redirect('*/*/', array(
                'step' => 2,
                '_current' => true,
                'skip_get_suggested' => NULL
            ));
        }

        $this->setWizardStep('categoryStepOne');

        $defaultMode = 'same';
        if ($this->getRequest()->getParam('source') == 'category') {
            $defaultMode = 'category';
        }

        $mode = NULL;

        $temp = $this->getListing()->getSetting(
            'additional_data', array('ebay_category_settings_mode',$this->getRequest()->getParam('source'))
        );
        $temp && $mode = $temp;

        $temp = $this->getSessionValue('mode');
        $temp && $mode = $temp;

        if ($mode) {
            !in_array($mode, array('same','category','product','manually')) && $mode = $defaultMode;
        } else {
            $mode = $defaultMode;
        }

        $block = $this->createBlock('Ebay\Listing\Product\Category\Settings\Mode');
        $block->setData('mode', $mode);
        $this->addContent($block);

        $this->getResultPage()->getConfig()->getTitle()->prepend($this->__('Set eBay Categories'));
        $this->setPageHelpLink('x/lAItAQ');

        return $this->getResult();
    }

    //########################################

    private function stepTwoModeSame()
    {
        if ($this->getRequest()->isPost()) {
            $categoryParam = $this->getRequest()->getParam('category_data');
            $categoryData = array();
            if (!empty($categoryParam)) {
                $categoryData = $this->getHelper('Data')->jsonDecode($categoryParam);
            }

            $sessionData = $this->getHelper('Data\Session')->getValue($this->sessionKey);

            $data = array();
            $keys = array(
                'category_main_mode',
                'category_main_id',
                'category_main_attribute',

                'category_secondary_mode',
                'category_secondary_id',
                'category_secondary_attribute',

                'store_category_main_mode',
                'store_category_main_id',
                'store_category_main_attribute',

                'store_category_secondary_mode',
                'store_category_secondary_id',
                'store_category_secondary_attribute',
            );
            foreach ($categoryData as $key => $value) {
                if (!in_array($key, $keys)) {
                    continue;
                }

                $data[$key] = $value;
            }

            $listing = $this->getListing();

            $this->addCategoriesPath($data, $listing);
            $data['marketplace_id'] = $listing->getMarketplaceId();

            $templates = $this->activeRecordFactory->getObject('Ebay\Template\Category')->getCollection()
                ->getItemsByPrimaryCategories(array($data));

            $templateExists = (bool)$templates;

            $specifics = array();
            /* @var $categoryTemplate \Ess\M2ePro\Model\Ebay\Template\Category */
            if ($categoryTemplate = reset($templates)) {
                $specifics = $categoryTemplate->getSpecifics();
            }

            $useLastSpecifics = $this->useLastSpecifics();

            $sessionData['mode_same']['category'] = $data;
            $sessionData['mode_same']['specific'] = $specifics;

            $this->getHelper('Data\Session')->setValue($this->sessionKey, $sessionData);

            if (!$useLastSpecifics || !$templateExists) {
                return $this->_redirect(
                    '*/*', array('_current' => true, 'step' => 3)
                );
            }

            $builderData = $data;
            $builderData['account_id'] = $this->getListing()->getAccountId();
            $builderData['marketplace_id'] = $this->getListing()->getMarketplaceId();

            $otherCategoryTemplate = $this->modelFactory->getObject('Ebay\Template\OtherCategory\Builder')->build(
                $builderData
            );

            $this->saveModeSame($categoryTemplate,$otherCategoryTemplate,!empty($sessionData['mode_same']['remember']));

            return $this->_redirect(
                '*/ebay_listing/review', array('id' => $this->getRequest()->getParam('id'))
            );
        }

        $this->setWizardStep('categoryStepTwo');

        $listing = $this->getListing();
        $sessionData = $this->getHelper('Data\Session')->getValue($this->sessionKey);

        $internalData = array();

        $internalData = array_merge(
            $internalData,
            $listing->getChildObject()->getLastPrimaryCategory(array('ebay_primary_category','mode_same'))
        );
        $internalData = array_merge(
            $internalData,
            $listing->getChildObject()->getLastPrimaryCategory(array('ebay_store_primary_category','mode_same'))
        );

        !empty($sessionData['mode_same']['category']) && $internalData = $sessionData['mode_same']['category'];

        $block = $this->createBlock('Ebay\Listing\Product\Category\Settings\Mode\Same\Chooser', '', [
            'data' => ['internal_data' => $internalData]
        ]);
        $this->addContent($block);

        $this->getResultPage()->getConfig()->getTitle()->prepend($this->__('Set eBay Categories'));

        return $this->getResult();
    }

    private function stepTwoModeCategory()
    {
        $categoriesIds = $this->getCategoriesIdsByListingProductsIds(
            $this->getListing()->getChildObject()->getAddedListingProductsIds()
        );

        if (empty($categoriesIds) && !$this->getRequest()->isXmlHttpRequest()) {
            $this->getMessageManager()->addError($this->__(
                'Magento Categories are not specified on Products you are adding.')
            );
        }

        $this->initSessionData($categoriesIds);

        $listing = $this->getListing();

        $previousCategoriesData = array();

        $tempData = $listing->getChildObject()->getLastPrimaryCategory(array('ebay_primary_category','mode_category'));
        foreach ($tempData as $categoryId => $data) {
            !isset($previousCategoriesData[$categoryId]) && $previousCategoriesData[$categoryId] = array();
            $previousCategoriesData[$categoryId] += $data;
        }

        $tempData = $listing->getChildObject()->getLastPrimaryCategory(
            array('ebay_store_primary_category','mode_category')
        );
        foreach ($tempData as $categoryId => $data) {
            !isset($previousCategoriesData[$categoryId]) && $previousCategoriesData[$categoryId] = array();
            $previousCategoriesData[$categoryId] += $data;
        }

        $categoriesData = $this->getSessionValue($this->getSessionDataKey());

        foreach ($categoriesData as $magentoCategoryId => &$data) {

            if (!isset($previousCategoriesData[$magentoCategoryId])) {
                continue;
            }

            $listingProductsIds = $this->getSelectedListingProductsIdsByCategoriesIds(array($magentoCategoryId));
            $data['listing_products_ids'] = $listingProductsIds;

            if ($data['category_main_mode'] != \Ess\M2ePro\Model\Ebay\Template\Category::CATEGORY_MODE_NONE) {
                continue;
            }

            $this->addCategoriesPath($previousCategoriesData[$magentoCategoryId], $listing);

            $data = array_merge($data,$previousCategoriesData[$magentoCategoryId]);
        }

        $this->setSessionValue($this->getSessionDataKey(),$categoriesData);

        $this->setWizardStep('categoryStepTwo');

        $block = $this->createBlock('Ebay\Listing\Product\Category\Settings\Mode\Category');
        $block->getChildBlock('grid')->setStoreId($listing->getStoreId());
        $block->getChildBlock('grid')->setCategoriesData($categoriesData);

        if ($this->getRequest()->isXmlHttpRequest()) {
            $this->setAjaxContent($block->getChildBlock('grid')->toHtml());

            return $this->getResult();
        }

        $this->addContent($block);

        $this->getResultPage()->getConfig()->getTitle()->prepend(
            $this->__('Set eBay Categories (Based On Magento Categories)')
        );

        return $this->getResult();
    }

    private function stepTwoModeManually()
    {
        return $this->stepTwoModeProduct(false);
    }

    private function stepTwoModeProduct($getSuggested = true)
    {
        $this->setWizardStep('categoryStepTwo');

        // ---------------------------------------
        $listing = $this->getListing();
        $listingProductAddIds = (array)$this->getHelper('Data')->jsonDecode(
            $listing->getChildObject()->getData('product_add_ids')
        );
        // ---------------------------------------

        // ---------------------------------------
        if (!$this->getRequest()->getParam('skip_get_suggested')) {
            $this->getHelper('Data\GlobalData')->setValue('get_suggested', $getSuggested);
        }
        $this->initSessionData($listingProductAddIds);
        // ---------------------------------------

        if ($this->getRequest()->isXmlHttpRequest()) {
            $this->setAjaxContent(
                $this->createBlock('Ebay\Listing\Product\Category\Settings\Mode\Product\Grid')->toHtml()
            );

            return $this->getResult();
        }

        $this->addContent($this->createBlock('Ebay\Listing\Product\Category\Settings\Mode\Product'));

        if ($getSuggested) {
            $title = $this->__('Set eBay Categories (Get Suggested Categories)');
        } else {
            $title = $this->__('Set eBay Categories (Set Manually for each Product)');
        }

        $this->getResultPage()->getConfig()->getTitle()->prepend($title);
        return $this->getResult();
    }

    //########################################

    private function stepThreeModeSame()
    {
        if ($this->getRequest()->isPost()) {
            $specifics = $this->getRequest()->getParam('specific_data');

            if ($specifics) {
                $specifics = $this->getHelper('Data')->jsonDecode($specifics);
                $specifics = $specifics['specifics'];
            } else {
                $specifics = array();
            }

            $sessionData = $this->getSessionValue($this->getSessionDataKey());

            // save category template & specifics
            // ---------------------------------------
            $builderData = $sessionData['category'];
            $builderData['specifics'] = $specifics;
            $builderData['account_id'] = $this->getListing()->getAccountId();
            $builderData['marketplace_id'] = $this->getListing()->getMarketplaceId();

            $categoryTemplate = $this->modelFactory->getObject('Ebay\Template\Category\Builder')->build($builderData);
            $otherCategoryTemplate = $this->modelFactory->getObject('Ebay\Template\OtherCategory\Builder')->build(
                $builderData
            );

            $this->saveModeSame($categoryTemplate, $otherCategoryTemplate, !empty($sessionData['remember']));

            return $this->_redirect(
                '*/ebay_listing/review', array('id' => $this->getRequest()->getParam('id'))
            );
        }

        $this->setWizardStep('categoryStepThree');

        $sessionData = $this->getHelper('Data\Session')->getValue($this->sessionKey);
        $selectedCategoryMode = $sessionData['mode_same']['category']['category_main_mode'];
        if ($selectedCategoryMode == \Ess\M2ePro\Model\Ebay\Template\Category::CATEGORY_MODE_EBAY) {
            $selectedCategoryValue = $sessionData['mode_same']['category']['category_main_id'];
        } else {
            $selectedCategoryValue = $sessionData['mode_same']['category']['category_main_attribute'];
        }

        $specificBlock = $this->createBlock('Ebay\Listing\Product\Category\Settings\Mode\Same\Specific', '', [
            'data' => [
                'category_mode' => $selectedCategoryMode,
                'category_value' => $selectedCategoryValue,
                'specifics' => $sessionData['mode_same']['specific']
            ]
        ]);

        $this->getResultPage()->getConfig()->getTitle()->prepend($this->__('Set eBay Specifics'));
        $this->setPageHelpLink('x/7gMtAQ');

        $this->addContent($specificBlock);

        return $this->getResult();
    }

    private function stepThreeModeCategory()
    {
        return $this->stepThree();
    }

    private function stepThreeModeProduct()
    {
        return $this->stepThree();
    }

    private function stepThreeModeManually()
    {
        return $this->stepThree();
    }

    private function stepThree()
    {
        $this->setWizardStep('categoryStepThree');

        $listing = $this->getListing();

        $templatesData = $this->getTemplatesData();

        if (count($templatesData) <= 0) {

            $this->save($this->getSessionValue($this->getSessionDataKey()));

            return $this->_redirect('*/ebay_listing/review', array(
                'disable_list' => true,
                '_current' => true
            ));
        }

        $this->initSpecificsSessionData($templatesData);

        $useLastSpecifics = $this->useLastSpecifics();

        $templatesExistForAll = true;
        foreach ($this->getSessionValue('specifics') as $categoryId => $specificsData) {
            if ($specificsData['template_exists'] && $useLastSpecifics) {
                unset($templatesData[$categoryId]);
            } else {
                $templatesExistForAll = false;
            }
        }

        if ($templatesExistForAll && $useLastSpecifics) {
            $this->save($this->getSessionValue($this->getSessionDataKey()));
            return $this->_redirect('*/ebay_listing/review', array('_current' => true));
        }

        $currentPrimaryCategory = $this->getCurrentPrimaryCategory();

        $this->setSessionValue('current_primary_category', $currentPrimaryCategory);

        $wrapper = $this->createBlock('Ebay\Listing\Product\Category\Settings\Specific\Wrapper');
        $wrapper->setData('store_id', $listing->getStoreId());
        $wrapper->setData('categories', $templatesData);
        $wrapper->setData('current_category', $currentPrimaryCategory);

        $wrapper->setChild('specific', $this->getSpecificBlock());

        $this->getResultPage()->getConfig()->getTitle()->prepend($this->__('Set eBay Specifics'));
        $this->setPageHelpLink('x/7gMtAQ');

        $this->addContent($wrapper);

        return $this->getResult();
    }

    //########################################

    private function initSpecificsSessionData($templatesData)
    {
        $specificsData = $this->getSessionValue('specifics');
        is_null($specificsData) && $specificsData = array();

        $existingTemplates = $this->activeRecordFactory->getObject('Ebay\Template\Category')->getCollection()
            ->getItemsByPrimaryCategories($templatesData);

        foreach ($templatesData as $id => $templateData) {

            if (!empty($specificsData[$id])) {
                continue;
            }

            $specifics = array();
            $templateExists = false;

            if (isset($existingTemplates[$id])) {
                $specifics = $existingTemplates[$id]->getSpecifics();
                $templateExists = true;
            }

            $specificsData[$id] = array(
                'specifics' => $specifics,
                'template_exists' => $templateExists
            );
        }

        $this->setSessionValue('specifics', $specificsData);
    }

    //########################################

    private function getCategoriesIdsByListingProductsIds($listingProductsIds)
    {
        $listingProductCollection = $this->ebayFactory->getObject('Listing\Product')->getCollection()
            ->addFieldToFilter('id',array('in' => $listingProductsIds));

        $productsIds = array();
        foreach ($listingProductCollection->getData() as $item) {
            $productsIds[] = $item['product_id'];
        }
        $productsIds = array_unique($productsIds);

        $listing = $this->getListing();

        return $this->getHelper('Magento\Category')->getLimitedCategoriesByProducts(
            $productsIds,
            $listing->getStoreId()
        );
    }

    //########################################

    protected function saveModeSame($categoryTemplate, $otherCategoryTemplate, $remember)
    {
        $this->assignTemplatesToProducts(
            $categoryTemplate->getId(),
            $otherCategoryTemplate->getId(),
            $this->getListing()->getChildObject()->getAddedListingProductsIds()
        );

        if ($remember) {
            $this->getListing()->setSetting(
                    'additional_data', 'mode_same_category_data',
                    array_merge(
                        $categoryTemplate->getData(),
                        $otherCategoryTemplate->getData(),
                        array('specifics' => $categoryTemplate->getSpecifics())
                    )
                )
                ->save();
        }

        $this->endWizard();
        $this->endListingCreation();
    }

    //########################################
}