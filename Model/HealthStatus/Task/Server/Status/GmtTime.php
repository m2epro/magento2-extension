<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\HealthStatus\Task\Server\Status;

use Ess\M2ePro\Model\Exception;
use Ess\M2ePro\Model\HealthStatus\Task\IssueType;
use Ess\M2ePro\Model\HealthStatus\Task\Result as TaskResult;

/**
 * Class \Ess\M2ePro\Model\HealthStatus\Task\Server\Status\GmtTime
 */
class GmtTime extends IssueType
{
    const DIFF_CRITICAL_LEVEL = 30;
    const DIFF_WARNING_LEVEL  = 15;
    const REQUEST_TIME_TOOK_TOO_LONG_LEVEL  = 10;

    /** @var \Ess\M2ePro\Model\HealthStatus\Task\Result\Factory */
    private $resultFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\HealthStatus\Task\Result\Factory $resultFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory
    ) {
        parent::__construct($helperFactory, $modelFactory);
        $this->resultFactory = $resultFactory;
    }

    //########################################

    public function process()
    {
        $dispatcherObject = $this->modelFactory->getObject('M2ePro\Connector\Dispatcher');
        $connectorObj = $dispatcherObject->getVirtualConnector('server', 'get', 'gmtTime');

        $requestTimeStart = microtime(true);
        $dispatcherObject->process($connectorObj);
        $requestTimeEnd = microtime(true);

        $responseData = $connectorObj->getResponseData();
        $requestTime = (int)($requestTimeEnd - $requestTimeStart);

        if (!isset($responseData['time']) || $requestTime > self::REQUEST_TIME_TOOK_TOO_LONG_LEVEL) {
            throw new Exception('Getting the current time from server took too long.');
        }

        $localTime = new \DateTime('now', new \DateTimeZone('UTC'));
        $serverTime = new \DateTime($responseData['time'], new \DateTimeZone('UTC'));

        $timeDifference = abs($localTime->getTimestamp() - $serverTime->getTimestamp());

        $result = $this->resultFactory->create($this);
        $result->setTaskData($responseData['time']);
        $result->setTaskResult(TaskResult::STATE_SUCCESS);

        if ($timeDifference >= self::DIFF_WARNING_LEVEL) {
            $result->setTaskResult(TaskResult::STATE_WARNING);
            $result->setTaskMessage($this->getHelper('Module\Translation')->translate([
            <<<HTML
Your Server Time <b>%time%</b> (UTC) needs to be updated based on the actual local time. 
It is important for the correct data synchronization with Channels.  
Please consult your Server Administrator/Developer to adjust the settings.
HTML
                ,
                $localTime->format('H:i:s')
            ]));
        }

        if ($timeDifference >= self::DIFF_CRITICAL_LEVEL) {
            $result->setTaskResult(TaskResult::STATE_CRITICAL);
            $result->setTaskMessage($this->getHelper('Module\Translation')->translate([
            <<<HTML
Your Server Time <b>%time%</b> (UTC) needs to be updated based on the actual local time.
 It is important for the correct data synchronization with Channels.  
Please consult your Server Administrator/Developer to adjust the settings.
HTML
                ,
                $localTime->format('H:i:s')
            ]));
        }

        return $result;
    }

    //########################################
}
