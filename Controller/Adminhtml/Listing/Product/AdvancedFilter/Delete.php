<?php

namespace Ess\M2ePro\Controller\Adminhtml\Listing\Product\AdvancedFilter;

class Delete extends \Ess\M2ePro\Controller\Adminhtml\Base
{
    /** @var \Ess\M2ePro\Block\Adminhtml\Magento\Product\Rule\ViewStateFactory */
    private $viewStateFactory;
    /** @var \Ess\M2ePro\Model\Listing\Product\AdvancedFilter\Repository */
    private $repository;

    public function __construct(
        \Ess\M2ePro\Block\Adminhtml\Magento\Product\Rule\ViewStateFactory $viewStateFactory,
        \Ess\M2ePro\Model\Listing\Product\AdvancedFilter\Repository $repository,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($context);

        $this->viewStateFactory = $viewStateFactory;
        $this->repository = $repository;
    }

    public function execute()
    {
        $ruleEntityId = $this->getRequest()->getPostValue('rule_entity_id');
        $viewStateKey = $this->getRequest()->getPostValue('view_state_key');
        if (empty($ruleEntityId) || empty($viewStateKey)) {
            throw new \Exception('Invalid input');
        }

        $advancedFilter = $this->repository->getAdvancedFilter((int)$ruleEntityId);
        $modelNick = $advancedFilter->getModelNick();
        $this->repository->remove($advancedFilter);

        $viewState = $this->viewStateFactory->create($viewStateKey);
        if (!$this->repository->isExistItemsWithModelNick($modelNick)) {
            $viewState->setStateCreation();
        } else {
            $viewState->setStateUnselect();
        }

        return $this->getResult();
    }
}
