<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Servicing\Task;

class License implements \Ess\M2ePro\Model\Servicing\TaskInterface
{
    public const NAME = 'license';

    /** @var \Ess\M2ePro\Model\Config\Manager */
    private $configManager;

    /**
     * @param \Ess\M2ePro\Model\Config\Manager $configManager
     */
    public function __construct(
        \Ess\M2ePro\Model\Config\Manager $configManager
    ) {
        $this->configManager = $configManager;
    }

    // ----------------------------------------

    /**
     * @return string
     */
    public function getServerTaskName(): string
    {
        return self::NAME;
    }

    // ----------------------------------------

    /**
     * @return bool
     */
    public function isAllowed(): bool
    {
        return true;
    }

    // ----------------------------------------

    /**
     * @return array
     */
    public function getRequestData(): array
    {
        return [];
    }

    // ----------------------------------------

    /**
     * @param array $data
     *
     * @return void
     */
    public function processResponseData(array $data): void
    {
        if (isset($data['info']) && is_array($data['info'])) {
            $this->updateInfoData($data['info']);
        }

        if (isset($data['validation']) && is_array($data['validation'])) {
            $this->updateValidationMainData($data['validation']);

            if (isset($data['validation']['validation']) && is_array($data['validation']['validation'])) {
                $this->updateValidationValidData($data['validation']['validation']);
            }
        }

        if (isset($data['connection']) && is_array($data['connection'])) {
            $this->updateConnectionData($data['connection']);
        }

        if (isset($data['status'])) {
            $this->updateStatus($data['status']);
        }
    }

    // ----------------------------------------

    /**
     * @param array $infoData
     *
     * @return void
     */
    private function updateInfoData(array $infoData): void
    {
        if (array_key_exists('email', $infoData)) {
            $this->configManager->setGroupValue('/license/info/', 'email', $infoData['email']);
        }
    }

    /**
     * @param array $validationData
     *
     * @return void
     */
    private function updateValidationMainData(array $validationData): void
    {
        if (array_key_exists('domain', $validationData)) {
            $this->configManager->setGroupValue('/license/domain/', 'valid', $validationData['domain']);
        }

        if (array_key_exists('ip', $validationData)) {
            $this->configManager->setGroupValue('/license/ip/', 'valid', $validationData['ip']);
        }
    }

    /**
     * @param array $isValidData
     *
     * @return void
     */
    private function updateValidationValidData(array $isValidData): void
    {
        if (array_key_exists('domain', $isValidData)) {
            $this->configManager->setGroupValue('/license/domain/', 'is_valid', (int)$isValidData['domain']);
        }

        if (array_key_exists('ip', $isValidData)) {
            $this->configManager->setGroupValue('/license/ip/', 'is_valid', (int)$isValidData['ip']);
        }
    }

    /**
     * @param array $data
     *
     * @return void
     */
    private function updateConnectionData(array $data): void
    {
        if (array_key_exists('domain', $data)) {
            $this->configManager->setGroupValue('/license/domain/', 'real', $data['domain']);
        }

        if (array_key_exists('ip', $data)) {
            $this->configManager->setGroupValue('/license/ip/', 'real', $data['ip']);
        }
    }

    /**
     * @param $status
     *
     * @return void
     */
    private function updateStatus($status): void
    {
        $this->configManager->setGroupValue('/license/', 'status', (int)$status);
    }
}
