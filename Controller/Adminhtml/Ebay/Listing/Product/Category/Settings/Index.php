<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Product\Category\Settings;

use \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Product\Category\Settings\Mode as CategoryTemplateBlock;
use \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Product\Category\Settings;
use \Ess\M2ePro\Helper\Component\Ebay\Category as eBayCategory;
use \Ess\M2ePro\Model\Ebay\Template\Category as TemplateCategory;
use Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Product\Add\SourceMode as SourceModeBlock;


/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Product\Category\Settings\Index
 */
class Index extends Settings
{
    /** @var \Ess\M2ePro\Model\Listing */
    protected $listing;

    //########################################

    public function execute()
    {
        if (!$listingId = $this->getRequest()->getParam('id')) {
            throw new \Ess\M2ePro\Model\Exception('Listing is not defined');
        }

        $this->listing = $this->getListingFromRequest();

        $addedIds = $this->listing->getChildObject()->getAddedListingProductsIds();
        if (empty($addedIds)) {
            return $this->_redirect('*/ebay_listing_product_add', ['id' => $listingId, '_current' => true]);
        }

        $step = (int)$this->getRequest()->getParam('step');

        if ($this->getSessionValue('mode') === null) {
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
                return $this->_redirect('*/*/', ['_current' => true,'step' => 1]);
        }

        $action .= 'Mode'. ucfirst($this->getSessionValue('mode'));

        return $this->$action();
    }

    //########################################

    private function stepOne()
    {
        $builderData = $this->listing->getSetting('additional_data', 'mode_same_category_data');
        if ($builderData) {
            /** @var \Ess\M2ePro\Model\Ebay\Template\Category $categoryTpl */
            $categoryTpl = $this->activeRecordFactory->getObject('Ebay_Template_Category');
            if (!empty($builderData[eBayCategory::TYPE_EBAY_MAIN])) {
                $categoryTpl->load($builderData[eBayCategory::TYPE_EBAY_MAIN]['template_id']);
            }

            /** @var \Ess\M2ePro\Model\Ebay\Template\Category $categorySecondaryTpl */
            $categorySecondaryTpl = $this->activeRecordFactory->getObject('Ebay_Template_Category');
            if (!empty($builderData[eBayCategory::TYPE_EBAY_SECONDARY])) {
                $categorySecondaryTpl->load($builderData[eBayCategory::TYPE_EBAY_SECONDARY]['template_id']);
            }

            /** @var \Ess\M2ePro\Model\Ebay\Template\StoreCategory $storeTpl */
            $storeTpl = $this->activeRecordFactory->getObject('Ebay_Template_StoreCategory');
            if (!empty($builderData[eBayCategory::TYPE_STORE_MAIN])) {
                $storeTpl->load($builderData[eBayCategory::TYPE_STORE_MAIN]['template_id']);
            }

            /** @var \Ess\M2ePro\Model\Ebay\Template\StoreCategory $storeSecondaryTpl */
            $storeSecondaryTpl = $this->activeRecordFactory->getObject('Ebay_Template_StoreCategory');
            if (!empty($builderData[eBayCategory::TYPE_STORE_SECONDARY])) {
                $storeSecondaryTpl->load($builderData[eBayCategory::TYPE_STORE_SECONDARY]['template_id']);
            }

            if ($categoryTpl->getId()) {
                $this->saveModeSame(
                    $categoryTpl,
                    $categorySecondaryTpl,
                    $storeTpl,
                    $storeSecondaryTpl,
                    false
                );

                return $this->_redirect(
                    '*/ebay_listing/review',
                    ['id' => $this->getRequest()->getParam('id')]
                );
            }
        }

        $source = $this->listing->getSetting('additional_data', 'source');

        if ($source == SourceModeBlock::MODE_OTHER) {
            return $this->_redirect('*/*/otherCategories', ['_current' => true]);
        }

        if ($this->getRequest()->isPost()) {
            $mode = $this->getRequest()->getParam('mode');
            $this->setSessionValue('mode', $mode);

            if ($mode == CategoryTemplateBlock::MODE_SAME) {
                $temp = $this->getSessionValue($this->getSessionDataKey());
                $temp['remember'] = (bool)$this->getRequest()->getParam('mode_same_remember_checkbox', false);
                $this->setSessionValue($this->getSessionDataKey(), $temp);
            }

            if ($source) {
                $this->getListingFromRequest()->setSetting(
                    'additional_data',
                    ['ebay_category_settings_mode',$source],
                    $mode
                )->save();
            }

            return $this->_redirect('*/*/', [
                'step' => 2,
                '_current' => true,
                'skip_get_suggested' => null
            ]);
        }

        $this->setWizardStep('categoryStepOne');

        $defaultMode = CategoryTemplateBlock::MODE_SAME;
        if ($source == CategoryTemplateBlock::MODE_CATEGORY) {
            $defaultMode = CategoryTemplateBlock::MODE_CATEGORY;
        }

        $mode = null;
        $temp = $this->listing->getSetting('additional_data', ['ebay_category_settings_mode', $source]);
        $temp && $mode = $temp;
        $temp = $this->getSessionValue('mode');
        $temp && $mode = $temp;

        $allowedModes = [
            CategoryTemplateBlock::MODE_SAME,
            CategoryTemplateBlock::MODE_CATEGORY,
            CategoryTemplateBlock::MODE_PRODUCT,
            CategoryTemplateBlock::MODE_MANUALLY
        ];

        if ($mode) {
            !in_array($mode, $allowedModes) && $mode = $defaultMode;
        } else {
            $mode = $defaultMode;
        }

        $this->clearSession();

        $block = $this->createBlock('Ebay_Listing_Product_Category_Settings_Mode');
        $block->setData('mode', $mode);

        $this->addContent($block);
        $this->getResultPage()->getConfig()->getTitle()->prepend($this->__('Set Your eBay Categories'));
        $this->setPageHelpLink('x/lAItAQ');

        return $this->getResult();
    }

    //########################################

    private function stepTwoModeSame()
    {
        if ($this->getRequest()->isPost()) {
            $categoryData = [];
            if ($param = $this->getRequest()->getParam('category_data')) {
                $categoryData = $this->getHelper('Data')->jsonDecode($param);
            }

            $sessionData = $this->getSessionValue();
            $sessionData['mode_same']['category'] = $categoryData;
            $this->setSessionValue(null, $sessionData);

            /** @var \Ess\M2ePro\Model\Ebay\Template\Category\Chooser\Converter $converter */
            $converter = $this->modelFactory->getObject('Ebay_Template_Category_Chooser_Converter');
            $converter->setAccountId($this->listing->getAccountId());
            $converter->setMarketplaceId($this->listing->getMarketplaceId());
            foreach ($categoryData as $type => $templateData) {
                $converter->setCategoryDataFromChooser($templateData, $type);
            }

            $categoryTpl = $this->modelFactory->getObject('Ebay_Template_Category_Builder')->build(
                $this->activeRecordFactory->getObject('Ebay_Template_Category'),
                $converter->getCategoryDataForTemplate(eBayCategory::TYPE_EBAY_MAIN)
            );
            $categorySecondaryTpl = $this->modelFactory->getObject('Ebay_Template_Category_Builder')->build(
                $this->activeRecordFactory->getObject('Ebay_Template_Category'),
                $converter->getCategoryDataForTemplate(eBayCategory::TYPE_EBAY_SECONDARY)
            );
            $storeTpl = $this->modelFactory->getObject('Ebay_Template_StoreCategory_Builder')->build(
                $this->activeRecordFactory->getObject('Ebay_Template_StoreCategory'),
                $converter->getCategoryDataForTemplate(eBayCategory::TYPE_STORE_MAIN)
            );
            $storeSecondaryTpl = $this->modelFactory->getObject('Ebay_Template_StoreCategory_Builder')->build(
                $this->activeRecordFactory->getObject('Ebay_Template_StoreCategory'),
                $converter->getCategoryDataForTemplate(eBayCategory::TYPE_STORE_SECONDARY)
            );

            $this->saveModeSame(
                $categoryTpl,
                $categorySecondaryTpl,
                $storeTpl,
                $storeSecondaryTpl,
                !empty($sessionData['mode_same']['remember'])
            );

            return $this->_redirect(
                '*/ebay_listing/review',
                ['id' => $this->getRequest()->getParam('id')]
            );
        }

        $this->setWizardStep('categoryStepTwo');

        $ebayListing = $this->listing->getChildObject();
        $sessionData = $this->getSessionValue();

        $categoriesData = [];

        /** @var \Ess\M2ePro\Model\Ebay\Template\Category\Chooser\Converter $converter */
        $converter = $this->modelFactory->getObject('Ebay_Template_Category_Chooser_Converter');
        $converter->setAccountId($this->listing->getAccountId());
        $converter->setMarketplaceId($this->listing->getMarketplaceId());

        $sameData = $ebayListing->getLastPrimaryCategory(['ebay_primary_category', 'mode_same']);
        if (!empty($sameData['mode']) && !empty($sameData['value']) && !empty($sameData['path'])) {
            $template = $this->activeRecordFactory->getObject('Ebay_Template_Category');
            $template->loadByCategoryValue(
                $sameData['value'],
                $sameData['mode'],
                $this->listing->getMarketplaceId(),
                0
            );

            if ($template->getId()) {
                $converter->setCategoryDataFromTemplate($template->getData(), eBayCategory::TYPE_EBAY_MAIN);
                $categoriesData[eBayCategory::TYPE_EBAY_MAIN] = $converter->getCategoryDataForChooser(
                    eBayCategory::TYPE_EBAY_MAIN
                );
            } else {
                $categoriesData[eBayCategory::TYPE_EBAY_MAIN] = [
                    'mode'  => $sameData['mode'],
                    'value' => $sameData['value'],
                    'path'  => $sameData['path']
                ];
            }
        }

        $sameData = $ebayListing->getLastPrimaryCategory(['ebay_store_primary_category', 'mode_same']);
        if (!empty($sameData['mode']) && !empty($sameData['value']) && !empty($sameData['path'])) {
            $template = $this->activeRecordFactory->getObject('Ebay_Template_StoreCategory');
            $template->loadByCategoryValue(
                $sameData['value'],
                $sameData['mode'],
                $this->listing->getAccountId()
            );

            if ($template->getId()) {
                $converter->setCategoryDataFromTemplate($template->getData(), eBayCategory::TYPE_STORE_MAIN);
                $categoriesData[eBayCategory::TYPE_STORE_MAIN] = $converter->getCategoryDataForChooser(
                    eBayCategory::TYPE_STORE_MAIN
                );
            } else {
                $categoriesData[eBayCategory::TYPE_STORE_MAIN] = [
                    'mode'  => $sameData['mode'],
                    'value' => $sameData['value'],
                    'path'  => $sameData['path']
                ];
            }
        }

        !empty($sessionData['mode_same']['category']) && $categoriesData = $sessionData['mode_same']['category'];

        $block = $this->createBlock('Ebay_Listing_Product_Category_Settings_Mode_Same_Chooser', '', [
            'data' => ['categories_data' => $categoriesData]
        ]);
        $this->addContent($block);

        $this->getResultPage()->getConfig()->getTitle()
             ->prepend($this->__('Set Category (All Products same Category)'));

        return $this->getResult();
    }

    private function stepTwoModeCategory()
    {
        $categoriesIds = $this->getCategoriesIdsByListingProductsIds(
            $this->listing->getChildObject()->getAddedListingProductsIds()
        );

        if (empty($categoriesIds) && !$this->getRequest()->isXmlHttpRequest()) {
            $this->getMessageManager()->addError($this->__(
                'Magento Category is not provided for the products you are currently adding.
                Please go back and select a different option to assign Channel category to your products. '
            ));
        }

        if (!$this->getRequest()->isAjax()) {
            $this->initSessionDataCategories($categoriesIds);
        }

        $categoriesData = $this->getSessionValue($this->getSessionDataKey());

        $this->setWizardStep('categoryStepTwo');

        $block = $this->createBlock('Ebay_Listing_Product_Category_Settings_Mode_Category');
        $block->getChildBlock('grid')->setStoreId($this->listing->getStoreId());
        $block->getChildBlock('grid')->setCategoriesData($categoriesData);

        if ($this->getRequest()->isXmlHttpRequest()) {
            $this->setAjaxContent($block->getChildBlock('grid')->toHtml());

            return $this->getResult();
        }

        $this->addContent($block);

        $this->getResultPage()->getConfig()->getTitle()
            ->prepend($this->__('Set Category (Based On Magento Categories)'));

        return $this->getResult();
    }

    private function stepTwoModeManually()
    {
        return $this->stepTwoModeProduct(false);
    }

    private function stepTwoModeProduct($getSuggested = true)
    {
        $this->setWizardStep('categoryStepTwo');

        if (!$this->getRequest()->getParam('skip_get_suggested')) {
            $this->getHelper('Data\GlobalData')->setValue('get_suggested', $getSuggested);
        }

        $this->initSessionDataProducts(
            $this->listing->getChildObject()->getAddedListingProductsIds()
        );

        if ($getSuggested) {
            $block = $this->createBlock('Ebay_Listing_Product_Category_Settings_Mode_Product');
            $this->getResultPage()->getConfig()->getTitle()->prepend(
                $this->__('Set Category (Suggested Categories)')
            );
        } else {
            $block = $this->createBlock('Ebay_Listing_Product_Category_Settings_Mode_Manually');
            $this->getResultPage()->getConfig()->getTitle()->prepend(
                $this->__('Set Category (Manually for each Product)')
            );
        }

        $categoriesData = $this->getSessionValue($this->getSessionDataKey());
        $block->getChildBlock('grid')->setCategoriesData($categoriesData);
        $this->addContent($block);

        return $this->getResult();
    }

    //########################################

    private function stepThreeModeSame()
    {
        return $this->_redirect(
            '*/ebay_listing/view',
            ['id' => $this->getRequest()->getParam('id')]
        );
    }

    private function stepThreeModeCategory()
    {
        $this->setWizardStep('categoryStepThree');
        $this->_forward('save');
    }

    private function stepThreeModeProduct()
    {
        $this->setWizardStep('categoryStepThree');
        return $this->stepThreeSelectSpecifics();
    }

    private function stepThreeModeManually()
    {
        $this->setWizardStep('categoryStepThree');
        return $this->stepThreeSelectSpecifics();
    }

    private function stepThreeSelectSpecifics()
    {
        $primaryData = [];
        $defaultHashes = [];

        $sessionData = $this->getSessionValue($this->getSessionDataKey());
        foreach ($sessionData as $id => $categoryData) {
            if (!isset($categoryData[eBayCategory::TYPE_EBAY_MAIN]) ||
                $categoryData[eBayCategory::TYPE_EBAY_MAIN]['mode'] === TemplateCategory::CATEGORY_MODE_NONE
            ) {
                continue;
            }

            $primaryCategory = $categoryData[eBayCategory::TYPE_EBAY_MAIN];

            if ($primaryCategory['is_custom_template'] !== null && $primaryCategory['is_custom_template'] == 0) {
                list($mainHash, $hash) = $this->getCategoryHashes($categoryData[eBayCategory::TYPE_EBAY_MAIN]);

                if (!isset($defaultHashes[$mainHash])) {
                    $defaultHashes[$mainHash] = $hash;
                }

                if (!isset($primaryData[$hash])) {
                    $primaryData[$hash][eBayCategory::TYPE_EBAY_MAIN] = $primaryCategory;
                    $primaryData[$hash]['listing_products_ids'] = [];
                }
            }
        }

        $canBeSkipped = !$this->getRequest()->isAjax();
        $listing = $this->getListingFromRequest();

        foreach ($sessionData as $id => &$categoryData) {
            if (!isset($categoryData[eBayCategory::TYPE_EBAY_MAIN]) ||
                $categoryData[eBayCategory::TYPE_EBAY_MAIN]['mode'] === TemplateCategory::CATEGORY_MODE_NONE
            ) {
                continue;
            }

            $primaryCategory = $categoryData[eBayCategory::TYPE_EBAY_MAIN];
            list($mainHash, $hash) = $this->getCategoryHashes($categoryData[eBayCategory::TYPE_EBAY_MAIN]);

            $hasRequiredSpecifics = $this->getHelper('Component_Ebay_Category_Ebay')->hasRequiredSpecifics(
                $categoryData[eBayCategory::TYPE_EBAY_MAIN]['value'],
                $listing->getMarketplaceId()
            );

            if ($primaryCategory['is_custom_template'] === null) {
                if (isset($defaultHashes[$mainHash])) {
                    /** set default settings for the same category and not selected specifics */
                    $hash = $defaultHashes[$mainHash];
                    if (isset($primaryData[$hash][eBayCategory::TYPE_EBAY_MAIN])) {
                        $categoryData[eBayCategory::TYPE_EBAY_MAIN] = $primaryData[$hash][eBayCategory::TYPE_EBAY_MAIN];
                    }
                } elseif ($hasRequiredSpecifics) {
                    $canBeSkipped = false;
                }
            }

            if (!isset($primaryData[$hash])) {
                $primaryData[$hash][eBayCategory::TYPE_EBAY_MAIN] = $categoryData[eBayCategory::TYPE_EBAY_MAIN];
                $primaryData[$hash]['listing_products_ids'] = $categoryData['listing_products_ids'];
            } else {
                // @codingStandardsIgnoreLine
                $primaryData[$hash]['listing_products_ids'] = array_merge(
                    $primaryData[$hash]['listing_products_ids'],
                    $categoryData['listing_products_ids']
                );
            }
        }

        unset($categoryData);
        $this->setSessionValue($this->getSessionDataKey(), $sessionData);

        if ($canBeSkipped) {
            $this->_forward('save');
        }

        $block = $this->createBlock('Ebay_Listing_Product_Category_Settings_Specific');
        $block->getChildBlock('grid')->setCategoriesData($primaryData);

        if ($this->getRequest()->isXmlHttpRequest()) {
            $this->setAjaxContent($block->getChildBlock('grid')->toHtml());

            return $this->getResult();
        }

        $this->addContent($block);

        $this->getResultPage()->getConfig()->getTitle()
            ->prepend($this->__('Set Category Specifics'));

        return $this->getResult();
    }

    //########################################

    private function getCategoriesIdsByListingProductsIds($listingProductsIds)
    {
        $listingProductCollection = $this->ebayFactory->getObject('Listing\Product')->getCollection()
            ->addFieldToFilter('id', ['in' => $listingProductsIds]);

        $productsIds = [];
        foreach ($listingProductCollection->getData() as $item) {
            $productsIds[] = $item['product_id'];
        }

        $productsIds = array_unique($productsIds);

        return $this->getHelper('Magento\Category')->getLimitedCategoriesByProducts(
            $productsIds,
            $this->listing->getStoreId()
        );
    }

    //########################################

    protected function saveModeSame(
        \Ess\M2ePro\Model\Ebay\Template\Category $categoryTpl,
        \Ess\M2ePro\Model\Ebay\Template\Category $categorySecondaryTpl,
        \Ess\M2ePro\Model\Ebay\Template\StoreCategory $storeTpl,
        \Ess\M2ePro\Model\Ebay\Template\StoreCategory $storeSecondaryTpl,
        $remember
    ) {
        $this->activeRecordFactory->getObject('Ebay_Listing_Product')->assignTemplatesToProducts(
            $this->getEbayListingFromRequest()->getAddedListingProductsIds(),
            $categoryTpl->getId(),
            $categorySecondaryTpl->getId(),
            $storeTpl->getId(),
            $storeSecondaryTpl->getId()
        );

        if ($remember) {
            $sameData = [];

            if ($categoryTpl->getId()) {
                $sameData[EbayCategory::TYPE_EBAY_MAIN]['template_id'] = $categoryTpl->getId();
            }

            if ($categorySecondaryTpl->getId()) {
                $sameData[EbayCategory::TYPE_EBAY_SECONDARY]['template_id'] = $categorySecondaryTpl->getId();
            }

            if ($storeTpl->getId()) {
                $sameData[EbayCategory::TYPE_STORE_MAIN]['template_id'] = $storeTpl->getId();
            }

            if ($storeSecondaryTpl->getId()) {
                $sameData[EbayCategory::TYPE_STORE_SECONDARY]['template_id'] = $storeSecondaryTpl->getId();
            }

            $this->listing->setSetting('additional_data', 'mode_same_category_data', $sameData);
            $this->listing->save();
        }

        $this->endWizard();
        $this->endListingCreation();
    }

    //########################################
}
