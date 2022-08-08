<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Motor;

class Group extends \Ess\M2ePro\Model\ActiveRecord\Component\AbstractModel
{
    const MODE_ITEM     = 1;
    const MODE_FILTER   = 2;

    /** @var \Ess\M2ePro\Helper\Component\Ebay\Motors */
    private $componentEbayMotors;

    public function __construct(
        \Ess\M2ePro\Helper\Component\Ebay\Motors $componentEbayMotors,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct(
            $modelFactory,
            $activeRecordFactory,
            $helperFactory,
            $context,
            $registry,
            $resource,
            $resourceCollection,
            $data
        );

        $this->componentEbayMotors = $componentEbayMotors;
    }

    public function _construct()
    {
        parent::_construct();
        $this->_init(\Ess\M2ePro\Model\ResourceModel\Ebay\Motor\Group::class);
    }

    public function delete()
    {
        /** @var \Ess\M2ePro\Helper\Component\Ebay\Motors $ebayMotorsHelper */
        $ebayMotorsHelper = $this->componentEbayMotors;

        $associatedProductsIds = $ebayMotorsHelper->getAssociatedProducts($this->getId(), 'GROUP');
        $ebayMotorsHelper->resetOnlinePartsData($associatedProductsIds);

        if (!parent::delete()) {
            return false;
        }

        $connection = $this->getResource()->getConnection();
        $filterGroupRelation = $this->getHelper('Module_Database_Structure')
            ->getTableNameWithPrefix('m2epro_ebay_motor_filter_to_group');
        $connection->delete($filterGroupRelation, ['group_id = ?' => $this->getId()]);

        return true;
    }

    /**
     * @return int
     */
    public function getTitle()
    {
        return (int)$this->getData('title');
    }

    /**
     * @return int
     */
    public function getMode()
    {
        return (int)$this->getData('mode');
    }

    /**
     * @return bool
     */
    public function isModeItem()
    {
        return $this->getMode() == self::MODE_ITEM;
    }

    /**
     * @return bool
     */
    public function isModeFilter()
    {
        return $this->getMode() == self::MODE_FILTER;
    }

    /**
     * @return int
     */
    public function getType()
    {
        return (int)$this->getData('type');
    }

    /**
     * @return bool
     */
    public function isTypeEpid()
    {
        return in_array($this->getType(), [
            \Ess\M2ePro\Helper\Component\Ebay\Motors::TYPE_EPID_MOTOR,
            \Ess\M2ePro\Helper\Component\Ebay\Motors::TYPE_EPID_UK,
            \Ess\M2ePro\Helper\Component\Ebay\Motors::TYPE_EPID_DE,
            \Ess\M2ePro\Helper\Component\Ebay\Motors::TYPE_EPID_AU,
            \Ess\M2ePro\Helper\Component\Ebay\Motors::TYPE_EPID_IT
        ]);
    }

    /**
     * @return bool
     */
    public function isTypeKtype()
    {
        return $this->getType() == \Ess\M2ePro\Helper\Component\Ebay\Motors::TYPE_KTYPE;
    }

    public function getItemsData()
    {
        return $this->getData('items_data');
    }

    public function getItems()
    {
        $data = $this->componentEbayMotors->parseAttributeValue($this->getItemsData());

        return $data['items'];
    }

    public function getFiltersIds()
    {
        $connection = $this->getResource()->getConnection();
        $table = $this->getHelper('Module_Database_Structure')
            ->getTableNameWithPrefix('m2epro_ebay_motor_filter_to_group');

        $select = $connection->select();
        $select->from(['emftg' => $table], ['filter_id'])
               ->where('group_id IN (?)', $this->getId());

        return $connection->fetchCol($select);
    }

    /**
     * @param array $itemsIds
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function removeItemsByIds($itemsIds)
    {
        if (!$this->getId()) {
            throw new \Ess\M2ePro\Model\Exception\Logic('Instance must be loaded first.');
        }

        if (!$this->isModeItem()) {
            throw new \Ess\M2ePro\Model\Exception\Logic(
                'Method should be used for item mode only instead of filter mode'
            );
        }

        $items = $this->getItems();

        foreach ($itemsIds as $itemId) {
            unset($items[$itemId]);
        }

        if (!empty($items)) {
            $this->setItemsData($this->componentEbayMotors->buildItemsAttributeValue($items));
            $this->save();
        } else {
            $this->delete();
        }

        /** @var \Ess\M2ePro\Helper\Component\Ebay\Motors $ebayMotorsHelper */
        $ebayMotorsHelper = $this->componentEbayMotors;
        $associatedProductsIds = $ebayMotorsHelper->getAssociatedProducts($this->getId(), 'GROUP');
        $ebayMotorsHelper->resetOnlinePartsData($associatedProductsIds);
    }

    /**
     * @param array $filtersIds
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function removeFiltersByIds($filtersIds)
    {
        if (!$this->getId()) {
            throw new \Ess\M2ePro\Model\Exception\Logic('Instance must be loaded first.');
        }
        $groupId = $this->getId();

        if (!$this->isModeFilter()) {
            throw new \Ess\M2ePro\Model\Exception\Logic(
                'Method should be used for filter mode only instead of item mode'
            );
        }

        $connWrite = $this->getResource()->getConnection('core/write');

        $filterGroupRelation = $this->getHelper('Module_Database_Structure')
            ->getTableNameWithPrefix('m2epro_ebay_motor_filter_to_group');

        $connWrite->delete(
            $filterGroupRelation,
            [
                'filter_id in (?)' => $filtersIds,
                'group_id = ?' => $groupId,
            ]
        );

        /** @var \Ess\M2ePro\Model\Ebay\Motor\Group $model */
        $model = $this->activeRecordFactory->getObject('Ebay_Motor_Group');
        $model->load($groupId);
        $ids = $model->getFiltersIds();

        if (empty($ids)) {
            $model->delete();
        }

        /** @var \Ess\M2ePro\Helper\Component\Ebay\Motors $ebayMotorsHelper */
        $ebayMotorsHelper = $this->componentEbayMotors;
        $associatedProductsIds = $ebayMotorsHelper->getAssociatedProducts($this->getId(), 'GROUP');
        $ebayMotorsHelper->resetOnlinePartsData($associatedProductsIds);
    }

    public function getNote()
    {
        return $this->getData('note');
    }
}
