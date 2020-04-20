<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing\View\Settings\Motors\View\Group;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\View\Settings\Motors\View\Group\Filters
 */
class Filters extends \Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock
{
    private $group;
    private $groupId;

    private $resourceConnection;

    //#########################################

    public function __construct(
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        array $data = []
    ) {
        $this->resourceConnection = $resourceConnection;

        parent::__construct($context, $data);
    }

    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        //------------------------------
        $this->setId('ebayMotorViewGroupFiltersPopup');
        //------------------------------

        $this->setTemplate('ebay/listing/view/settings/motors/view/group/filters.phtml');
    }

    //########################################

    /**
     * @return mixed
     */
    public function getGroupId()
    {
        return $this->groupId;
    }

    /**
     * @param mixed $groupId
     */
    public function setGroupId($groupId)
    {
        $this->groupId = $groupId;
    }

    /**
     * @return \Ess\M2ePro\Model\Ebay\Motor\Group
     */
    public function getGroup()
    {
        if ($this->group === null) {
            $this->group = $this->activeRecordFactory->getObjectLoaded('Ebay_Motor_Group', $this->getGroupId());
        }

        return $this->group;
    }

    //########################################

    public function getFilters()
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Ebay\Motor\Filter\Collection $collection */
        $collection = $this->activeRecordFactory->getObject('Ebay_Motor_Filter')->getCollection();

        $collection->getSelect()->join(
            [
                'ftg' => $this->getHelper('Module_Database_Structure')
                    ->getTableNameWithPrefix('m2epro_ebay_motor_filter_to_group')
            ],
            'ftg.filter_id=main_table.id',
            []
        );

        $collection->getSelect()->where('group_id = ?', (int)$this->getGroupId());

        return $collection->getItems();
    }

    //########################################
}
