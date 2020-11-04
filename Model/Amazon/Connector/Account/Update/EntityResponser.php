<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Connector\Account\Update;

/**
 * Class \Ess\M2ePro\Model\Amazon\Connector\Account\Update\EntityResponser
 */
class EntityResponser extends \Ess\M2ePro\Model\Connector\Command\Pending\Responser
{
    //########################################

    protected function validateResponse()
    {
        $responseData = $this->getResponse()->getResponseData();
        if (!isset($responseData['info'])) {
            return false;
        }

        return true;
    }

    protected function processResponseData()
    {
        $responseData = $this->getPreparedResponseData();

        /** @var $amazonAccount \Ess\M2ePro\Model\Amazon\Account */
        $amazonAccount = $this->amazonFactory
            ->getObjectLoaded('Account', $this->params['account_id'])
            ->getChildObject();

        $dataForUpdate = [
            'info' => $this->getHelper('Data')->jsonEncode($responseData['info'])
        ];

        $amazonAccount->addData($dataForUpdate)->save();
    }

    //########################################
}
