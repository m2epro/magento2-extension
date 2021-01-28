<?php

namespace Ess\M2ePro\Setup\Update\y20_m11;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

/**
 * Class Ess\M2ePro\Setup\Update\y20_m11\EbayRemoveCustomTemplates
 */
class EbayRemoveCustomTemplates extends AbstractFeature
{
    //########################################

    /**
     * @throws \Exception
     */
    public function execute()
    {
        if ($this->getTableModifier('ebay_listing')->isColumnExists('template_payment_mode')) {
            $listingTable = $this->getFullTableName('listing');
            $ebayListingTable = $this->getFullTableName('ebay_listing');
            $ebayTemplatePaymentTable = $this->getFullTableName('ebay_template_payment');
            $ebayTemplateShippingTable = $this->getFullTableName('ebay_template_shipping');
            $ebayTemplateReturnPolicyTable = $this->getFullTableName('ebay_template_return_policy');
            $templateDescriptionTable = $this->getFullTableName('template_description');
            $ebayTemplateDescriptionTable = $this->getFullTableName('ebay_template_description');
            $templateSellingFormatTable = $this->getFullTableName('template_selling_format');
            $ebayTemplateSellingFormatTable = $this->getFullTableName('ebay_template_selling_format');
            $templateSynchronizationTable = $this->getFullTableName('template_synchronization');
            $ebayTemplateSynchronizationTable = $this->getFullTableName('ebay_template_synchronization');

            $query = $this->getConnection()
                ->select()
                ->from($listingTable, ['id', 'title'])
                ->joinLeft(
                    $ebayListingTable,
                    'id = listing_id',
                    [
                        'template_payment_mode',
                        'template_payment_id',
                        'template_shipping_mode',
                        'template_shipping_id',
                        'template_return_policy_mode',
                        'template_return_policy_id',
                        'template_description_mode',
                        'template_description_id',
                        'template_selling_format_mode',
                        'template_selling_format_id',
                        'template_synchronization_mode',
                        'template_synchronization_id',
                    ]
                )
                ->where('component_mode = ?', 'ebay')
                ->query();

            while ($row = $query->fetch()) {
                if ($row['template_payment_mode'] == 1) {
                    $this->switchTemplateToNotCustom($ebayTemplatePaymentTable, $row['template_payment_id']);
                    $this->setTemplateTitle($ebayTemplatePaymentTable, $row['template_payment_id'], $row['title']);
                }

                if ($row['template_shipping_mode'] == 1) {
                    $this->switchTemplateToNotCustom($ebayTemplateShippingTable, $row['template_shipping_id']);
                    $this->setTemplateTitle($ebayTemplateShippingTable, $row['template_shipping_id'], $row['title']);
                }

                if ($row['template_return_policy_mode'] == 1) {
                    $this->switchTemplateToNotCustom($ebayTemplateReturnPolicyTable, $row['template_return_policy_id']);
                    $this->setTemplateTitle(
                        $ebayTemplateReturnPolicyTable,
                        $row['template_return_policy_id'],
                        $row['title']
                    );
                }

                if ($row['template_description_mode'] == 1) {
                    $this->switchTemplateToNotCustom(
                        $ebayTemplateDescriptionTable,
                        $row['template_description_id'],
                        'template_description_id'
                    );
                    $this->setTemplateTitle($templateDescriptionTable, $row['template_description_id'], $row['title']);
                }

                if ($row['template_selling_format_mode'] == 1) {
                    $this->switchTemplateToNotCustom(
                        $ebayTemplateSellingFormatTable,
                        $row['template_selling_format_id'],
                        'template_selling_format_id'
                    );
                    $this->setTemplateTitle(
                        $templateSellingFormatTable,
                        $row['template_selling_format_id'],
                        $row['title']
                    );
                }

                if ($row['template_synchronization_mode'] == 1) {
                    $this->switchTemplateToNotCustom(
                        $ebayTemplateSynchronizationTable,
                        $row['template_synchronization_id'],
                        'template_synchronization_id'
                    );
                    $this->setTemplateTitle(
                        $templateSynchronizationTable,
                        $row['template_synchronization_id'],
                        $row['title']
                    );
                }
            }

            $this->getTableModifier('ebay_listing')
                ->dropColumn('template_payment_mode', true, false)
                ->dropColumn('template_shipping_mode', true, false)
                ->dropColumn('template_return_policy_mode', true, false)
                ->dropColumn('template_description_mode', true, false)
                ->dropColumn('template_selling_format_mode', true, false)
                ->dropColumn('template_synchronization_mode', true, false)
                ->commit();
        }
    }

    //########################################

    protected function switchTemplateToNotCustom($table, $templateId, $idField = 'id')
    {
        $this->getConnection()->update(
            $table,
            [
                'is_custom_template' => 0
            ],
            [
                $idField . ' = ?'        => (int)$templateId,
                'is_custom_template = ?' => 1
            ]
        );
    }

    protected function setTemplateTitle($table, $templateId, $title)
    {
        $this->getConnection()->update(
            $table,
            [
                'title' => $title
            ],
            [
                'id = ?'    => (int)$templateId,
                'title = ?' => ''
            ]
        );
    }

    //########################################
}
