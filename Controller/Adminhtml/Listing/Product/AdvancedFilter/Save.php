<?php

declare(strict_types=1);

namespace Ess\M2ePro\Controller\Adminhtml\Listing\Product\AdvancedFilter;

class Save extends \Ess\M2ePro\Controller\Adminhtml\Base
{
    /** @var \Ess\M2ePro\Model\Listing\Product\AdvancedFilter\Manager */
    private $ruleManager;
    /** @var \Ess\M2ePro\Block\Adminhtml\Magento\Product\Rule\ViewStateFactory */
    private $viewStateFactory;

    public function __construct(
        \Ess\M2ePro\Model\Listing\Product\AdvancedFilter\Manager $ruleManager,
        \Ess\M2ePro\Block\Adminhtml\Magento\Product\Rule\ViewStateFactory $viewStateFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($context);

        $this->ruleManager = $ruleManager;
        $this->viewStateFactory = $viewStateFactory;
    }

    public function execute()
    {
        $request = $this->getRequest();
        parse_str($request->getPostValue('form_data'), $formData);
        $formData = $formData['rule'][$request->getPostValue('prefix')] ?? null;
        $title = $request->getPostValue('title');
        $viewStateKey = $request->getPostValue('view_state_key');
        $modelNick = $request->getPostValue('rule_nick');

        if (
             empty($modelNick)
            || empty($viewStateKey)
            || $formData === null
        ) {
            throw new \Exception('Invalid input');
        }

        if (empty($title)) {
            $this->setJsonContent(
                ['result' => false, 'message' => __('Please enter a title to save the filter')]
            );

            return $this->getResult();
        }

        $rule = $this->ruleManager->getRuleModelByNick($modelNick);
        if (!$this->ruleManager->isConditionsValid($formData, $rule)) {
            $this->setJsonContent(
                ['result' => false, 'message' => __('Please specify filter conditions before saving it')]
            );

            return $this->getResult();
        }

        $entity = $this->ruleManager->save($rule, $title, $formData);
        $viewState = $this->viewStateFactory->create($viewStateKey);
        $viewState->setStateSelect((int)$entity->getId(), true);

        $this->setJsonContent(['result' => true]);

        return $this->getResult();
    }
}
