<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Helper\Component\Amazon;

class Configuration extends \Ess\M2ePro\Helper\AbstractHelper
{
    const CONFIG_GROUP = '/amazon/configuration/';

    /** @var \Ess\M2ePro\Helper\Module */
    protected $helperModule;

    public function __construct(
        \Ess\M2ePro\Helper\Module $helperModule,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Framework\App\Helper\Context $context
    )
    {
        parent::__construct($helperFactory, $context);

        $this->helperModule = $helperModule;
    }

    //########################################

    public function getBusinessMode()
    {
        return (int)$this->helperModule->getConfig()->getGroupValue(
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
            $this->helperModule->getConfig()->setGroupValue(
                self::CONFIG_GROUP,
                'business_mode',
                $values['business_mode']
            );
        }
    }

    //########################################
}
