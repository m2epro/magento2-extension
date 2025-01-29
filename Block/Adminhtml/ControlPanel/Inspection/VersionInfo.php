<?php

namespace Ess\M2ePro\Block\Adminhtml\ControlPanel\Inspection;

class VersionInfo extends AbstractInspection
{
    private \Ess\M2ePro\Helper\Module $moduleHelper;

    public function __construct(
        \Ess\M2ePro\Helper\Module $moduleHelper,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        array $data = []
    ) {
        $this->moduleHelper = $moduleHelper;
        parent::__construct($context, $data);
    }

    public function _construct()
    {
        parent::_construct();

        $this->setId('controlPanelInspectionVersionInfo');
        $this->setTemplate('control_panel/inspection/versionInfo.phtml');

        $this->prepareInfo();
    }

    protected function prepareInfo()
    {
        $this->currentVersion = $this->moduleHelper->getPublicVersion();
        $this->latestPublicVersion = $this->moduleHelper->getLatestVersion();
    }
}
