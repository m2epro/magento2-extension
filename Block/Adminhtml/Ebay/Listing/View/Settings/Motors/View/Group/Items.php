<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing\View\Settings\Motors\View\Group;

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
        if (is_null($this->group)) {
            $this->group = $this->activeRecordFactory->getObjectLoaded('Ebay\Motor\Group', $this->getGroupId());
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