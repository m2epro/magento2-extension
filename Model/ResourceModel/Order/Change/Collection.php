<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel\Order\Change;

/**
 * Class \Ess\M2ePro\Model\ResourceModel\Order\Change\Collection
 */
class Collection extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Collection\AbstractModel
{
    //########################################

    public function _construct()
    {
        parent::_construct();
        $this->_init(
            'Ess\M2ePro\Model\Order\Change',
            'Ess\M2ePro\Model\ResourceModel\Order\Change'
        );
    }

    //########################################

    public function addAccountFilter($accountId)
    {
        $accountId = (int)$accountId;

        $mpTable = $this->activeRecordFactory->getObject('Order')->getResource()->getMainTable();

        $this->getSelect()->join(
            ['mo' => $mpTable],
            '(`mo`.`id` = `main_table`.`order_id` AND `mo`.`account_id` = '.$accountId.')',
            ['account_id', 'marketplace_id']
        );
    }

    //########################################

    public function addProcessingAttemptDateFilter($interval = 3600)
    {
        $interval = (int)$interval;

        if ($interval <= 0) {
            return;
        }

        $currentDate = new \DateTime('now', new \DateTimeZone('UTC'));
        $currentDate->modify("-{$interval} seconds");

        $this->getSelect()->where(
            'processing_attempt_date IS NULL OR processing_attempt_date <= ?',
            $currentDate->format('Y-m-d H:i:s')
        );
    }

    //########################################

    public function addProcessingLockFilter($tag)
    {
        $mysqlTag = $this->getConnection()->quote($tag);
        $this->getSelect()->joinLeft(
            ['lo' => $this->activeRecordFactory->getObject('Processing\Lock')->getResource()->getMainTable()],
            '(`lo`.`object_id` = `main_table`.`order_id` AND `lo`.`tag` = '.$mysqlTag.')',
            []
        );
        $this->getSelect()->where(
            '`lo`.`object_id` IS NULL'
        );
    }

    //########################################
}
