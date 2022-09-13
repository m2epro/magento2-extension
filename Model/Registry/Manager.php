<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Registry;

class Manager
{
    /** @var \Ess\M2ePro\Model\RegistryFactory */
    private $registryFactory;
    /** @var \Ess\M2ePro\Model\ResourceModel\Registry */
    private $registryResource;

    public function __construct(
        \Ess\M2ePro\Model\RegistryFactory $registryFactory,
        \Ess\M2ePro\Model\ResourceModel\Registry $registryResource
    ) {
        $this->registryFactory = $registryFactory;
        $this->registryResource = $registryResource;
    }

    // ----------------------------------------

    /**
     * @param $key
     * @param $value
     *
     * @return bool
     */
    public function setValue($key, $value): bool
    {
        if (is_array($value)) {
            $value = json_encode($value);
        }

        $registryModel = $this->loadByKey($key);
        $registryModel->setValue($value);
        $registryModel->save();

        return true;
    }

    /**
     * @param string $key
     *
     * @return array|mixed|null
     */
    public function getValue(string $key)
    {
        return $this->loadByKey($key)->getValue();
    }

    /**
     * @param $key
     *
     * @return array|bool|null
     */
    public function getValueFromJson($key)
    {
        $registryModel = $this->loadByKey($key);
        if (!$registryModel->getId()) {
            return [];
        }

        return json_decode($registryModel->getValue(), true);
    }

    /**
     * @param $key
     *
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteValue($key): void
    {
        $this->registryResource->deleteByKey($key);
    }

    // ----------------------------------------

    /**
     * @param string $key
     *
     * @return \Ess\M2ePro\Model\Registry
     */
    private function loadByKey(string $key): \Ess\M2ePro\Model\Registry
    {
        $registryModel = $this->registryFactory->create();
        $this->registryResource->load($registryModel, $key, 'key');

        if (!$registryModel->getId()) {
            $registryModel->setKey($key);
        }

        return $registryModel;
    }

    // ----------------------------------------
}
