<?php

namespace Ess\M2ePro\Setup\Update\y22_m07;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

class ClearPolicyLinkingToDeletedAccount extends AbstractFeature
{
    /** @var array */
    private $accountIds = [];

    /**
     * @return void
     * @throws \Zend_Db_Adapter_Exception
     * @throws \Zend_Db_Statement_Exception
     */
    public function execute(): void
    {
        $query = $this->installer->getConnection()
            ->select()
            ->from($this->getFullTableName('ebay_account'), 'account_id')
            ->query();
        while ($row = $query->fetch()) {
            $this->accountIds[] = $row['account_id'];
        }

        $query = $this->installer->getConnection()
            ->select()
            ->from(
                $this->getFullTableName('ebay_template_shipping'),
                ['id', 'local_shipping_rate_table', 'international_shipping_rate_table']
            )
            ->where('local_shipping_rate_table LIKE ?', "%\":%")
            ->orWhere('international_shipping_rate_table LIKE ?', "%\":%")
            ->query();

        while ($row = $query->fetch()) {
            $newLocalShippingRateTable = $this->clearDeletedAccountIds(
                $row['local_shipping_rate_table']
            );
            $newInternationalShippingRateTable = $this->clearDeletedAccountIds(
                $row['international_shipping_rate_table']
            );

            if (
                $newLocalShippingRateTable !== $row['local_shipping_rate_table']
                || $newInternationalShippingRateTable !== $row['international_shipping_rate_table']
            ) {
                $this->getConnection()->update(
                    $this->getFullTableName('ebay_template_shipping'),
                    [
                        'local_shipping_rate_table' => $newLocalShippingRateTable,
                        'international_shipping_rate_table' => $newInternationalShippingRateTable
                    ],
                    ['id = ?' => $row['id']]
                );
            }
        }
    }

    /**
     * @param string|null $input
     *
     * @return string|null
     */
    private function clearDeletedAccountIds(?string $input): ?string
    {
        if ($input === null) {
            return null;
        }

        $data = json_decode($input, true);
        foreach ($data as $key => $value) {
            if (!in_array($key, $this->accountIds)) {
                unset($data[$key]);
            }
        }

        return json_encode($data);
    }
}
