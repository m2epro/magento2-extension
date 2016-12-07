<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2016 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Log\Listing\View;

class Switcher extends \Ess\M2ePro\Block\Adminhtml\Switcher
{
    const VIEW_MODE_SEPARATED = 'separated';
    const VIEW_MODE_GROUPED   = 'grouped';

    protected $paramName = 'view_mode';
    protected $viewMode = null;

    //########################################

    public function getLabel()
    {
        return $this->__('View Mode');
    }

    public function getStyle()
    {
        return self::ADVANCED_STYLE;
    }

    public function hasDefaultOption()
    {
        return false;
    }

    public function getDefaultParam()
    {
        $sessionViewMode = $this->getHelper('Data\Session')->getValue(
            "{$this->getComponentMode()}_log_listing_view_mode"
        );

        if (is_null($sessionViewMode)) {
            return self::VIEW_MODE_SEPARATED;
        }

        return $sessionViewMode;
    }

    public function getSelectedParam()
    {
        if (!is_null($this->viewMode)) {
            return $this->viewMode;
        }

        $selectedViewMode = parent::getSelectedParam();

        $this->getHelper('Data\Session')->setValue(
            "{$this->getComponentMode()}_log_listing_view_mode", $selectedViewMode
        );

        $this->viewMode = $selectedViewMode;

        return $this->viewMode;
    }

    //---------------------------------------

    protected function loadItems()
    {
        $this->items = [
            'mode' => [
                'value' => [
                    [
                        'label' => $this->__('Separated'),
                        'value' => self::VIEW_MODE_SEPARATED
                    ],
                    [
                        'label' => $this->__('Grouped'),
                        'value' => self::VIEW_MODE_GROUPED
                    ],
                ]
            ]
        ];
    }

    //########################################
}