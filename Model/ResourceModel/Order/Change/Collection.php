<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel\Order\Change;

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
            array('mo' => $mpTable),
            '(`mo`.`id` = `main_table`.`order_id` AND `mo`.`account_id` = '.$accountId.')',
            array('account_id', 'marketplace_id')
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
            'processing_attempt_date IS NULL OR processing_attempt_date <= ?', $currentDate->format('Y-m-d H:i:s')
        );
    }

    //########################################

    public function addProcessingLockFilter($tag)
    {
        $mysqlTag = $this->getConnection()->quote($tag);
        $this->getSelect()->joinLeft(
            array('lo' => $this->activeRecordFactory->getObject('Processing\Lock')->getResource()->getMainTable()),
            '(`lo`.`object_id` = `main_table`.`order_id` AND `lo`.`tag` = '.$mysqlTag.')',
            array()
        );
        $this->getSelect()->where(
            '`lo`.`object_id` IS NULL'
        );
    }

    //########################################
}