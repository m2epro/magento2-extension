<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\HealthStatus;

use Ess\M2ePro\Model\HealthStatus\Task\Result\Set;

/**
 * Class \Ess\M2ePro\Model\HealthStatus\CurrentStatus
 */
class CurrentStatus extends \Ess\M2ePro\Model\AbstractModel
{
    /** @var \Ess\M2ePro\Model\ActiveRecord\Factory  */
    protected $activeRecordFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        array $data = []
    ) {
        parent::__construct($helperFactory, $modelFactory, $data);
        $this->activeRecordFactory = $activeRecordFactory;
    }

    //########################################

    public function get()
    {
        return (int)$this->getHelper('Module')->getRegistry()->getValue('/health_status/current_status/');
    }

    public function set(Set $resultSet)
    {
        $this->getHelper('Module')->getRegistry()->setValue(
            '/health_status/current_status/',
            (int)$resultSet->getWorstState()
        );

        $details = [];
        foreach ($resultSet->getByKeys() as $result) {
            $details[$result->getTaskHash()] = [
                'result' => $result->getTaskResult(),
                'data'   => $result->getTaskData()
            ];
        }

        $this->getHelper('Module')->getRegistry()->setValue(
            '/health_status/details/',
            $this->getHelper('Data')->jsonEncode($details)
        );

        return true;
    }

    //########################################
}
