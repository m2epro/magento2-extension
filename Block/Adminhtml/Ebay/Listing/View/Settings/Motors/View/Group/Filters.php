<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing\View\Settings\Motors\View\Group;

class Filters extends \Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock
{
    private $group;
    private $groupId;

    /** @var \Magento\Framework\App\ResourceConnection  */
    private $resourceConnection;

    /** @var \Ess\M2ePro\Helper\Module\Database\Structure */
    private $databaseHelper;

    public function __construct(
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Ess\M2ePro\Helper\Module\Database\Structure $databaseHelper,
        array $data = []
    ) {
        $this->resourceConnection = $resourceConnection;

        parent::__construct($context, $data);
        $this->databaseHelper = $databaseHelper;
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
                'ftg' => $this->databaseHelper
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
