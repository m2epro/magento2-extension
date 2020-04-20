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
    const DETAILS_REGISTRY_KEY = '/health_status/details/';

    /** @var \Ess\M2ePro\Model\Config\Manager\Cache */
    protected $cacheConfig;

    /** @var \Ess\M2ePro\Model\ActiveRecord\Factory  */
    protected $activeRecordFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\Config\Manager\Cache $cacheConfig,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        array $data = []
    ) {
        parent::__construct($helperFactory, $modelFactory, $data);
        $this->cacheConfig = $cacheConfig;
        $this->activeRecordFactory = $activeRecordFactory;
    }

    //########################################

    public function get()
    {
        return (int)$this->cacheConfig->getGroupValue('/health_status/', 'current_status');
    }

    public function set(Set $resultSet)
    {
        $this->cacheConfig->setGroupValue(
            '/health_status/',
            'current_status',
            (int)$resultSet->getWorstState()
        );

        $details = [];
        foreach ($resultSet->getByKeys() as $result) {
            $details[$result->getTaskHash()] = [
                'result' => $result->getTaskResult(),
                'data'   => $result->getTaskData()
            ];
        }

        $registry = $this->activeRecordFactory->getObjectLoaded(
            'Registry',
            self::DETAILS_REGISTRY_KEY,
            'key',
            false
        );

        !$registry && $registry = $this->activeRecordFactory->getObject('Registry');
        $registry->setData('key', self::DETAILS_REGISTRY_KEY);
        $registry->setData('value', $this->getHelper('Data')->jsonEncode($details));
        $registry->save();

        return true;
    }

    //########################################
}
