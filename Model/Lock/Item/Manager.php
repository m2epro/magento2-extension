<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Lock\Item;

class Manager extends \Ess\M2ePro\Model\AbstractModel
{
    private $nick = 'undefined';
    private $maxInactiveTime = 1800; // 30 min

    private $activeRecordFactory = null;

    /** @var \Ess\M2ePro\Model\Lock\Item */
    private $lockModel;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        array $data = []
    )
    {
        $this->activeRecordFactory = $activeRecordFactory;
        parent::__construct($helperFactory, $modelFactory, $data);
    }

    //########################################

    public function setLockModel(\Ess\M2ePro\Model\Lock\Item $lock)
    {
        $this->lockModel = $lock;
        $this->nick = $lock->getNick();

        return $this;
    }

    public function getLockModel($reload = true)
    {
        if (is_null($this->lockModel) || $reload) {
            $this->lockModel = $this->activeRecordFactory->getObjectLoaded(
                'Lock\Item', $this->nick, 'nick', false
            );
        }

        return $this->lockModel;
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
            'nick'      => $this->nick,
            'parent_id' => $parentId,
        );

        /** @var $lockModel \Ess\M2ePro\Model\Lock\Item **/
        $lockModel = $this->activeRecordFactory->getObject('Lock\Item')->setData($data);
        $lockModel->save();

        $this->setLockModel($lockModel);
        return true;
    }

    public function remove($reload = true)
    {
        /** @var $lockModel \Ess\M2ePro\Model\Lock\Item **/
        $lockModel = $this->getLockModel($reload);
        if (is_null($lockModel) || !$lockModel->getId()) {
            return false;
        }

        $childrenCollection = $this->activeRecordFactory->getObject('Lock\Item')->getCollection();
        $childrenCollection->addFieldToFilter('parent_id', $lockModel->getId());

        foreach ($childrenCollection->getItems() as $childLockModel) {

            /** @var $childManager \Ess\M2ePro\Model\Lock\Item\Manager **/
            $childManager = $this->modelFactory->getObject('Lock\Item\Manager');
            $childManager->setLockModel($childLockModel);
            $childManager->remove(false);
        }

        $lockModel->delete();
        return true;
    }

    // ---------------------------------------

    public function isExist($reload = true)
    {
        /** @var $lockModel \Ess\M2ePro\Model\Lock\Item **/
        $lockModel = $this->getLockModel($reload);
        if (is_null($lockModel) || !$lockModel->getId()) {
            return false;
        }

        $currentTimestamp = $this->getHelper('Data')->getCurrentGmtDate(true);
        $updateTimestamp  = strtotime($lockModel->getUpdateDate());

        if ($updateTimestamp < $currentTimestamp - $this->getMaxInactiveTime()) {

            $this->helperFactory->getObject('Module\Logger')->process(
                $lockModel->getData(), 'Lock Item was removed by lifetime', false
            );
            $this->remove($reload);
            return false;
        }

        return true;
    }

    public function activate($reload = true)
    {
        /** @var $lockModel \Ess\M2ePro\Model\Lock\Item **/
        $lockModel = $this->getLockModel($reload);
        if (is_null($lockModel) || !$lockModel->getId()) {
            return false;
        }

        if (!is_null($lockModel->getParentId())) {

            /** @var \Ess\M2ePro\Model\Lock\Item $parentLockItem */
            $parentLockItem = $this->activeRecordFactory->getObjectLoaded(
                'Lock\Item', $lockModel->getParentId(), NULL, false
            );

            /** @var $parentManager \Ess\M2ePro\Model\Lock\Item\Manager **/
            $parentManager = $this->modelFactory->getObject('Lock\Item\Manager');
            $parentManager->setLockModel($parentLockItem);
            $parentManager->activate(false);
        }

        $lockModel->setData('data', $lockModel->getContentData());
        $lockModel->setDataChanges(true);
        $lockModel->save();

        return true;
    }

    //########################################

    public function getRealId($reload = true)
    {
        return $this->getLockModel($reload)->getId();
    }

    // ---------------------------------------

    public function addContentData($key, $value, $reload = true)
    {
        /** @var $lockModel \Ess\M2ePro\Model\Lock\Item **/
        $lockModel = $this->getLockModel($reload);
        if (is_null($lockModel) || !$lockModel->getId()) {
            return false;
        }

        $data = $lockModel->getContentData();
        if (!empty($data)) {
            $data = $this->getHelper('Data')->jsonDecode($data);
        } else {
            $data = array();
        }

        $data[$key] = $value;

        $lockModel->setData('data', $this->getHelper('Data')->jsonEncode($data));
        $lockModel->save();

        return true;
    }

    public function setContentData(array $data, $reload = true)
    {
        /** @var $lockModel \Ess\M2ePro\Model\Lock\Item **/
        $lockModel = $this->getLockModel($reload);
        if (is_null($lockModel) || !$lockModel->getId()) {
            return false;
        }

        $lockModel->setData('data', $this->getHelper('Data')->jsonEncode($data));
        $lockModel->save();

        return true;
    }

    // ---------------------------------------

    public function getContentData($key = NULL, $reload = true)
    {
        /** @var $lockModel \Ess\M2ePro\Model\Lock\Item **/
        $lockModel = $this->getLockModel($reload);
        if (is_null($lockModel) || !$lockModel->getId()) {
            return NULL;
        }

        if ($lockModel->getData('data') == '') {
            return NULL;
        }

        $data = $this->getHelper('Data')->jsonDecode($lockModel->getContentData());
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

        register_shutdown_function(function()
        {
            $error = error_get_last();
            if (is_null($error) || !in_array((int)$error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR])) {
                return;
            }

            /** @var Manager $object */
            $object = $this->modelFactory->getObject('Lock\Item\Manager');
            $object->setNick($this->nick);
            $object->remove();
        });

        return true;
    }

    //########################################
}