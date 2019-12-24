<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing\View\Settings\Motors\Add;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\View\Settings\Motors\Add\Tabs
 */
class Tabs extends \Ess\M2ePro\Block\Adminhtml\Magento\Tabs\AbstractHorizontalTabs
{
    protected $motorsType;

    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        //------------------------------
        $this->setId('ebayMotorAddTabs');
        //------------------------------

        $this->setDestElementId('ebay_motor_add_tabs_container');
    }

    //------------------------------

    protected function _beforeToHtml()
    {
        //------------------------------
        $motorsType = $this->getMotorsType();
        $motorsType = $this->getHelper('Component_Ebay_Motors')->getIdentifierKey($motorsType);

        /** @var \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\View\Settings\Motors\Add\Item\Grid $itemsGrid */
        $itemsGrid = $this->createBlock(
            'Ebay\Listing\View\Settings\Motors\Add\Item\\' . ucfirst($motorsType) . '\\Grid'
        );
        $itemsGrid->setMotorsType($this->getMotorsType());
        $title = $this->getItemsTabTitle();

        $this->addTab('items', [
            'label'   => $this->__($title),
            'title'   => $this->__('Child Products'),
            'content' => $itemsGrid->toHtml()
        ]);
        //------------------------------

        //------------------------------
        /** @var \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\View\Settings\Motors\Add\Filter\Grid $filtersGrid */
        $filtersGrid = $this->createBlock('Ebay_Listing_View_Settings_Motors_Add_Filter_Grid');
        $filtersGrid->setMotorsType($this->getMotorsType());

        $this->addTab('filters', [
            'label'   => $this->__('Filters'),
            'title'   => $this->__('Filters'),
            'content' => $filtersGrid->toHtml()
        ]);
        //------------------------------

        //------------------------------
        /** @var \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\View\Settings\Motors\Add\Group\Grid $groupsGrid */
        $groupsGrid = $this->createBlock('Ebay_Listing_View_Settings_Motors_Add_Group_Grid');
        $groupsGrid->setMotorsType($this->getMotorsType());

        $this->addTab('groups', [
            'label'   => $this->__('Groups'),
            'title'   => $this->__('Groups'),
            'content' => $groupsGrid->toHtml()
        ]);
        //------------------------------

        $this->setActiveTab('items');

        return parent::_beforeToHtml();
    }

    protected function _toHtml()
    {
        return parent::_toHtml() . '<div id="ebay_motor_add_tabs_container"></div>';
    }

    //########################################

    public function setMotorsType($motorsType)
    {
        $this->motorsType = $motorsType;
    }

    public function getMotorsType()
    {
        if ($this->motorsType === null) {
            throw new \Ess\M2ePro\Model\Exception\Logic('Motors type not set.');
        }

        return $this->motorsType;
    }

    //########################################

    public function getItemsTabTitle()
    {
        if ($this->getHelper('Component_Ebay_Motors')->isTypeBasedOnEpids($this->getMotorsType())) {
            return $this->__('ePID(s)');
        }

        return $this->__('kType(s)');
    }

    //########################################
}
