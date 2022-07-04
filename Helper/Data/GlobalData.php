<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Helper\Data;

class GlobalData
{
    /** @var \Magento\Framework\Registry */
    private $registryModel;

    /**
     * @param \Magento\Framework\Registry $registryModel
     */
    public function __construct(
        \Magento\Framework\Registry $registryModel
    ) {
        $this->registryModel = $registryModel;
    }

    /**
     * @param string $key
     *
     * @return mixed
     */
    public function getValue($key)
    {
        $globalKey = \Ess\M2ePro\Helper\Data::CUSTOM_IDENTIFIER . '_' . $key;

        return $this->registryModel->registry($globalKey);
    }

    /**
     * @param string $key
     * @param mixed $value
     *
     * @return void
     */
    public function setValue($key, $value): void
    {
        $globalKey = \Ess\M2ePro\Helper\Data::CUSTOM_IDENTIFIER . '_' . $key;
        $this->registryModel->register($globalKey, $value);
    }

    /**
     * @param string $key
     *
     * @return void
     */
    public function unsetValue($key): void
    {
        $globalKey = \Ess\M2ePro\Helper\Data::CUSTOM_IDENTIFIER . '_' . $key;
        $this->registryModel->unregister($globalKey);
    }
}
