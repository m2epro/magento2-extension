<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Lock\Transactional;

class Manager extends \Ess\M2ePro\Model\AbstractModel
{
    private $nick = 'undefined';

    private $isTableLocked = false;
    private $isTransactionStarted = false;

    private $resourceConnection = null;
    private $activeRecordFactory = null;

    //########################################

    public function __construct(
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        array $data = []
    )
    {
        $this->resourceConnection = $resourceConnection;
        $this->activeRecordFactory = $activeRecordFactory;
        parent::__construct($helperFactory, $modelFactory, $data);
    }

    public function __destruct()
    {
        $this->unlock();
    }

    //########################################

    public function lock()
    {
        if ($this->getExclusiveLock()) {
            return;
        }

        $this->createExclusiveLock();
        $this->getExclusiveLock();
    }

    public function unlock()
    {
        $this->isTableLocked        && $this->unlockTable();
        $this->isTransactionStarted && $this->commitTransaction();
    }

    //########################################

    private function getExclusiveLock()
    {
        $this->startTransaction();

        $connection = $this->resourceConnection->getConnection();
        $lockId = (int)$connection->select()
                                  ->from($this->getTableName(), array('id'))
                                  ->where('nick = ?', $this->nick)
                                  ->forUpdate()
                                  ->query()->fetchColumn();

        if ($lockId) {
            return true;
        }

        $this->commitTransaction();
        return false;
    }

    private function createExclusiveLock()
    {
        $this->lockTable();

        $lock = $this->activeRecordFactory->getObjectLoaded(
            'Lock\Transactional', $this->nick, 'nick', false
        );

        if (is_null($lock)) {

            $lock = $this->activeRecordFactory->getObject('Lock\Transactional');
            $lock->setData(array(
                'nick' => $this->nick,
            ));
            $lock->save();
        }

        $this->unlockTable();
    }

    // ########################################

    private function startTransaction()
    {
        $connection = $this->resourceConnection->getConnection();
        $connection->beginTransaction();

        $this->isTransactionStarted = true;
    }

    private function commitTransaction()
    {
        $connection = $this->resourceConnection->getConnection();
        $connection->commit();

        $this->isTransactionStarted = false;
    }

    // ----------------------------------------

    private function lockTable()
    {
        $connection = $this->resourceConnection->getConnection();
        $connection->query("LOCK TABLES `{$this->getTableName()}` WRITE");

        $this->isTableLocked = true;
    }

    private function unlockTable()
    {
        $connection = $this->resourceConnection->getConnection();
        $connection->query('UNLOCK TABLES');

        $this->isTableLocked = false;
    }

    private function getTableName()
    {
        return $this->activeRecordFactory->getObject('Lock\Transactional')->getResource()->getMainTable();
    }

    //########################################

    public function setNick($value)
    {
        $this->nick = $value;
    }

    public function getNick()
    {
        return $this->nick;
    }

    //########################################
}