<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\Update\y20_m10;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

/**
 * Class Ess\M2ePro\Setup\Update\y20_m10\AddInvoiceAndShipment
 */
class AddInvoiceAndShipment extends AbstractFeature
{
    //########################################

    public function execute()
    {
        $this->addColumnToEbayAccount();
        $this->addColumnToWalmartAccount();
    }

    //########################################

    public function addColumnToEbayAccount()
    {
        if (!$this->getTableModifier('ebay_account')->isColumnExists('create_magento_invoice') &&
            !$this->getTableModifier('ebay_account')->isColumnExists('create_magento_shipment')) {

            $this->getTableModifier('ebay_account')
                ->addColumn(
                    'create_magento_invoice',
                    'SMALLINT(5) UNSIGNED NOT NULL',
                    1,
                    'magento_orders_settings',
                    false,
                    false
                )
                ->addColumn(
                    'create_magento_shipment',
                    'SMALLINT(5) UNSIGNED NOT NULL',
                    1,
                    'create_magento_invoice',
                    false,
                    false
                )
                ->commit();

            $dataHelper = $this->helperFactory->getObject('Data');

            $ebayAccountTable = $this->getFullTableName('ebay_account');

            $query = $this->getConnection()
                ->select()
                ->from($ebayAccountTable)
                ->query();

            while ($row = $query->fetch()) {
                $magentoOrdersSettings = $dataHelper->jsonDecode($row['magento_orders_settings']);

                $data = [
                    'create_magento_invoice' => empty($magentoOrdersSettings['invoice_mode']) ?
                        0 : $magentoOrdersSettings['invoice_mode'],
                    'create_magento_shipment'              => empty($magentoOrdersSettings['shipment_mode']) ?
                        0 : $magentoOrdersSettings['shipment_mode']
                ];

                // clearing old data
                unset($magentoOrdersSettings['invoice_mode']);
                unset($magentoOrdersSettings['shipment_mode']);
                $data['magento_orders_settings'] = $dataHelper->jsonEncode($magentoOrdersSettings);

                $this->getConnection()->update(
                    $ebayAccountTable,
                    $data,
                    ['account_id = ?' => (int)$row['account_id']]
                );
            }
        }
    }

    public function addColumnToWalmartAccount()
    {
        if (!$this->getTableModifier('walmart_account')->isColumnExists('create_magento_invoice') &&
            !$this->getTableModifier('walmart_account')->isColumnExists('create_magento_shipment')) {

            $this->getTableModifier('walmart_account')
                ->addColumn(
                    'create_magento_invoice',
                    'SMALLINT(5) UNSIGNED NOT NULL',
                    1,
                    'magento_orders_settings',
                    false,
                    false
                )
                ->addColumn(
                    'create_magento_shipment',
                    'SMALLINT(5) UNSIGNED NOT NULL',
                    1,
                    'create_magento_invoice',
                    false,
                    false
                )
                ->commit();

            $dataHelper = $this->helperFactory->getObject('Data');

            $walmartAccountTable = $this->getFullTableName('walmart_account');

            $query = $this->getConnection()
                ->select()
                ->from($walmartAccountTable)
                ->query();

            while ($row = $query->fetch()) {
                $magentoOrdersSettings = $dataHelper->jsonDecode($row['magento_orders_settings']);

                $data = [
                    'create_magento_invoice' => empty($magentoOrdersSettings['invoice_mode']) ?
                        0 : $magentoOrdersSettings['invoice_mode'],
                    'create_magento_shipment'              => empty($magentoOrdersSettings['shipment_mode']) ?
                        0 : $magentoOrdersSettings['shipment_mode']
                ];

                // clearing old data
                unset($magentoOrdersSettings['invoice_mode']);
                unset($magentoOrdersSettings['shipment_mode']);
                $data['magento_orders_settings'] = $dataHelper->jsonEncode($magentoOrdersSettings);

                $this->getConnection()->update(
                    $walmartAccountTable,
                    $data,
                    ['account_id = ?' => (int)$row['account_id']]
                );
            }
        }
    }

    //########################################
}
