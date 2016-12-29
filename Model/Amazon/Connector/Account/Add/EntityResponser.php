<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Connector\Account\Add;

class EntityResponser extends \Ess\M2ePro\Model\Amazon\Connector\Command\Pending\Responser
{
    //########################################

    protected function validateResponse()
    {
        $responseData = $this->getResponse()->getResponseData();
        if (empty($responseData['hash']) || !isset($responseData['info'])) {
            return false;
        }

        return true;
    }

    protected function processResponseData()
    {
        $responseData = $this->getPreparedResponseData();

        /** @var $amazonAccount \Ess\M2ePro\Model\Amazon\Account */
        $amazonAccount = $this->getAccount()->getChildObject();

        $dataForUpdate = array(
            'server_hash' => $responseData['hash'],
            'info'        => $this->getHelper('Data')->jsonEncode($responseData['info'])
        );

        $amazonAccount->addData($dataForUpdate)->save();
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Account
     */
    protected function getAccount()
    {
        return $this->getObjectByParam('Account','account_id');
    }

    //########################################
}