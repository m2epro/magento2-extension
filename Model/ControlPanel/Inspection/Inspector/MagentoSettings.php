<?php

namespace Ess\M2ePro\Model\ControlPanel\Inspection\Inspector;

use Ess\M2ePro\Model\ControlPanel\Inspection\InspectorInterface;
use Ess\M2ePro\Helper\Factory as HelperFactory;
use Ess\M2ePro\Model\ControlPanel\Inspection\Issue\Factory as IssueFactory;

class MagentoSettings implements InspectorInterface
{
    /** @var HelperFactory */
    private $helperFactory;

    /** @var IssueFactory */
    private $issueFactory;

    //########################################

    public function __construct(
        HelperFactory $helperFactory,
        IssueFactory $issueFactory
    ) {
        $this->helperFactory = $helperFactory;
        $this->issueFactory = $issueFactory;
    }

    //########################################

    public function process()
    {
        $issues = [];

        if (!extension_loaded('gd') || !function_exists('gd_info')) {
            $issues[] = $this->issueFactory->create(
                'GD library is not installed.'
            );
        }

        if ($this->helperFactory->getObject('Data')->getDefaultTimeZone() !== 'UTC') {
            $issues[] = $this->issueFactory->create(
                'Non-default Magento timezone set.',
                $this->helperFactory->getObject('Data')->getDefaultTimeZone()
            );
        }

        if ($this->helperFactory->getObject('Client_Cache')->isApcAvailable()) {
            $issues[] = $this->issueFactory->create(
                'APC Cache is enabled.'
            );
        }

        if ($this->helperFactory->getObject('Client_Cache')->isMemchachedAvailable()) {
            $issues[] = $this->issueFactory->create(
                'Memchached Cache is enabled.'
            );
        }

        if ($this->helperFactory->getObject('Client_Cache')->isRedisAvailable()) {
            $issues[] = $this->issueFactory->create(
                'Redis Cache is enabled.'
            );
        }

        return $issues;
    }

    //########################################
}
