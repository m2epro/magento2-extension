<?php

declare(strict_types=1);

namespace Ess\M2ePro\Setup\Update\y24_m02;

class DisableAmazonMarketplaceWithoutAccounts extends \Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature
{
    /**
     * @return void
     * @throws \Zend_Db_Adapter_Exception
     * @throws \Zend_Db_Statement_Exception
     */
    public function execute(): void
    {
        $marketplaceTable = $this->getFullTableName('marketplace');
        $amazonAccountTable = $this->getFullTableName('amazon_account');

        $query = $this->getConnection()
                      ->select()
                      ->from(
                          ['m' => $marketplaceTable],
                          ['id', 'status']
                      )
                      ->joinLeft(
                          ['aa' => $amazonAccountTable],
                          'aa.marketplace_id = m.id',
                          []
                      )
                      ->where('m.component_mode = ?', \Ess\M2ePro\Helper\Component\Amazon::NICK)
                      ->where('m.status = ?', \Ess\M2ePro\Model\Marketplace::STATUS_ENABLE)
                      ->where('aa.marketplace_id IS NULL')
                      ->query();

        while ($row = $query->fetch()) {
            $this->getConnection()
                 ->update(
                     $marketplaceTable,
                     ['status' => \Ess\M2ePro\Model\Marketplace::STATUS_DISABLE],
                     ['id = ?' => $row['id']]
                 );
        }
    }
}
