<?php

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Listing;

use Ess\M2ePro\Controller\Adminhtml\Walmart\Main;

class AllItems extends Main
{
    private \Ess\M2ePro\Block\Adminhtml\Magento\Product\Rule\ViewStateFactory $viewStateFactory;
    private \Ess\M2ePro\Block\Adminhtml\Magento\Product\Rule\ViewState\Manager $viewStateManager;
    private \Ess\M2ePro\Model\Walmart\Magento\Product\RuleFactory $ruleFactory;
    private \Ess\M2ePro\Helper\Data\GlobalData $globalData;
    private \Ess\M2ePro\Helper\Data\Session $sessionHelper;

    public function __construct(
        \Ess\M2ePro\Block\Adminhtml\Magento\Product\Rule\ViewStateFactory $viewStateFactory,
        \Ess\M2ePro\Block\Adminhtml\Magento\Product\Rule\ViewState\Manager $viewStateManager,
        \Ess\M2ePro\Model\Walmart\Magento\Product\RuleFactory $ruleFactory,
        \Ess\M2ePro\Helper\Data\GlobalData $globalData,
        \Ess\M2ePro\Helper\Data\Session $sessionHelper,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($walmartFactory, $context);
        $this->viewStateFactory = $viewStateFactory;
        $this->viewStateManager = $viewStateManager;
        $this->ruleFactory = $ruleFactory;
        $this->globalData = $globalData;
        $this->sessionHelper = $sessionHelper;
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Ess_M2ePro::walmart_listings');
    }

    public function execute()
    {
        $this->setRuleModel();

        if ($this->isAjax()) {
            $gridBlock = \Ess\M2ePro\Block\Adminhtml\Walmart\Listing\AllItems\Grid::class;
            $this->setAjaxContent(
                $this->getLayout()->createBlock($gridBlock)
            );

            return $this->getResult();
        }

        $this->addContent($this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\Walmart\Listing\AllItems::class));
        $this->getResultPage()->getConfig()->getTitle()->prepend($this->__('All Items'));

        return $this->getResult();
    }

    private function setRuleModel(): void
    {
        $viewKey = $this->buildPrefix(\Ess\M2ePro\Model\Walmart\Magento\Product\Rule::NICK);
        $getRuleBySessionData = function () {
            return $this->createRuleBySessionData();
        };

        $ruleModel = $this->viewStateManager->getRuleWithViewState(
            $this->viewStateFactory->create($viewKey),
            \Ess\M2ePro\Model\Walmart\Magento\Product\Rule::NICK,
            $getRuleBySessionData
        );

        $this->globalData->setValue('rule_model', $ruleModel);
    }

    private function buildPrefix(string $root): string
    {
        return $root . '_allitems';
    }

    private function createRuleBySessionData(): \Ess\M2ePro\Model\Walmart\Magento\Product\Rule
    {
        $prefix = $this->buildPrefix('walmart_rule_allitems');
        $this->globalData->setValue('rule_prefix', $prefix);

        $ruleModel = $this->ruleFactory->create($prefix, null);

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
}
