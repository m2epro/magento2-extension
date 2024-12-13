<?php

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing;

use Ess\M2ePro\Model\Ebay\Listing\Wizard\Repository as WizardRepository;
use Ess\M2ePro\Model\Ebay\Listing\Wizard;

class View extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing
{
    /** @var \Ess\M2ePro\Block\Adminhtml\Magento\Product\Rule\ViewStateFactory */
    private $viewStateFactory;
    /** @var \Ess\M2ePro\Helper\Data\GlobalData */
    private $globalData;
    /** @var \Ess\M2ePro\Helper\Data\Session */
    private $sessionHelper;
    /** @var \Ess\M2ePro\Model\Ebay\Magento\Product\RuleFactory */
    private $ruleFactory;
    /** @var \Ess\M2ePro\Block\Adminhtml\Magento\Product\Rule\ViewState\Manager */
    private $viewStateManager;

    private WizardRepository $wizardRepository;

    public function __construct(
        \Ess\M2ePro\Block\Adminhtml\Magento\Product\Rule\ViewStateFactory $viewStateFactory,
        \Ess\M2ePro\Block\Adminhtml\Magento\Product\Rule\ViewState\Manager $viewStateManager,
        \Ess\M2ePro\Model\Ebay\Magento\Product\RuleFactory $ruleFactory,
        \Ess\M2ePro\Helper\Data\GlobalData $globalData,
        \Ess\M2ePro\Helper\Data\Session $sessionHelper,
        WizardRepository $wizardRepository,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($ebayFactory, $context);

        $this->viewStateFactory = $viewStateFactory;
        $this->globalData = $globalData;
        $this->sessionHelper = $sessionHelper;
        $this->ruleFactory = $ruleFactory;
        $this->viewStateManager = $viewStateManager;
        $this->wizardRepository = $wizardRepository;
    }

    public function execute()
    {
        if ($this->getRequest()->getQuery('ajax')) {
            $id = $this->getRequest()->getParam('id');
            $listing = $this->ebayFactory->getCachedObjectLoaded('Listing', $id);

            $this->globalData->setValue('view_listing', $listing);

            $this->setRuleModel();

            $this->setAjaxContent(
                $this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\Ebay\Listing\View::class)->getGridHtml()
            );

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
                'view_mode' => \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\View\Switcher::VIEW_MODE_EBAY,
            ]);
        }

        $id = $this->getRequest()->getParam('id');

        try {
            $listing = $this->ebayFactory->getCachedObjectLoaded('Listing', $id);
        } catch (\Ess\M2ePro\Model\Exception\Logic $e) {
            $this->getMessageManager()->addError($this->__('Listing does not exist.'));

            return $this->_redirect('*/ebay_listing/index');
        }

        $existWizard = $this->wizardRepository->findNotCompletedByListingAndType($listing, Wizard::TYPE_GENERAL);

        if ($existWizard !== null && !$existWizard->isCompleted()) {
            $this->getMessageManager()->addNotice(
                $this->__(
                    'Please make sure you finish adding new Products before moving to the next step.'
                )
            );

            return $this->_redirect('*/ebay_listing_wizard/index', ['id' => $existWizard->getId()]);
        }

        $this->globalData->setValue('view_listing', $listing);

        $this->setRuleModel();

        $this->setPageHelpLink('listings');

        $this->getResultPage()->getConfig()->getTitle()->prepend(
            __('Listing "%listing_title"', ['listing_title' =>  $listing->getTitle()])
        );

        $this
            ->getLayout()
            ->createBlock(\Ess\M2ePro\Block\Adminhtml\Ebay\Category\Specific\Validation\Popup::class);

        $this->addContent($this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\Ebay\Listing\View::class));

        return $this->getResult();
    }

    private function setRuleModel(): void
    {
        $viewKey = $this->buildPrefix(\Ess\M2ePro\Model\Ebay\Magento\Product\Rule::NICK);
        $getRuleBySessionData = function () {
            return $this->createRuleBySessionData();
        };
        $ruleModel = $this->viewStateManager->getRuleWithViewState(
            $this->viewStateFactory->create($viewKey),
            \Ess\M2ePro\Model\Ebay\Magento\Product\Rule::NICK,
            $getRuleBySessionData,
            $this->getStoreId()
        );

        $this->globalData->setValue('rule_model', $ruleModel);
    }

    private function createRuleBySessionData(): \Ess\M2ePro\Model\Ebay\Magento\Product\Rule
    {
        $prefix = $this->buildPrefix('ebay_rule_view');
        $this->globalData->setValue('rule_prefix', $prefix);

        $ruleModel = $this->ruleFactory->create($prefix, $this->getStoreId());

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
        $listing = $this->getListingFromGlobalData();

        return $root . '_listing' . (isset($listing['id']) ? '_' . $listing['id'] : '');
    }

    private function getStoreId(): int
    {
        $listing = $this->getListingFromGlobalData();

        if (empty($listing['store_id'])) {
            return 0;
        }

        return (int)$listing['store_id'];
    }

    private function getListingFromGlobalData(): ?\Ess\M2ePro\Model\Listing
    {
        return $this->globalData->getValue('view_listing');
    }
}
