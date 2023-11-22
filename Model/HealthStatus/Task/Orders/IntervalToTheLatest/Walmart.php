<?php

namespace Ess\M2ePro\Model\HealthStatus\Task\Orders\IntervalToTheLatest;

use Ess\M2ePro\Helper\Component\Walmart as WalmartComponent;

class Walmart extends AbstractOrdersSync
{
    protected function getComponent(): string
    {
        return WalmartComponent::NICK;
    }

    protected function getErrorText(): string
    {
        $manageUrl = $this->getUrlBuilder()->getUrl('m2epro/synchronization_log/index/referrer/walmart/');
        $supportUrl = 'support@m2epro.com';

        return (string)__(
            'Recent synchronization of Walmart orders has not been successful. Please make sure that <br>'
            . '- the Cron Service and Server connection are properly configured.<br>'
            . '- the last M2E Pro installation/upgrade went well.<br>'
            . '- there are no errors in the <a target="_blank" href="%1">Synchronization Logs</a><br>'
            . 'If you need assistance, contact Support at <a href="mailto:%2">%2</a>.',
            $manageUrl,
            $supportUrl
        );
    }
}
