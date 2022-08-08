<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Motor;

class Filter extends \Ess\M2ePro\Model\ActiveRecord\Component\AbstractModel
{
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
        $this->_init(\Ess\M2ePro\Model\ResourceModel\Ebay\Motor\Filter::class);
    }

    public function delete()
    {
        /** @var \Ess\M2ePro\Helper\Component\Ebay\Motors $ebayMotorsHelper */
        $ebayMotorsHelper = $this->componentEbayMotors;
        $groupsIds = $ebayMotorsHelper->getGroupsAssociatedWithFilter($this->getId());

        foreach ($groupsIds as $groupId) {
            /** @var \Ess\M2ePro\Model\Ebay\Motor\Group $group */
            $group = $this->activeRecordFactory->getObject('Ebay_Motor_Group');
            $group->load($groupId);
            $group->removeFiltersByIds([$this->getId()]);
        }

        $associatedProductsIds = $ebayMotorsHelper->getAssociatedProducts($this->getId(), 'FILTER');
        $ebayMotorsHelper->resetOnlinePartsData($associatedProductsIds);

        if (!parent::delete()) {
            return false;
        }

        $connection = $this->getResource()->getConnection();
        $filterGroupRelation = $this->getHelper('Module_Database_Structure')
            ->getTableNameWithPrefix('m2epro_ebay_motor_filter_to_group');
        $connection->delete($filterGroupRelation, ['filter_id = ?' => $this->getId()]);

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

    public function getConditions($asObject = true)
    {
        if ($asObject) {
            return $this->getSettings('conditions');
        }
        return $this->getData('conditions');
    }

    public function getNote()
    {
        return $this->getData('note');
    }
}
