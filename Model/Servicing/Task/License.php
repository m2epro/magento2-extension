<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Servicing\Task;

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
        return array();
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
        $moduleName = $this->getHelper('Module')->getName();
        $primaryConfig = $this->getHelper('Primary')->getConfig();

        if (array_key_exists('email', $infoData)) {
            $primaryConfig->setGroupValue('/'.$moduleName.'/license/info/','email', $infoData['email']);
        }
    }

    private function updateValidationMainData(array $validationData)
    {
        $moduleName = $this->getHelper('Module')->getName();
        $primaryConfig = $this->getHelper('Primary')->getConfig();

        if (array_key_exists('domain', $validationData)) {
            $primaryConfig->setGroupValue('/'.$moduleName.'/license/','domain', $validationData['domain']);
        }

        if (array_key_exists('ip', $validationData)) {
            $primaryConfig->setGroupValue('/'.$moduleName.'/license/','ip', $validationData['ip']);
        }
    }

    private function updateValidationValidData(array $isValidData)
    {
        $moduleName = $this->getHelper('Module')->getName();
        $primaryConfig = $this->getHelper('Primary')->getConfig();

        if (array_key_exists('domain', $isValidData)) {
            $primaryConfig->setGroupValue('/'.$moduleName.'/license/valid/','domain',(int)$isValidData['domain']);
        }

        if (array_key_exists('ip', $isValidData)) {
            $primaryConfig->setGroupValue('/'.$moduleName.'/license/valid/','ip',(int)$isValidData['ip']);
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
        $moduleName = $this->getHelper('Module')->getName();
        $primaryConfig = $this->getHelper('Primary')->getConfig();

        $primaryConfig->setGroupValue('/'.$moduleName.'/license/','status',(int)$status);
    }

    //########################################
}