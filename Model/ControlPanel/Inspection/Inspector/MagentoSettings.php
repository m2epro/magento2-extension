<?php

namespace Ess\M2ePro\Model\ControlPanel\Inspection\Inspector;

use Ess\M2ePro\Model\ControlPanel\Inspection\AbstractInspection;
use Ess\M2ePro\Model\ControlPanel\Inspection\InspectorInterface;
use Ess\M2ePro\Model\ControlPanel\Inspection\Manager;

class MagentoSettings extends AbstractInspection implements InspectorInterface
{
    //########################################

    public function getTitle()
    {
        return 'Magento settings';
    }

    public function getExecutionSpeed()
    {
        return Manager::EXECUTION_SPEED_FAST;
    }

    public function getDescription()
    {
        return <<<HTML
- Non-default Magento timezone set<br>
- GD library is installed<br>
- [APC|Memchached|Redis] Cache is enabled<br>
HTML;
    }

    public function getGroup()
    {
        return Manager::GROUP_STRUCTURE;
    }

    //########################################

    public function process()
    {
        $issues = [];

        if (!extension_loaded('gd') || !function_exists('gd_info')) {
            $issues[] = $this->resultFactory->createError(
                $this,
                'GD library is not installed.'
            );
        }

        if ($this->helperFactory->getObject('Data')->getDefaultTimeZone() !== 'UTC') {
            $issues[] = $this->resultFactory->createError(
                $this,
                'Non-default Magento timezone set.',
                $this->helperFactory->getObject('Data')->getDefaultTimeZone()
            );
        }

        if ($this->helperFactory->getObject('Client_Cache')->isApcAvailable()) {
            $issues[] = $this->resultFactory->createNotice(
                $this,
                'APC Cache is enabled.'
            );
        }

        if ($this->helperFactory->getObject('Client_Cache')->isMemchachedAvailable()) {
            $issues[] = $this->resultFactory->createNotice(
                $this,
                'Memchached Cache is enabled.'
            );
        }

        if ($this->helperFactory->getObject('Client_Cache')->isRedisAvailable()) {
            $issues[] = $this->resultFactory->createNotice(
                $this,
                'Redis Cache is enabled.'
            );
        }

        return $issues;
    }

    //########################################
}
