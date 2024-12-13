<?php

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Main;

class View extends Main
{
    /** @var \Ess\M2ePro\Block\Adminhtml\Magento\Product\Rule\ViewStateFactory */
    private $viewStateFactory;
    /** @var \Ess\M2ePro\Block\Adminhtml\Magento\Product\Rule\ViewState\Manager */
    private $viewStateManager;
    /** @var \Ess\M2ePro\Model\Magento\Product\RuleFactory */
    private $magentoRuleFactory;
    /** @var \Ess\M2ePro\Model\Amazon\Magento\Product\RuleFactory */
    private $amazonRuleFactory;
    /** @var \Ess\M2ePro\Helper\Data\GlobalData */
    private $globalData;

    public function __construct(
        \Ess\M2ePro\Block\Adminhtml\Magento\Product\Rule\ViewStateFactory $viewStateFactory,
        \Ess\M2ePro\Block\Adminhtml\Magento\Product\Rule\ViewState\Manager $viewStateManager,
        \Ess\M2ePro\Model\Magento\Product\RuleFactory $magentoRuleFactory,
        \Ess\M2ePro\Model\Amazon\Magento\Product\RuleFactory $amazonRuleFactory,
        \Ess\M2ePro\Helper\Data\GlobalData $globalData,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($amazonFactory, $context);

        $this->viewStateFactory = $viewStateFactory;
        $this->viewStateManager = $viewStateManager;
        $this->magentoRuleFactory = $magentoRuleFactory;
        $this->amazonRuleFactory = $amazonRuleFactory;
        $this->globalData = $globalData;
    }

    public function execute()
    {
        if ($this->getRequest()->getQuery('ajax')) {
            $id = $this->getRequest()->getParam('id');
            $listing = $this->amazonFactory->getCachedObjectLoaded('Listing', $id);

            $this->globalData->setValue('view_listing', $listing);

            $listingView = $this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\Amazon\Listing\View::class);

            $this->setRuleModel();

            $this->setAjaxContent($listingView->getGridHtml());

            return $this->getResult();
        }

        if ((bool)$this->getRequest()->getParam('do_list', false)) {
            $this->getHelper('Data\Session')->setValue(
                'products_ids_for_list',
                implode(',', $this->getHelper('Data\Session')->getValue('added_products_ids'))
            );

            return $this->_redirect('*/*/*', [
                '_current' => true,
                'do_list' => null,
            ]);
        }

        $id = $this->getRequest()->getParam('id');

        try {
            $listing = $this->amazonFactory->getCachedObjectLoaded('Listing', $id);
        } catch (\Ess\M2ePro\Model\Exception\Logic $e) {
            $this->getMessageManager()->addError($this->__('Listing does not exist.'));

            return $this->_redirect('*/amazon_listing/index');
        }

        $listingProductsIds = $listing->getSetting('additional_data', 'adding_listing_products_ids');

        if (!empty($listingProductsIds)) {
            $step = $listing->getSetting('additional_data', 'wizard_current_step', 0);
            return $this->_redirect('*/amazon_listing_product_add/index', [
                'id' => $id,
                'not_completed' => 1,
                'step' => $step,
            ]);
        }

        // Check listing lock object
        // ---------------------------------------
        if ($listing->isSetProcessingLock('products_in_action')) {
            $this->getMessageManager()->addNotice(
                $this->__('Some Amazon request(s) are being processed now.')
            );
        }
        // ---------------------------------------

        $this->globalData->setValue('view_listing', $listing);

        $this->setPageHelpLink('m2e-pro-listings');

        $this->getResultPage()->getConfig()->getTitle()->prepend(
            __('Listing "%listing_title"', ['listing_title' => $listing->getTitle()])
        );

        $this->addContent($this
            ->getLayout()
            ->createBlock(\Ess\M2ePro\Block\Adminhtml\Amazon\Listing\View::class));

        $this->addContent($this
            ->getLayout()
            ->createBlock(\Ess\M2ePro\Block\Adminhtml\Amazon\ProductType\Validate\Popup::class));

        $this->setRuleModel();

        return $this->getResult();
    }

    private function setRuleModel(): void
    {
        if ($this->isViewModeMagento()) {
            $ruleModelNick = \Ess\M2ePro\Model\Magento\Product\Rule::NICK;
            $viewKey = $this->buildPrefix($ruleModelNick) . '_amazon_view_magento';
        } else {
            $ruleModelNick = \Ess\M2ePro\Model\Amazon\Magento\Product\Rule::NICK;
            $viewKey = $this->buildPrefix($ruleModelNick);
        }

        $getRuleBySessionData = function () {
            return $this->createRuleBySessionData();
        };
        $ruleModel = $this->viewStateManager->getRuleWithViewState(
            $this->viewStateFactory->create($viewKey),
            $ruleModelNick,
            $getRuleBySessionData,
            $this->getStoreId()
        );

        $this->globalData->setValue('rule_model', $ruleModel);
    }

    private function createRuleBySessionData(): \Ess\M2ePro\Model\Magento\Product\Rule
    {
        $prefix = $this->buildPrefix('amazon_rule_listing_view');
        $this->globalData->setValue('rule_prefix', $prefix);

        if ($this->isViewModeMagento()) {
            $prefix = $prefix . '_amazon_view_magento';
            $ruleModel = $this->magentoRuleFactory->create($prefix, $this->getStoreId());
        } else {
            $ruleModel = $this->amazonRuleFactory->create($prefix, $this->getStoreId());
        }

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
        $magentoViewMode = \Ess\M2ePro\Block\Adminhtml\Amazon\Listing\View\Switcher::VIEW_MODE_MAGENTO;
        $sessionParamName = 'amazonListingView' . $this->getListingDataFromGlobalData()['id'] . 'view_mode';

        if (
            $this->getRequest()->getParam('view_mode') == $magentoViewMode
            || $magentoViewMode == $this->getHelper('Data\Session')->getValue($sessionParamName)
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
