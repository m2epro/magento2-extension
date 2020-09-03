<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\HealthStatus\Task\Server\Status;

use Ess\M2ePro\Model\HealthStatus\Task\IssueType;
use Ess\M2ePro\Model\HealthStatus\Task\Result as TaskResult;

/**
 * Class \Ess\M2ePro\Model\HealthStatus\Task\Server\Status\SystemLogs
 */
class SystemLogs extends IssueType
{
    const COUNT_CRITICAL_LEVEL = 1500;
    const COUNT_WARNING_LEVEL  = 500;
    const SEE_TO_BACK_INTERVAL = 3600;

    /** @var \Ess\M2ePro\Model\HealthStatus\Task\Result\Factory */
    private $resultFactory;

    /** @var \Ess\M2ePro\Model\ActiveRecord\Factory */
    private $activeRecordFactory;

    private $urlBuilder;
    //########################################

    public function __construct(
        \Ess\M2ePro\Model\HealthStatus\Task\Result\Factory $resultFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Magento\Framework\UrlInterface $urlBuilder
    ) {
        parent::__construct($helperFactory, $modelFactory);
        $this->resultFactory = $resultFactory;
        $this->activeRecordFactory = $activeRecordFactory;
        $this->urlBuilder = $urlBuilder;
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
M2E Pro has recorded <b>%exceptions%</b> 
messages to the System Log during the last hour. <a target="_blank" href="%url%">Click here</a> for the details.
HTML
                ,
                $exceptionsCount,
                $this->urlBuilder->getUrl('m2epro/developers/index')
            ]));
        }

        if ($exceptionsCount >= self::COUNT_CRITICAL_LEVEL) {
            $result->setTaskResult(TaskResult::STATE_CRITICAL);
            $result->setTaskMessage($this->getHelper('Module\Translation')->translate([
            <<<HTML
M2E Pro has recorded <b>%exceptions%</b> messages to the System Log during the last hour. 
<a href="%url%">Click here</a> for the details. 
HTML
                ,
                $exceptionsCount,
                $this->urlBuilder->getUrl('m2epro/developers/index')
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
