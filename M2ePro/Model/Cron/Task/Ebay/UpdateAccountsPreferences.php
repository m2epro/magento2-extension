<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Cron\Task\Ebay;

use Ess\M2ePro\Helper\Component\Ebay;

/**
 * Class \Ess\M2ePro\Model\Cron\Task\Ebay\UpdateAccountsPreferences
 */
class UpdateAccountsPreferences extends \Ess\M2ePro\Model\Cron\Task\AbstractModel
{
    const NICK = 'ebay/update_accounts_preferences';

    /**
     * @var int (in seconds)
     */
    protected $interval = 86400;

    //########################################

    public function performActions()
    {
        $accountCollection = $this->parentFactory->getObject(Ebay::NICK, 'Account')->getCollection();

        /** @var \Ess\M2ePro\Model\Account[] $accounts */
        $accounts = $accountCollection->getItems();

        foreach ($accounts as $account) {
            $account->getChildObject()->updateUserPreferences();
        }
    }

    //########################################
}
