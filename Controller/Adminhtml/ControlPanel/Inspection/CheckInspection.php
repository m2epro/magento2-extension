<?php

namespace Ess\M2ePro\Controller\Adminhtml\ControlPanel\Inspection;

use Ess\M2ePro\Controller\Adminhtml\Context;
use Ess\M2ePro\Controller\Adminhtml\ControlPanel\Main;
use Ess\M2ePro\Model\ControlPanel\Inspection\Manager;

class CheckInspection extends Main
{
    /** @var \Ess\M2ePro\Model\ControlPanel\Inspection\Manager Manager */
    private $inspectionManager;

    //########################################
    public function __construct(Manager $inspectionManager, Context $context)
    {
        $this->inspectionManager = $inspectionManager;

        parent::__construct($context);
    }

    public function execute()
    {
        $inspectionName = $this->getRequest()->getParam('name');
        $results = $this->inspectionManager->runInspection($inspectionName);

        $isSuccess = false;
        $metadata = '';
        $message = $this->__('Success');
        foreach ($results as $result) {
            if ($result->isSuccess()) {
                $isSuccess = true;
                break;
            }
            $metadata = $result->getMetadata();
            $message = $result->getMessage();
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
