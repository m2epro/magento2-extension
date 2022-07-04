<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\ControlPanel\Inspection;

class Cron extends AbstractInspection
{
    /** @var \Ess\M2ePro\Helper\Module\Cron */
    protected $cronHelper;

    /** @var \Ess\M2ePro\Model\Config\Manager */
    private $config;

    /**
     * @param \Ess\M2ePro\Model\Config\Manager $config
     * @param \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context
     * @param array $data
     */
    public function __construct(
        \Ess\M2ePro\Helper\Module\Cron $cronHelper,
        \Ess\M2ePro\Model\Config\Manager $config,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        array $data = []
    ) {
        $this->cronHelper = $cronHelper;
        $this->config = $config;
        parent::__construct($context, $data);
    }

    public function _construct()
    {
        parent::_construct();

        $this->setId('controlPanelInspectionCron');
        $this->setTemplate('control_panel/inspection/cron.phtml');
    }

    // ----------------------------------------

    protected function _beforeToHtml()
    {
        $this->cronLastRunTime = 'N/A';
        $this->cronIsNotWorking = false;
        $this->cronCurrentRunner = ucwords(str_replace('_', ' ', $this->cronHelper->getRunner()));
        $this->cronServiceAuthKey = $this->config->getGroupValue('/cron/service/', 'auth_key');

        $cronLastRunTime = $this->cronHelper->getLastRun();
        if ($cronLastRunTime !== null) {
            $this->cronLastRunTime = $cronLastRunTime;
            $this->cronIsNotWorking = $this->cronHelper->isLastRunMoreThan(1, true);
        }

        $this->isMagentoCronDisabled    = (bool)(int)$this->config->getGroupValue('/cron/magento/', 'disabled');
        $this->isControllerCronDisabled = (bool)(int)$this->config->getGroupValue('/cron/service_controller/', 'disabled');
        $this->isPubCronDisabled        = (bool)(int)$this->config->getGroupValue('/cron/service_pub/', 'disabled');

        return parent::_beforeToHtml();
    }

    //########################################

    public function isShownServiceDescriptionMessage()
    {
        if (!$this->getData('is_support_mode')) {
            return false;
        }

        if ($this->cronHelper->isRunnerService() && !$this->cronIsNotWorking) {
            return true;
        }

        return false;
    }

    //########################################
}
