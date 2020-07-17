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
        $config = $this->getHelper('Module')->getConfig();

        if (array_key_exists('email', $infoData)) {
            $config->setGroupValue('/license/info/', 'email', $infoData['email']);
        }
    }

    private function updateValidationMainData(array $validationData)
    {
        $config = $this->getHelper('Module')->getConfig();

        if (array_key_exists('domain', $validationData)) {
            $config->setGroupValue('/license/domain/', 'valid', $validationData['domain']);
        }

        if (array_key_exists('ip', $validationData)) {
            $config->setGroupValue('/license/ip/', 'valid', $validationData['ip']);
        }
    }

    private function updateValidationValidData(array $isValidData)
    {
        $config = $this->getHelper('Module')->getConfig();

        if (array_key_exists('domain', $isValidData)) {
            $config->setGroupValue('/license/domain/', 'is_valid', (int)$isValidData['domain']);
        }

        if (array_key_exists('ip', $isValidData)) {
            $config->setGroupValue('/license/ip/', 'is_valid', (int)$isValidData['ip']);
        }
    }

    private function updateConnectionData(array $data)
    {
        $config = $this->getHelper('Module')->getConfig();

        if (array_key_exists('domain', $data)) {
            $config->setGroupValue('/license/domain/', 'real', $data['domain']);
        }

        if (array_key_exists('ip', $data)) {
            $config->setGroupValue('/license/ip/', 'real', $data['ip']);
        }
    }

    private function updateStatus($status)
    {
        $config = $this->getHelper('Module')->getConfig();
        $config->setGroupValue('/license/', 'status', (int)$status);
    }

    //########################################
}
