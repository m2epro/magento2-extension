<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Helper;

class AbstractHelper extends \Magento\Framework\App\Helper\AbstractHelper
{
    protected $helperFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Framework\App\Helper\Context $context
    )
    {
        $this->helperFactory = $helperFactory;
        parent::__construct($context);
    }

    //########################################

    /**
     * @param $helperName
     * @param array $arguments
     * @return \Magento\Framework\App\Helper\AbstractHelper
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function getHelper($helperName, array $arguments = [])
    {
        return $this->helperFactory->getObject($helperName, $arguments);
    }

    //########################################

}