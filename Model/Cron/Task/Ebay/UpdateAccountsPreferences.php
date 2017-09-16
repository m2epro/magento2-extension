<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Cron\Task\Ebay;

class UpdateAccountsPreferences extends \Ess\M2ePro\Model\Cron\Task\AbstractModel
{
    const NICK = 'ebay/update_accounts_preferences';
    const MAX_MEMORY_LIMIT = 128;

    //########################################

    protected function getNick()
    {
        return self::NICK;
    }

    protected function getMaxMemoryLimit()
    {
        return self::MAX_MEMORY_LIMIT;
    }

    //########################################

    public function performActions()
    {
        $accountCollection = $this->parentFactory->getObject(
            \Ess\M2ePro\Helper\Component\Ebay::NICK, 'Account'
        )->getCollection();

        /** @var \Ess\M2ePro\Model\Account[] $accounts */
        $accounts = $accountCollection->getItems();

        foreach ($accounts as $account) {
            $account->getChildObject()->updateUserPreferences();
        }
    }

    //########################################
}