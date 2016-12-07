<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2016 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Listing\View;

abstract class Switcher extends \Ess\M2ePro\Block\Adminhtml\Switcher
{
    protected $paramName = 'view_mode';
    protected $viewMode = null;

    //########################################

    abstract protected function getComponentMode();

    abstract protected function getDefaultViewMode();

    //########################################

    public function getLabel()
    {
        return $this->__('View Mode');
    }

    public function hasDefaultOption()
    {
        return false;
    }

    public function getStyle()
    {
        return self::ADVANCED_STYLE;
    }

    public function getDefaultParam()
    {
        $listing = $this->activeRecordFactory->getCachedObjectLoaded(
            'Listing', $this->getRequest()->getParam('id')
        );

        $sessionViewMode = $this->getHelper('Data\Session')->getValue(
            "{$this->getComponentMode()}_listing_{$listing->getId()}_view_mode"
        );

        if (is_null($sessionViewMode)) {
            return $this->getDefaultViewMode();
        }

        return $sessionViewMode;
    }

    public function getSelectedParam()
    {
        if (!is_null($this->viewMode)) {
            return $this->viewMode;
        }

        $selectedViewMode = parent::getSelectedParam();

        $listing = $this->activeRecordFactory->getCachedObjectLoaded(
            'Listing', $this->getRequest()->getParam('id')
        );

        $this->getHelper('Data\Session')->setValue(
            "{$this->getComponentMode()}_listing_{$listing->getId()}_view_mode", $selectedViewMode
        );

        $this->viewMode = $selectedViewMode;

        return $this->viewMode;
    }

    //########################################
}