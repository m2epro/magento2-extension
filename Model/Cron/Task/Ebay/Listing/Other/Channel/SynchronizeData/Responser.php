<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Cron\Task\Ebay\Listing\Other\Channel\SynchronizeData;

/**
 * Class \Ess\M2ePro\Model\Cron\Task\Ebay\Listing\Other\Channel\SynchronizeData\Responser
 */
class Responser extends \Ess\M2ePro\Model\Ebay\Connector\Inventory\Get\ItemsResponser
{
    /** @var \Ess\M2ePro\Model\Synchronization\Log */
    protected $synchronizationLog;

    //########################################

    /**
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function processResponseMessages()
    {
        parent::processResponseMessages();

        foreach ($this->getResponse()->getMessages()->getEntities() as $message) {
            if (!$message->isError() && !$message->isWarning()) {
                continue;
            }

            $logType = $message->isError() ? \Ess\M2ePro\Model\Log\AbstractModel::TYPE_ERROR
                : \Ess\M2ePro\Model\Log\AbstractModel::TYPE_WARNING;

            $this->getSynchronizationLog()->addMessage(
                $this->getHelper('Module\Translation')->__($message->getText()),
                $logType
            );
        }
    }

    /**
     * @return bool
     */
    protected function isNeedProcessResponse()
    {
        if (!parent::isNeedProcessResponse()) {
            return false;
        }

        if ($this->getResponse()->getMessages()->hasErrorEntities()) {
            return false;
        }

        return true;
    }

    //########################################

    /**
     * @param $messageText
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function failDetected($messageText)
    {
        parent::failDetected($messageText);

        $this->getSynchronizationLog()->addMessage(
            $this->getHelper('Module\Translation')->__($messageText),
            \Ess\M2ePro\Model\Log\AbstractModel::TYPE_ERROR
        );
    }

    //########################################

    /**
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function processResponseData()
    {
        try {

            /** @var $updatingModel \Ess\M2ePro\Model\Ebay\Listing\Other\Updating */
            $updatingModel = $this->modelFactory->getObject('Ebay_Listing_Other_Updating');
            $updatingModel->initialize(
                $this->ebayFactory->getObjectLoaded('Account', $this->params['account_id'])
            );
            $updatingModel->processResponseData($this->getPreparedResponseData());
        } catch (\Exception $e) {
            $this->getHelper('Module\Exception')->process($e);
            $this->getSynchronizationLog()->addMessageFromException($e);
        }
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Synchronization\Log
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function getSynchronizationLog()
    {
        if ($this->synchronizationLog !== null) {
            return $this->synchronizationLog;
        }

        $this->synchronizationLog = $this->activeRecordFactory->getObject('Synchronization\Log');
        $this->synchronizationLog->setComponentMode(\Ess\M2ePro\Helper\Component\Ebay::NICK);
        $this->synchronizationLog->setSynchronizationTask(\Ess\M2ePro\Model\Synchronization\Log::TASK_OTHER_LISTINGS);

        return $this->synchronizationLog;
    }

    //########################################
}
