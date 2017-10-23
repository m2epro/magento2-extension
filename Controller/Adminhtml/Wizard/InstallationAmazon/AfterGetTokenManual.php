<?php

namespace Ess\M2ePro\Controller\Adminhtml\Wizard\InstallationAmazon;

class AfterGetTokenManual extends AfterGetTokenAbstract
{
    //########################################

    protected function getAccountData()
    {
        $params = $this->getRequest()->getParams();

        if (empty($params)) {
            return $this->indexAction();
        }

        $requiredFields = array(
            'merchant_id',
            'marketplace_id',
            'token',
        );

        foreach ($requiredFields as $requiredField) {
            if (!isset($params[$requiredField])) {
                // M2ePro_TRANSLATIONS
                // The Amazon token obtaining is currently unavailable.
                $message = $this->__('The Amazon token obtaining is currently unavailable.');
                throw new \Exception($message);
            }
        }

        return array_merge(
            array(
                'title'          => $params['merchant_id'],
                'marketplace_id' => $params['marketplace_id'],
                'merchant_id'    => $params['merchant_id'],
                'token'          => $params['token'],
            ),
            $this->getAmazonAccountDefaultSettings()
        );
    }

    //########################################
}