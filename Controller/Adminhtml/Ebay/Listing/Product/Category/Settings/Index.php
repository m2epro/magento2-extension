<?php

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Product\Category\Settings;

use Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Product\Add\SourceMode as SourceModeBlock;
use Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Product\Category\Settings\Mode as CategoryTemplateBlock;
use Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Product\Category\Settings;
use Ess\M2ePro\Helper\Component\Ebay\Category as eBayCategory;

class Index extends Settings
{
    /** @var \Ess\M2ePro\Model\Listing */
    protected $listing;
    /** @var \Ess\M2ePro\Helper\Magento\Category */
    protected $magentoCategoryHelper;
    /** @var \Ess\M2ePro\Model\Ebay\ListingFactory */
    protected $ebayListingFactory;

    public function __construct(
        \Ess\M2ePro\Helper\Data\Session $sessionHelper,
        \Ess\M2ePro\Helper\Module\Wizard $wizardHelper,
        \Ess\M2ePro\Helper\Magento $magentoHelper,
        \Ess\M2ePro\Helper\Magento\Category $magentoCategoryHelper,
        eBayCategory\Ebay $componentEbayCategoryEbay,
        eBayCategory $componentEbayCategory,
        eBayCategory\Store $componentEbayCategoryStore,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Model\Ebay\ListingFactory $ebayListingFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct(
            $sessionHelper,
            $wizardHelper,
            $magentoHelper,
            $magentoCategoryHelper,
            $componentEbayCategoryEbay,
            $componentEbayCategory,
            $componentEbayCategoryStore,
            $ebayFactory,
            $context
        );
        $this->ebayListingFactory = $ebayListingFactory;
        $this->magentoCategoryHelper = $magentoCategoryHelper;
    }

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

        if ($step === 4) {
            return $this->stepValidate();
        }

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
                return $this->_redirect('*/*/', ['_current' => true, 'step' => 1]);
        }

        $action .= 'Mode' . ucfirst($this->getSessionValue('mode'));

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

                return $this->_redirect('*/*/', [
                    'step' => 4,
                    '_current' => true,
                ]);
            }
        }

        $source = $this->listing->getSetting('additional_data', 'source');

        if ($source == SourceModeBlock::MODE_OTHER) {
            return $this->_redirect('*/*/otherCategories', ['_current' => true]);
        }

        $ebayListing = $this->ebayListingFactory
            ->create()
            ->load($this->getRequest()->getParam('id'), 'listing_id');
        $mode = $ebayListing->getAddProductMode();
        $back = $this->getRequest()->getParam('back');

        if ($mode && $back && !$this->getRequest()->getParam('listing_creation')) {
            return $this->_redirect('*/ebay_listing_product_add/exitToListing', [
                'id' => $ebayListing->getId(),
                '_current' => true,
                'step' => 1
            ]);
        }

        if ($mode && !$back) {
            $this->setSessionValue('mode', $mode);

            return $this->_redirect('*/*/', [
                'step' => 2,
                '_current' => true,
                'skip_get_suggested' => null,
            ]);
        }
        if ($this->getRequest()->isPost()) {
            $mode = $this->getRequest()->getParam('mode');
            $this->setSessionValue('mode', $mode);
            $ebayListing->setAddProductMode($mode);
            $ebayListing->save();

            if ($source) {
                $this->getListingFromRequest()->setSetting(
                    'additional_data',
                    ['ebay_category_settings_mode', $source],
                    $mode
                )->save();
            }

            return $this->_redirect('*/*/', [
                'step' => 2,
                '_current' => true,
                'skip_get_suggested' => null,
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

        if ($mode === null) {
            $mode = $ebayListing->getAddProductMode();
        }

        $allowedModes = [
            CategoryTemplateBlock::MODE_SAME,
            CategoryTemplateBlock::MODE_CATEGORY,
            CategoryTemplateBlock::MODE_PRODUCT,
            CategoryTemplateBlock::MODE_MANUALLY,
        ];

        if ($mode) {
            !in_array($mode, $allowedModes) && $mode = $defaultMode;
        } else {
            $mode = $defaultMode;
        }

        $this->clearSession();

        $block = $this->getLayout()
                      ->createBlock(\Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Product\Category\Settings\Mode::class);
        $block->setData('mode', $mode);

        $this->addContent($block);
        $this->getResultPage()->getConfig()->getTitle()->prepend($this->__('Set Your eBay Categories'));
        $this->setPageHelpLink('display/eBayMagentoV6X/Set+eBay+Categories');

        return $this->getResult();
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

    private function stepValidate()
    {
        if ($this->getRequest()->isPost()) {
            $grid = $this
                ->getLayout()
                ->createBlock(\Ess\M2ePro\Block\Adminhtml\Ebay\Category\Specific\Validation\Grid::class, '', [
                    'listingProductIds' => $this->listing->getChildObject()->getAddedListingProductsIds(),
                ]);

            $this->setAjaxContent($grid);

            return $this->getResult();
        }

        $this->setWizardStep('categoryStepValidation');

        $page = $this
            ->getLayout()
            ->createBlock(\Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Product\Category\Settings\Validate::class, '', [
                'listing' => $this->listing,
            ]);

        $this->addContent($page);
        $this->getResultPage()->getConfig()->getTitle()->prepend(__('Validate Category Specific'));

        return $this->getResult();
    }
}
