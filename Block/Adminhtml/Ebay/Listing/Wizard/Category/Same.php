<?php

declare(strict_types=1);

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Wizard\Category;

use Ess\M2ePro\Block\Adminhtml\Magento\AbstractContainer;
use Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Wizard\WizardTrait;
use Ess\M2ePro\Block\Adminhtml\Magento\Context\Widget;
use Ess\M2ePro\Model\Ebay\Listing\Wizard\Ui\RuntimeStorage as WizardRuntimeStorage;
use Ess\M2ePro\Model\Listing\Ui\RuntimeStorage as ListingRuntimeStorage;
use Ess\M2ePro\Model\Ebay\Listing\Wizard\ManagerFactory;
use Magento\Framework\UrlInterface;

class Same extends AbstractContainer
{
    use WizardTrait;

    private ListingRuntimeStorage $uiListingRuntimeStorage;
    private UrlInterface $urlBuilder;
    private WizardRuntimeStorage $uiWizardRuntimeStorage;
    private ManagerFactory $wizardManagerFactory;
    private \Ess\M2ePro\Helper\Data $dataHelper;

    public function __construct(
        ManagerFactory $wizardManagerFactory,
        ListingRuntimeStorage $uiListingRuntimeStorage,
        Widget $context,
        WizardRuntimeStorage $uiWizardRuntimeStorage,
        UrlInterface $urlBuilder,
        \Ess\M2ePro\Helper\Data $dataHelper,
        array $data = []
    ) {
        $this->uiListingRuntimeStorage = $uiListingRuntimeStorage;
        $this->uiWizardRuntimeStorage = $uiWizardRuntimeStorage;
        $this->wizardManagerFactory = $wizardManagerFactory;
        $this->urlBuilder = $urlBuilder;
        $this->dataHelper = $dataHelper;

        parent::__construct($context, $data);
    }

    protected function _construct()
    {
        parent::_construct();

        $wizardId = (int)$this->getWizardIdFromRequest();
        $wizardManager = $this->wizardManagerFactory->createById($wizardId);

        $this->setId('listingCategoryChooser');

        //@todo remove direct request call
        $this->prepareButtons(
            [
                'label' => __('Continue'),
                'class' => 'action-primary forward',
                'onclick' => sprintf(
                    "EbayListingCategoryObj.modeSameSubmitData('%s')",
                    $this->getUrl('*/ebay_listing_wizard_category/assignModeSame', ['id' => $this->getWizardIdFromRequest()]),
                ),
            ],
            $wizardManager
        );

        $this->_headerText = __('Categories');
    }

    protected function _beforeToHtml()
    {
        $urlBuilder = $this->urlBuilder;

        $this->jsUrl->addUrls($this->dataHelper->getControllerActions('Ebay\Category', ['_current' => true]));
        $this->jsUrl->addUrls($this->dataHelper->getControllerActions('Ebay\Marketplace', ['_current' => true]));
        /**
         * overrides specif routes
         *
         * @todo refactor
         */
        $this->jsUrl->addUrls(
            [
                'ebay_category/editCategory' => $urlBuilder->getUrl(
                    '*/ebay_category/editCategory'
                ),
                'ebay_category/getCategoryAttributesHtml' => $urlBuilder->getUrl(
                    '*/ebay_category/getCategoryAttributesHtml'
                ),
                'ebay_category/getChildCategories' => $urlBuilder->getUrl(
                    '*/ebay_listing_wizard_category/getChildCategories'
                ),
                'ebay_category/getChooserEditHtml' => $urlBuilder->getUrl(
                    '*/ebay_category/getChooserEditHtml'
                ),
                'ebay_category/getCountsOfAttributes' => $urlBuilder->getUrl(
                    '*/ebay_category/getCountsOfAttributes'
                ),
                'ebay_category/getEditedCategoryInfo' => $urlBuilder->getUrl(
                    '*/ebay_category/getEditedCategoryInfo'
                ),
                'ebay_category/getRecent' => $urlBuilder->getUrl(
                    '*/ebay_category/getRecent'
                ),
                'ebay_category/getSelectedCategoryDetails' => $urlBuilder->getUrl(
                    '*/ebay_listing_wizard_category/getSelectedCategoriesDetails'
                ),
                'ebay_category/saveCategoryAttributes' => $urlBuilder->getUrl(
                    '*/ebay_category/saveCategoryAttributes'
                ),
                'ebay_category/saveCategoryAttributesAjax' => $urlBuilder->getUrl(
                    '*/ebay_category/saveCategoryAttributesAjax'
                ),
                'ebay_category/search' => $urlBuilder->getUrl(
                    '*/ebay_category/search'
                ),
                'ebay_category/getCategorySpecificHtml' => $urlBuilder->getUrl(
                    '*/ebay_listing_wizard_category/getCategorySpecificHtml'
                ),
            ],
        );

        return parent::_beforeToHtml();
    }

    protected function _toHtml()
    {
        $parentHtml = parent::_toHtml();
        $listing = $this->uiListingRuntimeStorage->getListing();

        //$viewHeaderBlock = $this->getLayout()->createBlock(Header::class, '', [
        //    'data' => ['listing' => $listing],
        //]);
        // ---------------------------------------

        // ---------------------------------------

        /** @var \Ess\M2ePro\Block\Adminhtml\Ebay\Template\Category\Chooser $chooserBlock */
        $chooserBlock = $this->getLayout()
                             ->createBlock(\Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Wizard\Category\ModeSame\Chooser::class);
        $chooserBlock->setMarketplaceId($listing->getMarketplaceId());
        $chooserBlock->setAccountId($listing->getAccountId());
        $chooserBlock->setCategoriesData($this->getData('categories_data'));

        // ---------------------------------------

        $this->js->addOnReadyJs(
            <<<JS
require([
    'M2ePro/Ebay/Listing/Category',
    'M2ePro/Ebay/Template/Category/Chooser'
], function(){
    window.EbayListingCategoryObj = new EbayListingCategory(null);

    EbayTemplateCategoryChooserObj.confirmSpecificsCallback = function() {
        var typeMain = M2ePro.php.constant('Ess_M2ePro_Helper_Component_Ebay_Category::TYPE_EBAY_MAIN');
        this.selectedCategories[typeMain].specific = this.selectedSpecifics;
    }.bind(EbayTemplateCategoryChooserObj);

    EbayTemplateCategoryChooserObj.resetSpecificsCallback = function() {
        var typeMain = M2ePro.php.constant('Ess_M2ePro_Helper_Component_Ebay_Category::TYPE_EBAY_MAIN');
        this.selectedCategories[typeMain].specific = this.selectedSpecifics;
    }.bind(EbayTemplateCategoryChooserObj);

})
JS
        );

        return <<<HTML

<div id="ebay_category_chooser">{$chooserBlock->toHtml()}</div>
{$parentHtml}
HTML;
    }
}
