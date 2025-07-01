<?php

declare(strict_types=1);

namespace Ess\M2ePro\Block\Adminhtml\Magento\Product\Rule\ViewState;

class Manager
{
    /** @var \Ess\M2ePro\Model\Listing\Product\AdvancedFilter\Repository */
    private $repository;
    /** @var \Ess\M2ePro\Model\Listing\Product\AdvancedFilter\Manager */
    private $ruleManager;
    /** @var \Magento\Framework\App\RequestInterface */
    private $request;

    public function __construct(
        \Ess\M2ePro\Model\Listing\Product\AdvancedFilter\Repository $repository,
        \Ess\M2ePro\Model\Listing\Product\AdvancedFilter\Manager $ruleManager,
        \Magento\Framework\App\RequestInterface $request
    ) {
        $this->repository = $repository;
        $this->ruleManager = $ruleManager;
        $this->request = $request;
    }

    public function getRuleWithViewState(
        \Ess\M2ePro\Block\Adminhtml\Magento\Product\Rule\ViewState $viewState,
        string $ruleModelNick,
        callable $getRuleBySessionData,
        ?int $storeId = null
    ): \Ess\M2ePro\Model\Magento\Product\Rule {

        // State - Creation
        //  ---------------------------------------------
        if ($viewState->isWithoutState()) {
            if ($this->repository->isExistItemsWithModelNick($ruleModelNick)) {
                return $this->getRuleWithUnselectedState($viewState, $ruleModelNick, $storeId);
            }
            $viewState->setStateCreation();
        }

        if ($this->request->getPost('create_new_filter')) {
            $viewState->setStateCreation();
            $viewState->setIsShowRuleBlock(true);
        }

        if ($viewState->isStateCreation() && $this->request->getPost('creating_back')) {
            return $this->getRuleWithUnselectedState($viewState, $ruleModelNick, $storeId);
        }

        if ($viewState->isStateCreation()) {
            $rule = $getRuleBySessionData();
            $rule->setViewSate($viewState);

            return $rule;
        }

        // State - Updating
        //  ---------------------------------------------

        if (
            $this->request->getPost('rule_entity_id')
            && $this->request->getPost('rule_updating')
        ) {
            $viewState->setStateUpdating((int)$this->request->getPost('rule_entity_id'));
        }

        if ($viewState->isStateUpdating() && $this->request->getPost('is_reset')) {
            $rule = $this->ruleManager->getRuleModelByNick($ruleModelNick, $storeId);
            $rule->setViewSate($viewState);

            return $rule;
        }

        if ($viewState->isStateUpdating() && $this->request->getPost('updating_back')) {
            return $this->getRuleWithUnselectedState($viewState, $ruleModelNick, $storeId);
        }

        if ($viewState->isStateUpdating()) {
            $rule = $this->ruleManager->getRuleWithSavedConditions(
                $viewState->getUpdatedEntityId(),
                $ruleModelNick,
                $storeId
            );
            $viewState->setIsShowRuleBlock(true);
            $rule->setViewSate($viewState);

            return $rule;
        }

        // State - Selected
        //  ---------------------------------------------

        if ($this->request->getPost('rule_entity_id')) {
            $viewState->setStateSelect((int)$this->request->getPost('rule_entity_id'));
        }

        if ($viewState->isStateSelected() && $this->request->getPost('is_reset')) {
            return $this->getRuleWithUnselectedState($viewState, $ruleModelNick, $storeId);
        }

        if ($viewState->isStateSelected()) {
            $rule = $this->ruleManager->getRuleWithSavedConditions(
                $viewState->getSelectedEntityId(),
                $ruleModelNick,
                $storeId
            );

            $viewState->setIsShowRuleBlock(true);
            $rule->setViewSate($viewState);

            return $rule;
        }

        // State - Unselected
        //  ---------------------------------------------

        if ($viewState->isStateUnselected()) {
            return $this->getRuleWithUnselectedState($viewState, $ruleModelNick, $storeId);
        }

        //  ---------------------------------------------

        throw new \LogicException('Unresolved View State');
    }

    private function getRuleWithUnselectedState(
        \Ess\M2ePro\Block\Adminhtml\Magento\Product\Rule\ViewState $viewState,
        string $ruleModelNick,
        ?int $storeId = null
    ): \Ess\M2ePro\Model\Magento\Product\Rule {
        $viewState->setStateUnselect();
        $rule = $this->ruleManager->getRuleModelByNick($ruleModelNick, $storeId);
        $rule->setViewSate($viewState);

        return $rule;
    }
}
