<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Repricing\Synchronization;

/**
 * Class \Ess\M2ePro\Model\Amazon\Repricing\Synchronization\AbstractModel
 */
abstract class AbstractModel extends \Ess\M2ePro\Model\Amazon\Repricing\AbstractModel
{
    const MODE_GENERAL      = 'general';
    const MODE_ACTUAL_PRICE = 'actual_price';

    //########################################

    abstract public function run($skus = null);

    //########################################

    abstract protected function getMode();

    //########################################

    protected function sendRequest(array $filters = [])
    {
        $requestData = [
            'account_token' => $this->getAmazonAccountRepricing()->getToken(),
            'mode'          => $this->getMode()
        ];

        if (!empty($filters)) {
            foreach ($filters as $name => $value) {
                $filters[$name] = $this->getHelper('Data')->jsonEncode($value);
            }

            $requestData['filters'] = $filters;
        }

        try {
            $result = $this->getHelper('Component_Amazon_Repricing')->sendRequest(
                \Ess\M2ePro\Helper\Component\Amazon\Repricing::COMMAND_SYNCHRONIZE,
                $requestData
            );
        } catch (\Exception $exception) {
            $this->getSynchronizationLog()->addMessage(
                $this->getHelper('Module\Translation')->__($exception->getMessage()),
                \Ess\M2ePro\Model\Log\AbstractModel::TYPE_ERROR,
                \Ess\M2ePro\Model\Log\AbstractModel::PRIORITY_HIGH
            );

            $this->getHelper('Module\Exception')->process($exception, false);
            return false;
        }

        $this->processErrorMessages($result['response']);
        return $result['response'];
    }

    //########################################
}
