<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Listing\SynchronizeInventory;

use Ess\M2ePro\Model\ActiveRecord\Component\Parent\Factory;

/**
 * Class \Ess\M2ePro\Model\Listing\SynchronizeInventory\AbstractHandler
 */
abstract class AbstractHandler
{
    /** @var array */
    protected $responserParams;

    /** @var \Ess\M2ePro\Model\Account */
    protected $account;

    /** @var int */
    protected $logsActionId;

    /** @var \Ess\M2ePro\Model\ActiveRecord\Factory */
    protected $activeRecordFactory;

    /** @var \Ess\M2ePro\Helper\Factory */
    protected $helperFactory;

    /** @var \Ess\M2ePro\Model\Factory */
    protected $modelFactory;

    /** @var Factory */
    protected $parentFactory;

    /** @var \Magento\Framework\App\ResourceConnection */
    protected $resourceConnection;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Factory $parentFactory,
        \Magento\Framework\App\ResourceConnection $resourceConnection
    )
    {
        $this->activeRecordFactory = $activeRecordFactory;
        $this->helperFactory       = $helperFactory;
        $this->modelFactory        = $modelFactory;
        $this->parentFactory       = $parentFactory;
        $this->resourceConnection  = $resourceConnection;
    }

    //########################################

    /**
     * @param array $responseData
     * @return array|void
     */
    abstract public function handle(array $responseData);

    /**
     * @return string
     */
    abstract protected function getComponentMode();

    /**
     * @return string
     */
    abstract protected function getInventoryIdentifier();

    /**
     * @param array $responserParams
     * @return $this
     */
    public function setResponserParams(array $responserParams)
    {
        $this->responserParams = $responserParams;
        return $this;
    }

    /**
     * @return \Ess\M2ePro\Model\Account
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function getAccount()
    {
        if ($this->account !== null) {
            return $this->account;
        }

        return $this->account = $this->parentFactory->getObjectLoaded(
            $this->getComponentMode(),
            'Account',
            $this->responserParams['account_id']
        );
    }

    /**
     * @return int
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function getLogsActionId()
    {
        if ($this->logsActionId !== null) {
            return $this->logsActionId;
        }

        return $this->logsActionId = (int)$this->activeRecordFactory
            ->getObject('Listing\Log')
            ->getResource()
            ->getNextActionId();
    }

    //########################################
}
