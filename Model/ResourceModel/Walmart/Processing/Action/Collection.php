<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel\Walmart\Processing\Action;

/**
 * Class \Ess\M2ePro\Model\ResourceModel\Walmart\Processing\Action\Collection
 */
class Collection extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Collection\AbstractModel
{
    // ########################################

    public function _construct()
    {
        parent::_construct();
        $this->_init(
            'Ess\M2ePro\Model\Walmart\Processing\Action',
            'Ess\M2ePro\Model\ResourceModel\Walmart\Processing\Action'
        );
    }

    // ########################################

    /**
     * @param \Ess\M2ePro\Model\Account[] $accounts
     * @return $this
     */
    public function setAccountsFilter(array $accounts)
    {
        $accountIds = [];
        foreach ($accounts as $account) {
            $accountIds[] = $account->getId();
        }

        $this->addFieldToFilter('account_id', ['in' => $accountIds]);

        return $this;
    }

    /**
     * @param string $actionType
     * @return $this
     */
    public function setActionTypeFilter($actionType)
    {
        $this->addFieldToFilter('type', $actionType);
        return $this;
    }

    public function setRequestPendingSingleIdFilter($requestPendingSingleIds)
    {
        if (!is_array($requestPendingSingleIds)) {
            $requestPendingSingleIds = [$requestPendingSingleIds];
        }

        $this->addFieldToFilter('request_pending_single_id', ['in' => $requestPendingSingleIds]);
        return $this;
    }

    public function setNotProcessedFilter()
    {
        $this->addFieldToFilter('request_pending_single_id', ['null' => true]);
        return $this;
    }

    public function setInProgressFilter()
    {
        $this->addFieldToFilter('request_pending_single_id', ['notnull' => true]);
        return $this;
    }

    public function setStartedBeforeFilter($minutes)
    {
        $dateTime = new \DateTime('now', new \DateTimeZone('UTC'));
        $dateTime->modify('- '.(int)$minutes.' minutes');

        $this->addFieldToFilter('start_date', ['lt' => $dateTime->format('Y-m-d H:i:s')]);

        return $this;
    }

    // ########################################
}
