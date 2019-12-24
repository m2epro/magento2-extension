<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\General;

use Ess\M2ePro\Controller\Adminhtml\General;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\General\SynchGetLastResult
 */
class SynchGetLastResult extends General
{
    //########################################

    public function execute()
    {
        $operationHistoryCollection = $this->activeRecordFactory->getObject('Synchronization\OperationHistory')
            ->getCollection();
        $operationHistoryCollection->addFieldToFilter('nick', 'synchronization');
        $operationHistoryCollection->setOrder('id', 'DESC');
        $operationHistoryCollection->getSelect()->limit(1);

        $operationHistory = $operationHistoryCollection->getFirstItem();

        $logCollection = $this->activeRecordFactory->getObject('Synchronization\Log')->getCollection();
        $logCollection->addFieldToFilter('operation_history_id', (int)$operationHistory->getId());
        $logCollection->addFieldToFilter('type', ['in' => [\Ess\M2ePro\Model\Log\AbstractModel::TYPE_ERROR]]);

        if ($logCollection->getSize() > 0) {
            $this->setAjaxContent('error', false);
            return $this->getResult();
        }

        $logCollection = $this->activeRecordFactory->getObject('Synchronization\Log')->getCollection();
        $logCollection->addFieldToFilter('operation_history_id', (int)$operationHistory->getId());
        $logCollection->addFieldToFilter(
            'type',
            ['in' => [\Ess\M2ePro\Model\Log\AbstractModel::TYPE_WARNING]]
        );

        if ($logCollection->getSize() > 0) {
            $this->setAjaxContent('warning', false);
        } else {
            $this->setAjaxContent('success', false);
        }

        return $this->getResult();
    }

    //########################################
}
