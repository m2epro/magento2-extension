<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\ControlPanel\Inspection;

use Ess\M2ePro\Block\Adminhtml\Magento\Context\Template;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\ControlPanel\Inspection\VersionInfo
 */
class VersionInfo extends AbstractInspection
{
    //########################################

    public function _construct()
    {
        parent::_construct();

        $this->setId('controlPanelInspectionVersionInfo');
        $this->setTemplate('control_panel/inspection/versionInfo.phtml');

        $this->prepareInfo();
    }

    //########################################

    protected function prepareInfo()
    {
        $this->currentVersion = $this->getHelper('Module')->getPublicVersion();

        $this->latestPublicVersion = $this->getHelper('Module')->getRegistry()->getValue(
            '/installation/public_last_version/'
        );
        $this->latestVersion  = $this->getHelper('Module')->getRegistry()->getValue(
            '/installation/build_last_version/'
        );
    }

    //########################################
}
