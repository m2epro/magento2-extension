<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\ControlPanel\Inspection;

class VersionInfo extends AbstractInspection
{
    /** @var \Ess\M2ePro\Helper\Module */
    private $moduleHelper;
    /** @var \Ess\M2ePro\Model\Registry\Manager */
    private $registry;

    /**
     * @param \Ess\M2ePro\Helper\Module $moduleHelper
     * @param \Ess\M2ePro\Model\Registry\Manager $registry
     * @param \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context
     * @param array $data
     */
    public function __construct(
        \Ess\M2ePro\Helper\Module $moduleHelper,
        \Ess\M2ePro\Model\Registry\Manager $registry,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        array $data = []
    ) {
        $this->moduleHelper = $moduleHelper;
        $this->registry = $registry;
        parent::__construct($context, $data);
    }

    public function _construct()
    {
        parent::_construct();

        $this->setId('controlPanelInspectionVersionInfo');
        $this->setTemplate('control_panel/inspection/versionInfo.phtml');

        $this->prepareInfo();
    }

    // ----------------------------------------

    protected function prepareInfo()
    {
        $this->currentVersion = $this->moduleHelper->getPublicVersion();

        $this->latestPublicVersion = $this->registry->getValue(
            '/installation/public_last_version/'
        );
        $this->latestVersion = $this->registry->getValue(
            '/installation/build_last_version/'
        );
    }
}
