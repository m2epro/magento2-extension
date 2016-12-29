<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model;

class LockItem extends \Ess\M2ePro\Model\ActiveRecord\AbstractModel
{
    private $nick = 'undefined';
    private $maxInactiveTime = 1800; // 30 min

    //########################################

    public function _construct()
    {
        parent::_construct();
        $this->_init('Ess\M2ePro\Model\ResourceModel\LockItem');
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

    // ---------------------------------------

    public function setMaxInactiveTime($value)
    {
        $this->maxInactiveTime = (int)$value;
    }

    public function getMaxInactiveTime()
    {
        return $this->maxInactiveTime;
    }

    //########################################

    public function create($parentId = NULL)
    {
        $data = array(
            'nick' => $this->nick,
            'parent_id' => $parentId
        );

        $this->activeRecordFactory->getObject('LockItem')->setData($data)->save();

        return true;
    }

    public function remove()
    {
        /** @var $lockModel \Ess\M2ePro\Model\LockItem **/
        $lockModel = $this->activeRecordFactory->getObjectLoaded(
            'LockItem', $this->nick,'nick', false
        );

        if (is_null($lockModel)) {
            return false;
        }

        $childrenCollection = $this->activeRecordFactory->getObject('LockItem')->getCollection();
        $childrenCollection->addFieldToFilter('parent_id', $lockModel->getId());

        foreach ($childrenCollection->getItems() as $childLockModel) {
            /** @var $childLockModel \Ess\M2ePro\Model\LockItem **/
            $childLockModel = $this->activeRecordFactory
                                   ->getObject('LockItem')
                                   ->load($childLockModel->getId());
            $childLockModel->setNick($childLockModel->getData('nick'));
            $childLockModel->getId() && $childLockModel->remove();
        }

        $lockModel->delete();

        return true;
    }

    // ---------------------------------------

    /**
     * @return bool
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function isExist()
    {
        /** @var $lockModel \Ess\M2ePro\Model\LockItem **/
        $lockModel = $this->activeRecordFactory->getObjectLoaded(
            'LockItem', $this->nick, 'nick', false
        );

        if (is_null($lockModel)) {
            return false;
        }

        $currentTimestamp = $this->getHelper('Data')->getCurrentGmtDate(true);
        $updateTimestamp = strtotime($lockModel->getData('update_date'));

        if ($updateTimestamp < $currentTimestamp - $this->getMaxInactiveTime()) {
            $lockModel->delete();
            return false;
        }

        return true;
    }

    public function activate()
    {
        /** @var $lockModel \Ess\M2ePro\Model\LockItem **/
        $lockModel = $this->activeRecordFactory->getObjectLoaded(
            'LockItem', $this->nick, 'nick', false
        );

        if (is_null($lockModel)) {
            return false;
        }

        $parentId = $lockModel->getData('parent_id');

        if (!is_null($parentId)) {
            /** @var $parentLockModel \Ess\M2ePro\Model\LockItem **/
            $parentLockModel = $this->activeRecordFactory->getObjectLoaded('LockItem', $parentId, NULL, false);

            if (is_null($parentLockModel)) {
                $parentLockModel = $this->activeRecordFactory->getObject('LockItem');
            }

            $parentLockModel->setNick($parentLockModel->getData('nick'));
            $parentLockModel->getId() && $parentLockModel->activate();
        }

        $lockModel->setData('data',$lockModel->getData('data'))->save();

        return true;
    }

    //########################################

    public function getRealId()
    {
        /** @var $lockModel \Ess\M2ePro\Model\LockItem **/
        $lockModel = $this->activeRecordFactory->getObjectLoaded('LockItem', $this->nick,'nick', false);
        return !is_null($lockModel) ? $lockModel->getId() : NULL;
    }

    // ---------------------------------------

    public function addContentData($key, $value)
    {
        /** @var $lockModel \Ess\M2ePro\Model\LockItem **/
        $lockModel = $this->activeRecordFactory->getObjectLoaded(
            'LockItem', $this->nick, 'nick', false
        );

        if (is_null($lockModel)) {
            return false;
        }

        $data = $lockModel->getData('data');
        if (!empty($data)) {
            $data = json_decode($data, true);
        } else {
            $data = array();
        }

        $data[$key] = $value;

        $lockModel->setData('data', $this->getHelper('Data')->jsonEncode($data));
        $lockModel->save();

        return true;
    }

    public function setContentData(array $data)
    {
        /** @var $lockModel \Ess\M2ePro\Model\LockItem **/
        $lockModel = $this->activeRecordFactory->getObjectLoaded('LockItem', $this->nick, 'nick', false);

        if (is_null($lockModel)) {
            return false;
        }

        $lockModel->setData(
            'data', $this->getHelper('Data')->jsonEncode($data)
        )->save();

        return true;
    }

    // ---------------------------------------

    public function getContentData($key = NULL)
    {
        /** @var $lockModel \Ess\M2ePro\Model\LockItem **/
        $lockModel = $this->activeRecordFactory->getObjectLoaded('LockItem', $this->nick, 'nick', false);

        if (is_null($lockModel)) {
            return NULL;
        }

        if ($lockModel->getData('data') == '') {
            return NULL;
        }

        $data = json_decode($lockModel->getData('data'),true);

        if (is_null($key)) {
            return $data;
        }

        if (isset($data[$key])) {
            return $data[$key];
        }

        return NULL;
    }

    //########################################

    public function makeShutdownFunction()
    {
        if (!$this->isExist()) {
            return false;
        }

        register_shutdown_function(function() {
            /** @var LockItem $object */
            $object = $this->activeRecordFactory->getObject('LockItem');
            $object->setNick($this->nick);
            $object->remove();
        });

        return true;
    }

    //########################################
}