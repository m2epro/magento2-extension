<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Lock\Item;

/**
 * Class \Ess\M2ePro\Model\Lock\Item\Manager
 */
class Manager extends \Ess\M2ePro\Model\AbstractModel
{
    const DEFAULT_MAX_INACTIVE_TIME = 900;

    /** @var \Ess\M2ePro\Model\ActiveRecord\Factory */
    private $activeRecordFactory;

    /** @var string */
    private $nick;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        $nick,
        array $data = []
    ) {
        $this->activeRecordFactory = $activeRecordFactory;
        $this->nick                = $nick;
        parent::__construct($helperFactory, $modelFactory, $data);
    }

    //########################################

    public function getNick()
    {
        return $this->nick;
    }

    //########################################

    public function create($parentNick = null)
    {
        /** @var $parentLockItem \Ess\M2ePro\Model\Lock\Item */
        $parentLockItem = $this->activeRecordFactory->getObject('Lock\Item');
        if ($parentNick !== null) {
            $parentLockItem->load($parentNick, 'nick');
        }

        $data = [
            'nick'      => $this->nick,
            'parent_id' => $parentLockItem->getId(),
        ];

        /** @var $lockModel \Ess\M2ePro\Model\Lock\Item */
        $lockModel = $this->activeRecordFactory->getObject('Lock\Item')->setData($data);
        $lockModel->save();

        return $this;
    }

    public function remove()
    {
        $lockItem = $this->getLockItemObject();
        if ($lockItem === null) {
            return false;
        }

        /** @var \Ess\M2ePro\Model\ResourceModel\Lock\Item\Collection $childLockItemCollection */
        $childLockItemCollection = $this->activeRecordFactory->getObject('Lock\Item')->getCollection();
        $childLockItemCollection->addFieldToFilter('parent_id', $lockItem->getId());

        /** @var \Ess\M2ePro\Model\Lock\Item[] $childLockItems */
        $childLockItems = $childLockItemCollection->getItems();

        foreach ($childLockItems as $childLockItem) {
            $childManager = $this->modelFactory->getObject(
                'Lock_Item_Manager',
                ['nick' => $childLockItem->getNick()]
            );
            $childManager->remove();
        }

        $lockItem->delete();

        return true;
    }

    // ---------------------------------------

    public function isExist()
    {
        return $this->getLockItemObject() !== null;
    }

    public function isInactiveMoreThanSeconds($maxInactiveInterval)
    {
        $lockItem = $this->getLockItemObject();
        if ($lockItem === null) {
            return true;
        }

        $currentTimestamp = $this->getHelper('Data')->getCurrentGmtDate(true);
        $updateTimestamp = strtotime($lockItem->getUpdateDate());

        if ($updateTimestamp < $currentTimestamp - $maxInactiveInterval) {
            return true;
        }

        return false;
    }

    public function activate()
    {
        $lockItem = $this->getLockItemObject();
        if ($lockItem === null) {
            throw new \Ess\M2ePro\Model\Exception(
                sprintf('Lock Item with nick "%s" does not exist.', $this->nick)
            );
        }

        if ($lockItem->getParentId() !== null) {

            /** @var $parentLockItem \Ess\M2ePro\Model\Lock\Item */
            $parentLockItem = $this->activeRecordFactory->getObject('Lock\Item')->load($lockItem->getParentId());

            if ($parentLockItem->getId()) {
                /** @var $parentManager \Ess\M2ePro\Model\Lock\Item\Manager */
                $parentManager = $this->modelFactory->getObject(
                    'Lock_Item_Manager',
                    ['nick' => $parentLockItem->getNick()]
                );
                $parentManager->activate();
            }
        }

        $lockItem->setDataChanges(true);
        $lockItem->save();

        return true;
    }

    //########################################

    public function addContentData($key, $value)
    {
        $lockItem = $this->getLockItemObject();
        if ($lockItem === null) {
            throw new \Ess\M2ePro\Model\Exception(
                sprintf('Lock Item with nick "%s" does not exist.', $this->nick)
            );
        }

        $data = $lockItem->getContentData();
        if (!empty($data)) {
            $data = $this->getHelper('Data')->jsonDecode($data);
        } else {
            $data = [];
        }

        $data[$key] = $value;

        $lockItem->setData('data', $this->getHelper('Data')->jsonEncode($data));
        $lockItem->save();

        return true;
    }

    public function setContentData(array $data)
    {
        $lockItem = $this->getLockItemObject();
        if ($lockItem === null) {
            throw new \Ess\M2ePro\Model\Exception(
                sprintf('Lock Item with nick "%s" does not exist.', $this->nick)
            );
        }

        $lockItem->setData('data', $this->getHelper('Data')->jsonEncode($data));
        $lockItem->save();

        return true;
    }

    // ---------------------------------------

    public function getContentData($key = null)
    {
        $lockItem = $this->getLockItemObject();
        if ($lockItem === null) {
            throw new \Ess\M2ePro\Model\Exception(
                sprintf('Lock Item with nick "%s" does not exist.', $this->nick)
            );
        }

        if ($lockItem->getData('data') == '') {
            return null;
        }

        $data = $this->getHelper('Data')->jsonDecode($lockItem->getContentData());
        if ($key === null) {
            return $data;
        }

        if (isset($data[$key])) {
            return $data[$key];
        }

        return null;
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Lock\Item
     */
    protected function getLockItemObject()
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Lock\Item\Collection $lockItemCollection */
        $lockItemCollection = $this->activeRecordFactory->getObject('Lock\Item')->getCollection();
        $lockItemCollection->addFieldToFilter('nick', $this->nick);

        /** @var \Ess\M2ePro\Model\Lock\Item $lockItem */
        $lockItem = $lockItemCollection->getFirstItem();

        return $lockItem->getId() ? $lockItem : null;
    }

    //########################################
}
