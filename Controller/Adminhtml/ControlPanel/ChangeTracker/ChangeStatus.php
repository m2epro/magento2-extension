<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\ControlPanel\ChangeTracker;

use Ess\M2ePro\Controller\Adminhtml\Context;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\ControlPanel\Cron\Run
 */
class ChangeStatus extends \Ess\M2ePro\Controller\Adminhtml\ControlPanel\Main
{
    /**
     * @var \Ess\M2ePro\Helper\Module\ChangeTracker
     */
    private $changeTrackerHelper;
    /**
     * @var \Ess\M2ePro\Helper\Module\Configuration
     */
    private $configurationHelper;

    /**
     * @param Context $context
     * @param \Ess\M2ePro\Helper\Module\Configuration $configurationHelper
     * @param \Ess\M2ePro\Helper\Module\ChangeTracker $changeTrackerHelper
     */
    public function __construct(
        Context $context,
        \Ess\M2ePro\Helper\Module\Configuration $configurationHelper,
        \Ess\M2ePro\Helper\Module\ChangeTracker $changeTrackerHelper
    ) {
        parent::__construct($context);
        $this->changeTrackerHelper = $changeTrackerHelper;
        $this->configurationHelper = $configurationHelper;
    }

    /**
     * Execute action based on request and return result
     * @return \Magento\Framework\Controller\ResultInterface|ResponseInterface
     */
    public function execute()
    {
        $request = $this->getRequest();
        if ($request === null) {
            $this->setJsonContent([
                'error' => true,
                'message' => 'Empty request',
            ]);

            return $this->getResult();
        }

        $changeTrackerStatus = $request->getParam('change_tracker_status', 0);
        $directChangesStatus = $request->getParam('track_direct_status', 0);
        $timeout = (int)$request->getParam('timeout', 5);
        $logLevel = (int)$request->getParam('log_level', 200);

        $this->changeTrackerHelper->setStatus($changeTrackerStatus);
        $this->changeTrackerHelper->setLogLevel($logLevel);
        $this->changeTrackerHelper->setInterval($timeout);
        $this->configurationHelper->setConfigValues([
            'listing_product_inspector_mode' => $directChangesStatus,
        ]);

        $this->setJsonContent([
            'error' => false,
            'message' => 'Configuration saved!',
        ]);

        return $this->getResult();
    }
}
