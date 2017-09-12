<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\HealthStatus\Task\Server\Status;

use Ess\M2ePro\Model\HealthStatus\Task\IssueType;
use Ess\M2ePro\Model\HealthStatus\Task\Result as TaskResult;

class SystemLogs extends IssueType
{
    const COUNT_CRITICAL_LEVEL = 1500;
    const COUNT_WARNING_LEVEL  = 500;
    const SEE_TO_BACK_INTERVAL = 3600;

    /** @var \Ess\M2ePro\Model\HealthStatus\Task\Result\Factory */
    private $resultFactory;

    /** @var \Ess\M2ePro\Model\ActiveRecord\Factory */
    private $activeRecordFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\HealthStatus\Task\Result\Factory $resultFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory
    ){
        parent::__construct($helperFactory, $modelFactory);
        $this->resultFactory = $resultFactory;
        $this->activeRecordFactory = $activeRecordFactory;
    }

    //########################################

    public function process()
    {
        $exceptionsCount = $this->getExceptionsCountByBackInterval(self::SEE_TO_BACK_INTERVAL);

        $result = $this->resultFactory->create($this);
        $result->setTaskResult(TaskResult::STATE_SUCCESS);
        $result->setTaskData($exceptionsCount);

        if ($exceptionsCount >= self::COUNT_WARNING_LEVEL) {

            $result->setTaskResult(TaskResult::STATE_WARNING);
            $result->setTaskMessage($this->getHelper('Module\Translation')->translate([
<<<HTML
M2E Pro records into the internal System Log all messages about any failure, temporary issues which might
potentially have a negative impact on running of M2E Pro.
There were <b>%exceptions%</b> new messages recorded during the last hour.
It might be a sequence of some issues with Module’s functioning, its particular parts or related technologies.
Please, consult with your Server Administrator/Developer to ensure that your Magento is correctly working.
HTML
                ,
                $exceptionsCount
            ]));
        }

        if ($exceptionsCount >= self::COUNT_CRITICAL_LEVEL) {

            $result->setTaskResult(TaskResult::STATE_CRITICAL);
            $result->setTaskMessage($this->getHelper('Module\Translation')->translate([
<<<HTML
M2E Pro records into the internal System Log all messages about any failure, temporary issues which might
potentially have a negative impact on running of M2E Pro.
There were <b>%exceptions%</b> new messages recorded during the last hour.
It might be a sequence of some issues with Module’s functioning, its particular parts or related technologies.
Please, consult with your Server Administrator/Developer to ensure that your Magento is correctly working.
Then if you will need some more assistance in this matter, you can contact our Support Team via email
<a href="mailto:support@m2epro.com">support@m2epro.com</a>.
HTML
                ,
                $exceptionsCount
            ]));
        }

        return $result;
    }

    //########################################

    private function getExceptionsCountByBackInterval($inSeconds)
    {
        $date = new \DateTime('now', new \DateTimeZone('UTC'));
        $date->modify("- {$inSeconds} seconds");

        $collection = $this->activeRecordFactory->getObject('Log\System')->getCollection();
        $collection->addFieldToFilter('type', ['neq' => '\Ess\M2ePro\Model\Exception\Connection']);
        $collection->addFieldToFilter('type', ['nlike' => '%Logging%']);
        $collection->addFieldToFilter('create_date', ['gt' => $date->format('Y-m-d H:i:s')]);

        return $collection->getSize();
    }

    //########################################
}