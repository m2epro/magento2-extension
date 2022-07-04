<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Helper\Component\Amazon;

class Configuration
{
    private const CONFIG_GROUP = '/amazon/configuration/';

    /** @var \Ess\M2ePro\Model\Config\Manager */
    private $config;

    public function __construct(
        \Ess\M2ePro\Model\Config\Manager $config
    ) {
        $this->config = $config;
    }

    // ----------------------------------------

    public function getBusinessMode()
    {
        return (int)$this->config->getGroupValue(
            self::CONFIG_GROUP,
            'business_mode'
        );
    }

    public function isEnabledBusinessMode()
    {
        return $this->getBusinessMode() == 1;
    }

    //########################################

    public function setConfigValues(array $values)
    {
        if (isset($values['business_mode'])) {
            $this->config->setGroupValue(
                self::CONFIG_GROUP,
                'business_mode',
                $values['business_mode']
            );
        }
    }

    //########################################
}
