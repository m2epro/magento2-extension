<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Helper;

/**
 * Class \Ess\M2ePro\Helper\Primary
 */
class Primary extends \Ess\M2ePro\Helper\AbstractHelper
{
    protected $primaryConfig;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\Config\Manager\Primary $primaryConfig,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Framework\App\Helper\Context $context
    ) {
        $this->primaryConfig = $primaryConfig;
        parent::__construct($helperFactory, $context);
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Config\Manager\Primary
     */
    public function getConfig()
    {
        return $this->primaryConfig;
    }

    //########################################

    public function getModules()
    {
        return $this->getConfig()->getAllGroupValues('/modules/');
    }

    //########################################
}
