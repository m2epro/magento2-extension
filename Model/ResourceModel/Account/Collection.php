<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel\Account;

class Collection extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Collection\Component\Parent\AbstractModel
{
    public function _construct()
    {
        parent::_construct();
        $this->_init(
            \Ess\M2ePro\Model\Account::class,
            \Ess\M2ePro\Model\ResourceModel\Account::class
        );
    }

    /**
     * @return \Ess\M2ePro\Model\Account[]
     * @throws \Ess\M2ePro\Model\Exception\Logic
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getAccountsWithValidRepricingAccount(): array
    {
        $amazonRepricingAccountResource = $this->activeRecordFactory
            ->getObject('Amazon_Account_Repricing')
            ->getResource();

        $this->getSelect()->joinInner(
            ['aar' => $amazonRepricingAccountResource->getMainTable()],
            'aar.account_id = main_table.id',
            []
        );

        $this->getSelect()->where('invalid = 0');

        return $this->getAll();
    }

    /**
     * @return \Ess\M2ePro\Model\Account[]
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getItemsWithEnablesOtherListingsSynch(): array
    {
        if ($this->childMode === null) {
            throw new \Ess\M2ePro\Model\Exception\Logic('Child mode must be set');
        }

        $this->addFieldToFilter('other_listings_synchronization', 1);

        return $this->getAll();
    }

    /**
     * @return \Ess\M2ePro\Model\Account[]
     */
    public function getAll(): array
    {
        return $this->getItems();
    }
}
