<?php

namespace Ess\M2ePro\Controller\Adminhtml\ControlPanel\Inspection;

use Ess\M2ePro\Controller\Adminhtml\Context;
use Ess\M2ePro\Controller\Adminhtml\ControlPanel\Main;
use Ess\M2ePro\Model\ControlPanel\Inspection\Repository;
use Ess\M2ePro\Model\ControlPanel\Inspection\Processor;

class CheckInspection extends Main
{
    /** @var \Ess\M2ePro\Model\ControlPanel\Inspection\Processor */
    private $processor;

    /** @var \Ess\M2ePro\Model\ControlPanel\Inspection\Repository */
    private $repository;

    //########################################

    public function __construct(
        Repository $repository,
        Processor $processor,
        Context $context
    )
    {
        $this->repository = $repository;
        $this->processor = $processor;

        parent::__construct($context);
    }

    public function execute()
    {
        $inspectionTitle = $this->getRequest()->getParam('title');

        $definition = $this->repository->getDefinition($inspectionTitle);
        $result = $this->processor->process($definition);

        $isSuccess = true;
        $metadata = '';
        $message = $this->__('Success');

        if ($result->isSuccess()) {
            $issues = $result->getIssues();

            if (!empty($issues)) {
                $isSuccess = false;
                $lastIssue = end($issues);

                $metadata = $lastIssue->getMetadata();
                $message = $lastIssue->getMessage();
            }
        } else {
            $message = $result->getErrorMessage();
            $isSuccess = false;
        }

        $this->setJsonContent([
            'result'   => $isSuccess,
            'metadata' => $metadata,
            'message'  => $message
        ]);

        return $this->getResult();
    }

    //########################################
}
