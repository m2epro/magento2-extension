<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Walmart\Listing\View;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Walmart\Listing\View\Switcher
 */
class Switcher extends \Ess\M2ePro\Block\Adminhtml\Listing\View\Switcher
{
    const VIEW_MODE_WALMART         = 'walmart';
    const VIEW_MODE_MAGENTO         = 'magento';
    const VIEW_MODE_SETTINGS        = 'settings';

    //########################################

    public function getDefaultViewMode()
    {
        return self::VIEW_MODE_WALMART;
    }

    public function getTooltip()
    {
        return $this->__(<<<HTML
    Switch between 3 View Modes to manage the related Item data:
    <ul class="list">
        <li>
            <strong>Walmart</strong> - displays the Products based on their Channel information, i.e.
            the current Item Price, Quantity, Status, etc. on Walmart. For each individual Item, you can edit
            the related SKU or Product ID values. The Mass Actions allows managing Walmart Items in bulk,
            i.e. List, Revise, Relist, Stop the Item, etc. <br>
            In Manage Variation pop-up, Walmart Variant Groups can be configured.
        </li>
        <li>
            <strong>Settings</strong> – allows you to assign a new Category Policy to the selected Item,
            duplicate the Item in the current Listing or move it to another one.
        </li>
        <li>
            <strong>Magento</strong> - displays the Products based on their Magento information, i.e.
            the current Product Price, Quantity, Stock Availability, Status, etc. in Magento.
        </li>
    </ul>
    To edit the entire Listing configurations, click <strong>Edit Settings</strong>.
HTML
        );
    }

    //---------------------------------------

    protected function getComponentMode()
    {
        return \Ess\M2ePro\Helper\Component\Walmart::NICK;
    }

    //---------------------------------------

    protected function loadItems()
    {
        $this->items = [
            'mode' => [
                'value' => [
                    [
                        'value' => self::VIEW_MODE_WALMART,
                        'label' => $this->getHelper('Component\Walmart')->getTitle()
                    ],
                    [
                        'value' => self::VIEW_MODE_SETTINGS,
                        'label' => $this->__('Settings')
                    ],
                    [
                        'value' => self::VIEW_MODE_MAGENTO,
                        'label' => $this->__('Magento')
                    ],
                ]
            ]
        ];
    }

    //########################################
}
