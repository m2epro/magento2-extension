<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Servicing\Task;

/**
 * Class \Ess\M2ePro\Model\Servicing\Task\License
 */
class License extends \Ess\M2ePro\Model\Servicing\Task
{
    //########################################

    /**
     * @return string
     */
    public function getPublicNick()
    {
        return 'license';
    }

    //########################################

    /**
     * @return array
     */
    public function getRequestData()
    {
        return [];
    }

    public function processResponseData(array $data)
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

    //########################################

    private function updateInfoData(array $infoData)
    {
        $primaryConfig = $this->getHelper('Primary')->getConfig();

        if (array_key_exists('email', $infoData)) {
            $primaryConfig->setGroupValue('/license/info/', 'email', $infoData['email']);
        }
    }

    private function updateValidationMainData(array $validationData)
    {
        $primaryConfig = $this->getHelper('Primary')->getConfig();

        if (array_key_exists('domain', $validationData)) {
            $primaryConfig->setGroupValue('/license/', 'domain', $validationData['domain']);
        }

        if (array_key_exists('ip', $validationData)) {
            $primaryConfig->setGroupValue('/license/', 'ip', $validationData['ip']);
        }
    }

    private function updateValidationValidData(array $isValidData)
    {
        $primaryConfig = $this->getHelper('Primary')->getConfig();

        if (array_key_exists('domain', $isValidData)) {
            $primaryConfig->setGroupValue('/license/valid/', 'domain', (int)$isValidData['domain']);
        }

        if (array_key_exists('ip', $isValidData)) {
            $primaryConfig->setGroupValue('/license/valid/', 'ip', (int)$isValidData['ip']);
        }
    }

    private function updateConnectionData(array $data)
    {
        $cacheConfig = $this->cacheConfig;

        if (array_key_exists('domain', $data)) {
            $cacheConfig->setGroupValue('/license/connection/', 'domain', $data['domain']);
        }

        if (array_key_exists('ip', $data)) {
            $cacheConfig->setGroupValue('/license/connection/', 'ip', $data['ip']);
        }

        if (array_key_exists('directory', $data)) {
            $cacheConfig->setGroupValue('/license/connection/', 'directory', $data['directory']);
        }
    }

    private function updateStatus($status)
    {
        $this->getHelper('Primary')->getConfig()->setGroupValue('/license/', 'status', (int)$status);
    }

    //########################################
}
