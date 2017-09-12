<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\HealthStatus\Task\Server\Status;

use Ess\M2ePro\Model\Exception;
use Ess\M2ePro\Model\HealthStatus\Task\IssueType;
use Ess\M2ePro\Model\HealthStatus\Task\Result as TaskResult;

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
    ){
        parent::__construct($helperFactory, $modelFactory);
        $this->resultFactory = $resultFactory;
    }

    //########################################

    public function process()
    {
        $dispatcherObject = $this->modelFactory->getObject('M2ePro\Connector\Dispatcher');
        $connectorObj = $dispatcherObject->getVirtualConnector('server','get','gmtTime');

        $requestTimeStart = microtime();
        $dispatcherObject->process($connectorObj);
        $requestTimeEnd = microtime();

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
The Time value which is used by your Server and in your Magento is different from the actual Time value by
several seconds. Your current Time value by UTC <b>%time%</b> is different from the reference one,
that might lead to the further issues with the data updating on Channels.<br>
Please, consult with your Server Administrator/Developer to adjust your settings.
HTML
                ,
                $localTime->format('H:i:s')
            ]));
        }

        if ($timeDifference >= self::DIFF_CRITICAL_LEVEL) {

            $result->setTaskResult(TaskResult::STATE_CRITICAL);
            $result->setTaskMessage($this->getHelper('Module\Translation')->translate([
<<<HTML
The Time value which is used by your Server and in your Magento is different from the actual Time value that might
be a sequence of incorrect configurations provided.
In order to correctly synchronize/update your data on Channels, the Time value specified in your system should be
identical to the reference Time value.<br>
However, your current Time value by UTC <b>%time%</b> is different from the reference one.
Please, consult with your Server Administrator/Developer to adjust your settings.
If you would like to get some more assistance in this matter, you can contact our Support Team via email
<a href="mailto:support@m2epro.com">support@m2epro.com</a>.
HTML
                ,
                $localTime->format('H:i:s')
            ]));
        }

        return $result;
    }

    //########################################
}