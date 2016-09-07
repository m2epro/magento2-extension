<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Listing\Product\Action;

class Logger extends \Ess\M2ePro\Model\AbstractModel
{
    private $action    = \Ess\M2ePro\Model\Listing\Log::ACTION_UNKNOWN;

    private $actionId  = NULL;

    private $initiator = \Ess\M2ePro\Helper\Data::INITIATOR_UNKNOWN;

    /**
     * @var \Ess\M2ePro\Model\Amazon\Listing\Log
     */
    private $listingLog = NULL;

    private $status = \Ess\M2ePro\Helper\Data::STATUS_SUCCESS;

    protected $activeRecordFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory
    )
    {
        $this->activeRecordFactory = $activeRecordFactory;
        parent::__construct($helperFactory, $modelFactory);
    }

    //########################################

    /**
     * @param $value
     */
    public function setAction($value)
    {
        $this->action = (int)$value;
    }

    /**
     * @param $id
     */
    public function setActionId($id)
    {
        $this->actionId = (int)$id;
    }

    /**
     * @param $value
     */
    public function setInitiator($value)
    {
        $this->initiator = (int)$value;
    }

    //########################################

    /**
     * @return null|int
     */
    public function getActionId()
    {
        return $this->actionId;
    }

    // ---------------------------------------

    /**
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param int $status
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }

    //########################################

    /**
     * @param \Ess\M2ePro\Model\Listing\Product $listingProduct
     * @param string $message
     * @param int $type
     * @param int $priority
     */
    public function logListingProductMessage(\Ess\M2ePro\Model\Listing\Product $listingProduct,
                                             \Ess\M2ePro\Model\Connector\Connection\Response\Message $message,
                                             $priority = \Ess\M2ePro\Model\Log\AbstractModel::PRIORITY_MEDIUM)
    {
        $this->getListingLog()->addProductMessage($listingProduct->getListingId() ,
                                                  $listingProduct->getProductId() ,
                                                  $listingProduct->getId() ,
                                                  $this->initiator ,
                                                  $this->actionId ,
                                                  $this->action ,
                                                  $message->getText(),
                                                  $this->initLogType($message),
                                                  $priority);
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Amazon\Listing\Log
     */
    private function getListingLog()
    {
        if (is_null($this->listingLog)) {

            /** @var \Ess\M2ePro\Model\Amazon\Listing\Log $listingLog */
            $listingLog = $this->activeRecordFactory->getObject('Amazon\Listing\Log');

            $this->listingLog = $listingLog;
        }

        return $this->listingLog;
    }

    //########################################

    private function initLogType(\Ess\M2ePro\Model\Connector\Connection\Response\Message $message)
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
}