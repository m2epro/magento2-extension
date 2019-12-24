<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Listing\Product\Action;

/**
 * Class \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Logger
 */
class Logger extends \Ess\M2ePro\Model\AbstractModel
{
    protected $action = \Ess\M2ePro\Model\Listing\Log::ACTION_UNKNOWN;

    protected $actionId = null;
    protected $initiator = \Ess\M2ePro\Helper\Data::INITIATOR_UNKNOWN;

    protected $storeMode = false;
    protected $storedMessages = [];

    protected $status = \Ess\M2ePro\Helper\Data::STATUS_SUCCESS;

    /**
     * @var \Ess\M2ePro\Model\Listing\Log
     */
    private $listingLog = null;

    protected $activeRecordFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory
    ) {
        parent::__construct($helperFactory, $modelFactory);
        $this->activeRecordFactory = $activeRecordFactory;
    }

    //########################################

    /**
     * @param int $id
     */
    public function setActionId($id)
    {
        $this->actionId = (int)$id;
    }

    /**
     * @return null|int
     */
    public function getActionId()
    {
        return $this->actionId;
    }

    // ---------------------------------------

    /**
     * @param int $value
     */
    public function setAction($value)
    {
        $this->action = (int)$value;
    }

    /**
     * @param int $value
     */
    public function setInitiator($value)
    {
        $this->initiator = (int)$value;
    }

    //########################################

    public function setStoreMode($value)
    {
        $this->storeMode = (bool)$value;
    }

    public function getStoredMessages()
    {
        return $this->storedMessages;
    }

    // ---------------------------------------

    /**
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }

    public function setStatus($status)
    {
        if ($status == \Ess\M2ePro\Helper\Data::STATUS_ERROR) {
            $this->status = \Ess\M2ePro\Helper\Data::STATUS_ERROR;
            return;
        }

        if ($this->status == \Ess\M2ePro\Helper\Data::STATUS_ERROR) {
            return;
        }

        if ($status == \Ess\M2ePro\Helper\Data::STATUS_WARNING) {
            $this->status = \Ess\M2ePro\Helper\Data::STATUS_WARNING;
            return;
        }

        if ($this->status == \Ess\M2ePro\Helper\Data::STATUS_WARNING) {
            return;
        }

        $this->status = \Ess\M2ePro\Helper\Data::STATUS_SUCCESS;
    }

    //########################################

    /**
     * @param \Ess\M2ePro\Model\Listing\Product $listingProduct
     * @param \Ess\M2ePro\Model\Connector\Connection\Response\Message $message
     * @param int $priority
     */
    public function logListingProductMessage(
        \Ess\M2ePro\Model\Listing\Product $listingProduct,
        \Ess\M2ePro\Model\Connector\Connection\Response\Message $message,
        $priority = \Ess\M2ePro\Model\Log\AbstractModel::PRIORITY_MEDIUM
    ) {
        if ($this->storeMode) {
            $this->storedMessages[] = [
                'type' => $this->initLogType($message),
                'text' => $message->getText(),
            ];

            return;
        }

        $this->getListingLog()
            ->addProductMessage(
                $listingProduct->getListingId(),
                $listingProduct->getProductId(),
                $listingProduct->getId(),
                $this->initiator,
                $this->actionId,
                $this->action,
                $message->getText(),
                $this->initLogType($message),
                $priority
            );
    }

    //########################################

    protected function initLogType(\Ess\M2ePro\Model\Connector\Connection\Response\Message $message)
    {
        if ($message->isError()) {
            $this->setStatus(\Ess\M2ePro\Helper\Data::STATUS_ERROR);
            return \Ess\M2ePro\Model\Log\AbstractModel::TYPE_ERROR;
        }

        if ($message->isWarning()) {
            $this->setStatus(\Ess\M2ePro\Helper\Data::STATUS_WARNING);
            return \Ess\M2ePro\Model\Log\AbstractModel::TYPE_WARNING;
        }

        if ($message->isSuccess()) {
            $this->setStatus(\Ess\M2ePro\Helper\Data::STATUS_SUCCESS);
            return \Ess\M2ePro\Model\Log\AbstractModel::TYPE_SUCCESS;
        }

        if ($message->isNotice()) {
            $this->setStatus(\Ess\M2ePro\Helper\Data::STATUS_SUCCESS);
            return \Ess\M2ePro\Model\Log\AbstractModel::TYPE_NOTICE;
        }

        $this->setStatus(\Ess\M2ePro\Helper\Data::STATUS_ERROR);

        return \Ess\M2ePro\Model\Log\AbstractModel::TYPE_ERROR;
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Listing\Log
     */
    private function getListingLog()
    {
        if ($this->listingLog === null) {

            /** @var \Ess\M2ePro\Model\Listing\Log $listingLog */
            $listingLog = $this->activeRecordFactory->getObject('Listing\Log');
            $listingLog->setComponentMode(\Ess\M2ePro\Helper\Component\Ebay::NICK);

            $this->listingLog = $listingLog;
        }

        return $this->listingLog;
    }

    //########################################
}
