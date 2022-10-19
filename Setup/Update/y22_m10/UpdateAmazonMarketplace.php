<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\Update\y22_m10;

class UpdateAmazonMarketplace extends \Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature
{
    /**
     * @return void
     * @throws \Zend_Db_Adapter_Exception
     */
    public function execute(): void
    {
        foreach (['NL', 'BE'] as $countryCode) {
            $marketplaceId = $this->getConnection()
                                  ->select()
                                  ->from($this->getFullTableName('marketplace'))
                                  ->where('`code` = ?', $countryCode)
                                  ->where('`component_mode` = ?', 'amazon')
                                  ->query()
                                  ->fetchColumn();

            $this->getConnection()->update(
                $this->getFullTableName('amazon_marketplace'),
                [
                    'is_vat_calculation_service_available' => 1,
                ],
                [
                    '`marketplace_id` = ?' => $marketplaceId,
                ]
            );
        }
    }
}
