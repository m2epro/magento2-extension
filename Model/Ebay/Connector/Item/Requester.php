<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Connector\Item;

abstract class Requester extends \Ess\M2ePro\Model\Ebay\Connector\Command\Pending\Requester
{
    const TIMEOUT_INCREMENT_FOR_ONE_IMAGE = 30;

    protected $isRealTime = false;

    /** @var \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Logger $logger */
    protected $logger = NULL;

    //########################################

    public function setIsRealTime($isRealTime = true)
    {
        $this->isRealTime = $isRealTime;
        return $this;
    }

    public function isRealTime()
    {
        return $this->isRealTime;
    }

    //########################################

    protected function buildConnectionInstance()
    {
        $connection = parent::buildConnectionInstance();
        $connection->setTimeout($this->getRequestTimeOut());

        return $connection;
    }

    protected function getRequestTimeOut()
    {
        return 300;
    }

    //########################################

    public function process()
    {
        if ($this->isRealTime()) {
            try {
                parent::process();
            } catch (\Ess\M2ePro\Model\Exception\Connection $exception) {
                if ($this->account->getChildObject()->isModeSandbox()) {
                    throw $exception;
                }

                $this->processResponser();

            } catch (\Exception $exception) {
                if (strpos($exception->getMessage(), 'code:34') === false ||
                    $this->account->getChildObject()->isModeSandbox()
                ) {
                    throw $exception;
                }

                $this->processResponser();
            }

            if ($this->getResponser()->getStatus() != \Ess\M2ePro\Helper\Data::STATUS_SUCCESS) {
                $this->getLogger()->setStatus($this->getResponser()->getStatus());
            }
            $this->params['logs_action_id'] = $this->getResponser()->getLogsActionId();

            return;
        }

        $this->eventBeforeExecuting();
        $this->getProcessingRunner()->start();
    }

    //########################################

    public function getStatus()
    {
        return $this->getLogger()->getStatus();
    }

    /**
     * @return array|integer
     */
    public function getLogsActionId()
    {
        return $this->params['logs_action_id'];
    }

    //########################################

    abstract protected function getActionType();

    abstract protected function getLogsAction();

    protected function getOrmActionType()
    {
        switch ($this->getActionType()) {
            case \Ess\M2ePro\Model\Listing\Product::ACTION_LIST:
                return 'ListAction';
            case \Ess\M2ePro\Model\Listing\Product::ACTION_RELIST:
                return 'Relist';
            case \Ess\M2ePro\Model\Listing\Product::ACTION_REVISE:
                return 'Revise';
            case \Ess\M2ePro\Model\Listing\Product::ACTION_STOP:
                return 'Stop';
        }

        throw new \Ess\M2ePro\Model\Exception('Wrong Action type');
    }

    protected function getLockIdentifier()
    {
        if ($this->getActionType() == \Ess\M2ePro\Model\Listing\Product::ACTION_LIST) {
            return 'list';
        }

        return strtolower($this->getOrmActionType());
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Logger
     * @throws \Ess\M2ePro\Model\Exception
     */
    public function getLogger()
    {
        if (is_null($this->logger)) {

            /** @var \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Logger $logger */

            $logger = $this->modelFactory->getObject('Ebay\Listing\Product\Action\Logger');

            if (!isset($this->params['logs_action_id']) || !isset($this->params['status_changer'])) {
                throw new \Ess\M2ePro\Model\Exception('Product Connector has not received some params');
            }

            $logger->setActionId((int)$this->params['logs_action_id']);
            $logger->setAction($this->getLogsAction());

            switch ($this->params['status_changer']) {
                case \Ess\M2ePro\Model\Listing\Product::STATUS_CHANGER_UNKNOWN:
                    $initiator = \Ess\M2ePro\Helper\Data::INITIATOR_UNKNOWN;
                    break;
                case \Ess\M2ePro\Model\Listing\Product::STATUS_CHANGER_USER:
                    $initiator = \Ess\M2ePro\Helper\Data::INITIATOR_USER;
                    break;
                default:
                    $initiator = \Ess\M2ePro\Helper\Data::INITIATOR_EXTENSION;
                    break;
            }

            $logger->setInitiator($initiator);

            $this->logger = $logger;
        }

        return $this->logger;
    }

    //########################################
}