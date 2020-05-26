<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */
// @codingStandardsIgnoreFile

namespace Ess\M2ePro\Setup\Update\y20_m03;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

/**
 * Class \Ess\M2ePro\Setup\Update\y20_m03\AmazonSendInvoice
 */
class AmazonSendInvoice extends AbstractFeature
{
    //########################################

    public function execute()
    {
        $this->getTableModifier('amazon_account')
            ->renameColumn(
                'is_vat_calculation_service_enabled',
                'auto_invoicing',
                true,
                false
            )
            ->commit();

        $this->getTableModifier('amazon_order')
            ->addColumn('is_invoice_sent', 'smallint(5) UNSIGNED NOT NULL', '0', 'status', true, false)
            ->addColumn('is_credit_memo_sent', 'smallint(5) UNSIGNED NOT NULL', '0', 'is_invoice_sent', true, false)
            ->commit();

        $this->getTableModifier('amazon_marketplace')
            ->addColumn(
                'is_upload_invoices_available',
                'smallint(5) UNSIGNED NOT NULL',
                '0',
                'is_automatic_token_retrieving_available',
                true,
                false
            )
            ->commit();

        $this->getConnection()->update(
            $this->getFullTableName('amazon_marketplace'),
            ['is_upload_invoices_available' => 1],
            ['marketplace_id IN (?)' => [25, 26, 28, 30, 31]]
        );
    }

    //########################################
}