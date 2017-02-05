<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\HealthStatus;

class CurrentStatus extends \Ess\M2ePro\Model\AbstractModel
{
    /** @var \Ess\M2ePro\Model\Config\Manager\Cache */
    protected $cacheConfig;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\Config\Manager\Cache $cacheConfig,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        array $data = []
    ){
        parent::__construct($helperFactory, $modelFactory, $data);
        $this->cacheConfig = $cacheConfig;
    }

    //########################################

    public function get()
    {
        return (int)$this->cacheConfig->getGroupValue('/health_status/', 'current_status');
    }

    public function set($result)
    {
        return $this->cacheConfig->setGroupValue('/health_status/', 'current_status', (int)$result);
    }

    //########################################
}