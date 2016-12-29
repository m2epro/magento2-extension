<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Connector\Orders\Get;

class ItemsRequester extends \Ess\M2ePro\Model\Amazon\Connector\Command\Pending\Requester
{
    // ########################################

    public function getCommand()
    {
        return array('orders','get','items');
    }

    // ########################################

    protected function getResponserParams()
    {
        return array(
            'accounts_access_tokens' => $this->getAccountsAccessTokens()
        );
    }

    // ########################################

    protected function getRequestData()
    {
        $data = array(
            'accounts' => $this->getAccountsAccessTokens(),
            'from_update_date' => $this->params['from_update_date'],
            'to_update_date' => $this->params['to_update_date']
        );

        if (!empty($this->params['job_token'])) {
            $data['job_token'] = $this->params['job_token'];
        }

        return $data;
    }

    //-----------------------------------------

    private function getAccountsAccessTokens()
    {
        $accountsAccessTokens = array();
        foreach ($this->params['accounts'] as $account) {
            $accountsAccessTokens[] = $account->getChildObject()->getServerHash();
        }

        return $accountsAccessTokens;
    }

    // ########################################
}