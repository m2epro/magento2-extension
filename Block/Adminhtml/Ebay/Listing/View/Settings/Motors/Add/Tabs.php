<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing\View\Settings\Motors\Add;

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
        $motorsType = $this->getHelper('Component\Ebay\Motors')->getIdentifierKey($motorsType);

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
        $filtersGrid = $this->createBlock('Ebay\Listing\View\Settings\Motors\Add\Filter\Grid');
        $filtersGrid->setMotorsType($this->getMotorsType());

        $this->addTab('filters', [
            'label'   => $this->__('Filters'),
            'title'   => $this->__('Filters'),
            'content' => $filtersGrid->toHtml()
        ]);
        //------------------------------

        //------------------------------
        /** @var \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\View\Settings\Motors\Add\Group\Grid $groupsGrid */
        $groupsGrid = $this->createBlock('Ebay\Listing\View\Settings\Motors\Add\Group\Grid');
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
        if (is_null($this->motorsType)) {
            throw new \Ess\M2ePro\Model\Exception\Logic('Motors type not set.');
        }

        return $this->motorsType;
    }

    //########################################

    public function getItemsTabTitle()
    {
        if ($this->getHelper('Component\Ebay\Motors')->isTypeBasedOnEpids($this->getMotorsType())) {
            return $this->__('ePID(s)');
        }

        return $this->__('kType(s)');
    }

    //########################################
}