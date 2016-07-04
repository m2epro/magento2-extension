<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Repricing;

use Ess\M2ePro\Model\Account;
use Ess\M2ePro\Model\Exception\Logic;
use Ess\M2ePro\Model\Amazon\Account\Repricing as AccountRepricing;

abstract class AbstractModel extends \Ess\M2ePro\Model\AbstractModel
{
    /** @var Account $account */
    private $account = NULL;

    protected $activeRecordFactory;
    protected $resourceConnection;

    //########################################

    public function __construct(
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Magento\Framework\App\ResourceConnection $resourceConnection
    )
    {
        parent::__construct($helperFactory, $modelFactory);
        $this->activeRecordFactory = $activeRecordFactory;
        $this->resourceConnection = $resourceConnection;
    }

    //########################################

    public function setAccount(Account $account)
    {
        $this->account = $account;
        return $this;
    }

    /**
     * @return Account
     */
    protected function getAccount()
    {
        return $this->account;
    }

    /**
     * @return \Ess\M2ePro\Model\Amazon\Account
     * @throws Logic
     */
    protected function getAmazonAccount()
    {
        return $this->getAccount()->getChildObject();
    }

    /**
     * @return AccountRepricing
     */
    protected function getAmazonAccountRepricing()
    {
        return $this->getAmazonAccount()->getRepricing();
    }

    //########################################
}