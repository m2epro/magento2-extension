<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Helper;

class Factory
{
    protected $objectManager;

    //########################################

    /**
     * Construct
     *
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     */
    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager
    )
    {
        $this->objectManager = $objectManager;
    }

    //########################################

    /**
     * @param $helperName
     * @param array $arguments
     * @return \Magento\Framework\App\Helper\AbstractHelper
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getObject($helperName, array $arguments = [])
    {
        $helper = $this->objectManager->get('\Ess\M2ePro\Helper\\'.$helperName, $arguments);

        if (!$helper instanceof \Magento\Framework\App\Helper\AbstractHelper) {
            throw new \Ess\M2ePro\Model\Exception\Logic(
                __('%1 doesn\'t extends \Magento\Framework\App\Helper\AbstractHelper', $helperName)
            );
        }

        return $helper;
    }

    //########################################
}
