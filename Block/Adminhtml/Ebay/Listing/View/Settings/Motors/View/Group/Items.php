<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing\View\Settings\Motors\View\Group;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\View\Settings\Motors\View\Group\Items
 */
class Items extends \Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock
{
    private $group;
    private $groupId;

    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        //------------------------------
        $this->setId('ebayMotorViewGroupItemsPopup');
        //------------------------------

        $this->setTemplate('ebay/listing/view/settings/motors/view/group/items.phtml');
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

    public function getItemTitle()
    {
        return $this->getGroup()->isTypeEpid() ?
            $this->__('ePID') :
            $this->__('kType');
    }

    //########################################
}
