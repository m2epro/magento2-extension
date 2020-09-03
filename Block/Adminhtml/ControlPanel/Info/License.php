<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\ControlPanel\Info;

use Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\ControlPanel\Info\License
 */
class License extends AbstractBlock
{
    //########################################

    public function _construct()
    {
        parent::_construct();

        $this->setId('controlPanelInfoLicense');
        $this->setTemplate('control_panel/info/license.phtml');
    }

    //########################################

    protected function _beforeToHtml()
    {
        /** @var \Ess\M2ePro\Helper\Module\License $licenseHelper */
        $licenseHelper = $this->getHelper('Module_License');

        $this->licenseData = [
            'key'    => $this->getHelper('Data')->escapeHtml($licenseHelper->getKey()),
            'domain' => $this->getHelper('Data')->escapeHtml($licenseHelper->getDomain()),
            'ip'     => $this->getHelper('Data')->escapeHtml($licenseHelper->getIp()),
            'valid'  => [
                'domain' => $licenseHelper->isValidDomain(),
                'ip'     => $licenseHelper->isValidIp()
            ]
        ];

        $this->locationData = [
            'domain'             => $this->getHelper('Client')->getDomain(),
            'ip'                 => $this->getHelper('Client')->getIp(),
            'directory'          => $this->getHelper('Client')->getBaseDirectory(),
            'relative_directory' => $this->getHelper('Module')->getBaseRelativeDirectory()
        ];

        return parent::_beforeToHtml();
    }

    //########################################
}
