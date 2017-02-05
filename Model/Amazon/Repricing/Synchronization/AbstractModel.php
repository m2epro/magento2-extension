<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Repricing\Synchronization;

abstract class AbstractModel extends \Ess\M2ePro\Model\Amazon\Repricing\AbstractModel
{
    const MODE_GENERAL      = 'general';
    const MODE_ACTUAL_PRICE = 'actual_price';

    //########################################

    abstract public function run($skus = NULL);

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
            $result = $this->getHelper('Component\Amazon\Repricing')->sendRequest(
                \Ess\M2ePro\Helper\Component\Amazon\Repricing::COMMAND_SYNCHRONIZE,
                $requestData
            );
        } catch (\Exception $exception) {
            $this->getHelper('Module\Exception')->process($exception);
            return false;
        }

        return $this->getHelper('Data')->jsonDecode($result['response']);
    }

    //########################################
}