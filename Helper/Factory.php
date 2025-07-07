<?php

declare(strict_types=1);

namespace Ess\M2ePro\Helper;

class Factory
{
    private \Magento\Framework\ObjectManagerInterface $objectManager;

    public function __construct(\Magento\Framework\ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * @deprecated
     * @use self::getObjectByClass
     *
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

    /**
     * @psalm-template T
     * @psalm-param T $className
     *
     * @param string $className
     *
     * @return T
     */
    public function getObjectByClass(string $className)
    {
        return $this->objectManager->get($className);
    }
}
