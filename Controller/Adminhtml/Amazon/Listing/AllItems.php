<?php

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing;

class AllItems extends \Ess\M2ePro\Controller\Adminhtml\Amazon\Listing
{
    private \Ess\M2ePro\Block\Adminhtml\Magento\Product\Rule\ViewStateFactory $viewStateFactory;
    private \Ess\M2ePro\Block\Adminhtml\Magento\Product\Rule\ViewState\Manager $viewStateManager;
    private \Ess\M2ePro\Model\Amazon\Magento\Product\RuleFactory $ruleFactory;
    private \Ess\M2ePro\Helper\Data\GlobalData $globalData;
    private \Ess\M2ePro\Helper\Data\Session $sessionHelper;

    public function __construct(
        \Ess\M2ePro\Block\Adminhtml\Magento\Product\Rule\ViewStateFactory $viewStateFactory,
        \Ess\M2ePro\Block\Adminhtml\Magento\Product\Rule\ViewState\Manager $viewStateManager,
        \Ess\M2ePro\Model\Amazon\Magento\Product\RuleFactory $ruleFactory,
        \Ess\M2ePro\Helper\Data\GlobalData $globalData,
        \Ess\M2ePro\Helper\Data\Session $sessionHelper,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($amazonFactory, $context);
        $this->viewStateFactory = $viewStateFactory;
        $this->viewStateManager = $viewStateManager;
        $this->ruleFactory = $ruleFactory;
        $this->globalData = $globalData;
        $this->sessionHelper = $sessionHelper;
    }

    /**
     * @ingeritdoc
     */
    public function execute()
    {
        $this->setRuleModel();

        if ($this->getRequest()->getQuery('ajax')) {
            $this->setAjaxContent(
                $this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\Amazon\Listing\AllItems\Grid::class)
            );

            return $this->getResult();
        }

        $this->addContent($this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\Amazon\Listing\AllItems::class));
        $this->getResultPage()->getConfig()->getTitle()->prepend(__('All Items'));

        return $this->getResult();
    }

    private function setRuleModel(): void
    {
        $viewKey = $this->buildPrefix(\Ess\M2ePro\Model\Amazon\Magento\Product\Rule::NICK);
        $getRuleBySessionData = function () {
            return $this->createRuleBySessionData();
        };

        $ruleModel = $this->viewStateManager->getRuleWithViewState(
            $this->viewStateFactory->create($viewKey),
            \Ess\M2ePro\Model\Amazon\Magento\Product\Rule::NICK,
            $getRuleBySessionData
        );

        $this->globalData->setValue('rule_model', $ruleModel);
    }

    private function buildPrefix(string $root): string
    {
        return $root . '_allitems';
    }

    private function createRuleBySessionData(): \Ess\M2ePro\Model\Amazon\Magento\Product\Rule
    {
        $prefix = $this->buildPrefix('amazon_rule_allitems');
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
