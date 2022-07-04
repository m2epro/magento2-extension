<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Helper;

class Factory
{
    /** @var \Magento\Framework\ObjectManagerInterface */
    private $objectManager;

    /**
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     */
    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager
    ) {
        $this->objectManager = $objectManager;
    }

    /**
     * @param string $helperName
     *
     * @return object
     */
    public function getObject(string $helperName)
    {
        // fix for Magento2 sniffs that forcing to use ::class
        $helperName = str_replace('_', '\\', $helperName);

        return $this->objectManager->get('\Ess\M2ePro\Helper\\' . $helperName);
    }
}
