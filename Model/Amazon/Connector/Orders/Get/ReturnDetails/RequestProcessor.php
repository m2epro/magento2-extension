<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Amazon\Connector\Orders\Get\ReturnDetails;

class RequestProcessor
{
    public const REQUEST_PARAM_KEY_FROM_DATE = 'from_date';
    public const REQUEST_PARAM_KEY_START_PROCESS_DATE = 'start_process_date';

    private \Ess\M2ePro\Model\Amazon\Connector\DispatcherFactory $dispatcherFactory;

    public function __construct(\Ess\M2ePro\Model\Amazon\Connector\DispatcherFactory $dispatcherFactory)
    {
        $this->dispatcherFactory = $dispatcherFactory;
    }

    /**
     * @throws \Exception
     */
    public function process(\Ess\M2ePro\Model\Account $account, ?\DateTime $fromDate): void
    {
        $dispatcher = $this->dispatcherFactory->create();

        $requestParams = [
            self::REQUEST_PARAM_KEY_FROM_DATE => null,
            self::REQUEST_PARAM_KEY_START_PROCESS_DATE => \Ess\M2ePro\Helper\Date::createCurrentGmt()->format('Y-m-d H:i:s'),
        ];

        if ($fromDate !== null) {
            $requestParams[self::REQUEST_PARAM_KEY_FROM_DATE] = $fromDate->format('Y-m-d H:i:s');
        }

        $connector = $dispatcher->getConnectorByClass(
            \Ess\M2ePro\Model\Cron\Task\Amazon\Order\Receive\ReturnDetails\Requester::class,
            $requestParams,
            $account
        );

        $dispatcher->process($connector);
    }
}
