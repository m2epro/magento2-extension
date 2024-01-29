<?php

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Listing;

use Ess\M2ePro\Controller\Adminhtml\Walmart\Main;

class View extends Main
{
    /** @var \Ess\M2ePro\Block\Adminhtml\Magento\Product\Rule\ViewStateFactory */
    private $viewStateFactory;
    /** @var \Ess\M2ePro\Block\Adminhtml\Magento\Product\Rule\ViewState\Manager */
    private $viewStateManager;
    /** @var \Ess\M2ePro\Model\Magento\Product\RuleFactory */
    private $magentoRuleFactory;
    /** @var \Ess\M2ePro\Model\Walmart\Magento\Product\RuleFactory */
    private $walmartRuleFactory;
    /** @var \Ess\M2ePro\Helper\Data\GlobalData */
    private $globalData;
    /** @var \Ess\M2ePro\Helper\Data\Session */
    private $sessionHelper;

    public function __construct(
        \Ess\M2ePro\Block\Adminhtml\Magento\Product\Rule\ViewStateFactory $viewStateFactory,
        \Ess\M2ePro\Block\Adminhtml\Magento\Product\Rule\ViewState\Manager $viewStateManager,
        \Ess\M2ePro\Model\Magento\Product\RuleFactory $magentoRuleFactory,
        \Ess\M2ePro\Model\Walmart\Magento\Product\RuleFactory $walmartRuleFactory,
        \Ess\M2ePro\Helper\Data\GlobalData $globalData,
        \Ess\M2ePro\Helper\Data\Session $sessionHelper,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($walmartFactory, $context);

        $this->viewStateFactory = $viewStateFactory;
        $this->viewStateManager = $viewStateManager;
        $this->magentoRuleFactory = $magentoRuleFactory;
        $this->walmartRuleFactory = $walmartRuleFactory;
        $this->globalData = $globalData;
        $this->sessionHelper = $sessionHelper;
    }

    public function execute()
    {
        if ($this->getRequest()->getQuery('ajax')) {
            $id = $this->getRequest()->getParam('id');
            $listing = $this->walmartFactory->getCachedObjectLoaded('Listing', $id);

            $this->globalData->setValue('view_listing', $listing);

            $listingView = $this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\Walmart\Listing\View::class);

            $this->setRuleModel();

            $this->setAjaxContent($listingView->getGridHtml());

            return $this->getResult();
        }

        if ((bool)$this->getRequest()->getParam('do_list', false)) {
            $this->sessionHelper->setValue(
                'products_ids_for_list',
                implode(',', $this->sessionHelper->getValue('added_products_ids'))
            );

            return $this->_redirect('*/*/*', [
                '_current' => true,
                'do_list' => null,
            ]);
        }

        $id = $this->getRequest()->getParam('id');

        try {
            $listing = $this->walmartFactory->getCachedObjectLoaded('Listing', $id);
        } catch (\Ess\M2ePro\Model\Exception\Logic $e) {
            $this->getMessageManager()->addError($this->__('Listing does not exist.'));

            return $this->_redirect('*/walmart_listing/index');
        }

        $listingProductsIds = $listing->getSetting('additional_data', 'adding_listing_products_ids');

        if (!empty($listingProductsIds)) {
            return $this->_redirect('*/walmart_listing_product_add/index', [
                'id' => $id,
                'not_completed' => 1,
                'step' => 3,
            ]);
        }

        // Check listing lock object
        // ---------------------------------------
        if ($listing->isSetProcessingLock('products_in_action')) {
            $this->getMessageManager()->addNotice(
                $this->__('Some Walmart request(s) are being processed now.')
            );
        }
        // ---------------------------------------

        $this->globalData->setValue('view_listing', $listing);

        $this->setPageHelpLink('help/m2/walmart-integration/m2e-pro-listing-set-up/managing-listing-products');

        $this->getResultPage()->getConfig()->getTitle()->prepend(
            $this->__('M2E Pro Listing "%listing_title%"', $listing->getTitle())
        );

        $this->addContent($this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\Walmart\Listing\View::class));

        $this->setRuleModel();

        return $this->getResult();
    }

    private function setRuleModel(): void
    {
        if ($this->isViewModeMagento()) {
            $ruleModelNick = \Ess\M2ePro\Model\Magento\Product\Rule::NICK;
            $viewKey = $this->buildPrefix($ruleModelNick) . '_walmart_view_magento';
        } else {
            $ruleModelNick = \Ess\M2ePro\Model\Walmart\Magento\Product\Rule::NICK;
            $viewKey = $this->buildPrefix($ruleModelNick);
        }

        $getRuleBySessionData = function () {
            return $this->createRuleBySessionData();
        };
        $ruleModel = $this->viewStateManager->getRuleWithViewState(
            $this->viewStateFactory->create($viewKey),
            $ruleModelNick,
            $this->getStoreId(),
            $getRuleBySessionData
        );

        $this->globalData->setValue('rule_model', $ruleModel);
    }

    private function createRuleBySessionData(): \Ess\M2ePro\Model\Magento\Product\Rule
    {
        $prefix = $this->buildPrefix('walmart_rule_listing_view');
        $this->globalData->setValue('rule_prefix', $prefix);

        if ($this->isViewModeMagento()) {
            $prefix = $prefix . '_view_magento';
            $ruleModel = $this->magentoRuleFactory->create($prefix, $this->getStoreId());
        } else {
            $ruleModel = $this->walmartRuleFactory->create($prefix, $this->getStoreId());
        }

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

        return $root . '_listing' . (isset($listing['id']) ? '_' . $listing['id'] : '');
    }

    private function getStoreId(): int
    {
        $listing = $this->getListingDataFromGlobalData();

        if (empty($listing['store_id'])) {
            return 0;
        }

        return (int)$listing['store_id'];
    }

    private function isViewModeMagento(): bool
    {
        $isViewModeMagento = false;
        $magentoViewMode = \Ess\M2ePro\Block\Adminhtml\Walmart\Listing\View\Switcher::VIEW_MODE_MAGENTO;
        $sessionParamName = 'walmartListingView' . $this->getListingDataFromGlobalData()['id'] . 'view_mode';

        if (
            $this->getRequest()->getParam('view_mode') == $magentoViewMode
            || $magentoViewMode == $this->sessionHelper->getValue($sessionParamName)
        ) {
            $isViewModeMagento = true;
        }

        return $isViewModeMagento;
    }

    private function getListingDataFromGlobalData(): array
    {
        return $this->globalData->getValue('view_listing')->getData();
    }
}
