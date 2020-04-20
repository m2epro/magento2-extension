<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\ControlPanel\Database;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\ControlPanel\Database\ShowOperationHistoryExecutionTreeDown
 */
class ShowOperationHistoryExecutionTreeDown extends Table
{
    public function execute()
    {
        $operationHistoryId = $this->getRequest()->getParam('operation_history_id');
        if (empty($operationHistoryId)) {
            $this->getMessageManager()->addErrorMessage("Operation history ID is not presented.");
            return $this->redirectToTablePage('m2epro_operation_history');
        }

        $operationHistory = $this->activeRecordFactory->getObject('OperationHistory');
        $operationHistory->setObject($operationHistoryId);

        while ($parentId = $operationHistory->getObject()->getData('parent_id')) {
            $object = $operationHistory->load($parentId);
            $operationHistory->setObject($object);
        }

        $this->getResponse()->setBody(
            '<pre>'.$operationHistory->getExecutionTreeDownInfo().'</pre>'
        );
    }
}
