<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Lock\Item;

class Manager
{
    public const DEFAULT_MAX_INACTIVE_TIME = 900;

    /** @var string */
    private $nick;
    /** @var \Ess\M2ePro\Model\ResourceModel\Lock\Item\CollectionFactory */
    private $lockItemCollectionFactory;
    /** @var \Ess\M2ePro\Model\Lock\ItemFactory */
    private $lockItemFactory;
    /** @var \Ess\M2ePro\Model\Lock\Item\ManagerFactory */
    private $lockItemManagerFactory;

    public function __construct(
        \Ess\M2ePro\Model\Lock\Item\ManagerFactory $lockItemManagerFactory,
        \Ess\M2ePro\Model\Lock\ItemFactory $lockItemFactory,
        \Ess\M2ePro\Model\ResourceModel\Lock\Item\CollectionFactory $lockItemCollectionFactory,
        string $nick
    ) {
        $this->lockItemCollectionFactory = $lockItemCollectionFactory;
        $this->nick = $nick;
        $this->lockItemFactory = $lockItemFactory;
        $this->lockItemManagerFactory = $lockItemManagerFactory;
    }

    // ----------------------------------------

    public function getNick(): string
    {
        return $this->nick;
    }

    // ----------------------------------------

    public function create($parentNick = null): self
    {
        /** @var \Ess\M2ePro\Model\Lock\Item $parentLockItem */
        $parentLockItem = $this->lockItemFactory->create();
        if ($parentNick !== null) {
            $parentLockItem->load($parentNick, 'nick');
        }

        /** @var \Ess\M2ePro\Model\Lock\Item $lockModel */
        $lockModel = $this->lockItemFactory->create();
        $lockModel->setNick($this->nick)
                  ->setParentId($parentLockItem->getId());

        $lockModel->save();

        return $this;
    }

    public function remove(): bool
    {
        $lockItem = $this->getLockItemObject();
        if ($lockItem === null) {
            return false;
        }

        /** @var \Ess\M2ePro\Model\ResourceModel\Lock\Item\Collection $childLockItemCollection */
        $childLockItemCollection = $this->lockItemCollectionFactory->create();
        $childLockItemCollection->addFieldToFilter('parent_id', $lockItem->getId());

        /** @var \Ess\M2ePro\Model\Lock\Item[] $childLockItems */
        $childLockItems = $childLockItemCollection->getItems();

        foreach ($childLockItems as $childLockItem) {
            $childManager = $this->lockItemManagerFactory->create(
                $childLockItem->getNick()
            );
            $childManager->remove();
        }

        $lockItem->delete();

        return true;
    }

    // ---------------------------------------

    public function isExist(): bool
    {
        return $this->getLockItemObject() !== null;
    }

    public function isInactiveMoreThanSeconds($maxInactiveInterval): bool
    {
        $lockItem = $this->getLockItemObject();
        if ($lockItem === null) {
            return true;
        }

        $currentDate = \Ess\M2ePro\Helper\Date::createCurrentGmt();
        $updateDate = \Ess\M2ePro\Helper\Date::createDateGmt($lockItem->getUpdateDate());

        return $updateDate->getTimestamp() < ($currentDate->getTimestamp() - $maxInactiveInterval);
    }

    public function activate(): void
    {
        $lockItem = $this->getLockItemObject();
        if ($lockItem === null) {
            throw new \Ess\M2ePro\Model\Exception(
                sprintf('Lock Item with nick "%s" does not exist.', $this->nick)
            );
        }

        if ($lockItem->getParentId() !== null) {
            /** @var \Ess\M2ePro\Model\Lock\Item $parentLockItem */
            $parentLockItem = $this->lockItemFactory->create()->load($lockItem->getParentId());

            if ($parentLockItem->getId()) {
                $parentManager = $this->lockItemManagerFactory->create(
                    $parentLockItem->getNick()
                );
                $parentManager->activate();
            }
        }

        $lockItem->setDataChanges(true);
        $lockItem->save();
    }

    // ----------------------------------------

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
            $data = \Ess\M2ePro\Helper\Json::decode($data);
        } else {
            $data = [];
        }

        $data[$key] = $value;

        $lockItem->setData('data', \Ess\M2ePro\Helper\Json::encode($data));
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

        $lockItem->setData('data', \Ess\M2ePro\Helper\Json::encode($data));
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

        $data = \Ess\M2ePro\Helper\Json::decode($lockItem->getContentData());
        if ($key === null) {
            return $data;
        }

        return $data[$key] ?? null;
    }

    // ----------------------------------------

    private function getLockItemObject(): ?\Ess\M2ePro\Model\Lock\Item
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Lock\Item\Collection $lockItemCollection */
        $lockItemCollection = $this->lockItemCollectionFactory->create();
        $lockItemCollection->addFieldToFilter('nick', $this->nick);

        /** @var \Ess\M2ePro\Model\Lock\Item $lockItem */
        $lockItem = $lockItemCollection->getFirstItem();

        return $lockItem->getId() ? $lockItem : null;
    }
}
