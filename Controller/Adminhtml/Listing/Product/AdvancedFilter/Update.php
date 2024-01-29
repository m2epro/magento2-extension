<?php

declare(strict_types=1);

namespace Ess\M2ePro\Controller\Adminhtml\Listing\Product\AdvancedFilter;

class Update extends \Ess\M2ePro\Controller\Adminhtml\Base
{
    /** @var \Ess\M2ePro\Model\Listing\Product\AdvancedFilter\Manager */
    private $ruleManager;
    /** @var \Ess\M2ePro\Model\Listing\Product\AdvancedFilter\Repository */
    private $repository;

    public function __construct(
        \Ess\M2ePro\Model\Listing\Product\AdvancedFilter\Manager $ruleManager,
        \Ess\M2ePro\Model\Listing\Product\AdvancedFilter\Repository $repository,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($context);

        $this->ruleManager = $ruleManager;
        $this->repository = $repository;
    }

    public function execute()
    {
        $request = $this->getRequest();
        parse_str($request->getPostValue('form_data'), $formData);
        $formData = $formData['rule'][$request->getPostValue('prefix')] ?? null;
        $title = $request->getPostValue('title');
        $ruleEntityId = $request->getPostValue('rule_entity_id');

        if (empty($ruleEntityId) || $formData === null) {
            throw new \Exception('Invalid input');
        }

        if (empty($title)) {
            $this->setJsonContent(
                ['result' => false, 'message' => __('Please enter a title to save the filter')]
            );

            return $this->getResult();
        }

        $advancedFilter = $this->repository->getAdvancedFilter((int)$ruleEntityId);
        $rule = $this->ruleManager->getRuleModelByNick($advancedFilter->getModelNick());
        if (!$this->ruleManager->isConditionsValid($formData, $rule)) {
            $this->setJsonContent(
                ['result' => false, 'message' => __('Please specify filter conditions before saving it')]
            );
            return $this->getResult();
        }

        $this->ruleManager->update($advancedFilter, $title, $formData);
        $this->setJsonContent(['result' => true]);

        return $this->getResult();
    }
}
