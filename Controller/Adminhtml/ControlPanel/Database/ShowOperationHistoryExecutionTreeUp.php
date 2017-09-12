<?php

namespace Ess\M2ePro\Controller\Adminhtml\ControlPanel\Database;

class ShowOperationHistoryExecutionTreeUp extends Table
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

        $this->getResponse()->setBody(
            '<pre>'.$operationHistory->getExecutionTreeUpInfo().'</pre>'
        );
    }
}