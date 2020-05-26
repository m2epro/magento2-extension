<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\Update\y19_m10;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

/**
 * Class \Ess\M2ePro\Setup\Update\y19_m10\RemoveAmazonShippingOverride
 */
class RemoveAmazonShippingOverride extends AbstractFeature
{
    //########################################

    public function execute()
    {
        $this->getTableModifier('amazon_account')
            ->dropColumn('shipping_mode');

        $amazonDictionaryShippingOverrideTableName = $this->getFullTableName('amazon_dictionary_shipping_override');

        if ($this->installer->tableExists($amazonDictionaryShippingOverrideTableName)) {
            $this->getConnection()->dropTable($amazonDictionaryShippingOverrideTableName);
        }

        $amazonTemplateShippingOverrideServiceTableName = $this->getFullTableName(
            'amazon_template_shipping_override_service'
        );

        if ($this->installer->tableExists($amazonTemplateShippingOverrideServiceTableName)) {
            $this->getConnection()->dropTable($amazonTemplateShippingOverrideServiceTableName);
        }

        $amazonTemplateShippingOverrideTableName = $this->getFullTableName('amazon_template_shipping_override');

        if ($this->installer->tableExists($amazonTemplateShippingOverrideTableName)) {
            $this->getConnection()->dropTable($amazonTemplateShippingOverrideTableName);
        }

        if ($this->installer->tableExists($this->getFullTableName('amazon_template_shipping_template')) &&
            !$this->installer->tableExists($this->getFullTableName('amazon_template_shipping'))
        ) {
            $this->renameTable(
                'amazon_template_shipping_template',
                'amazon_template_shipping'
            );
        }

        $this->getTableModifier('amazon_listing_product')
            ->dropColumn('template_shipping_override_id', true, false)
            ->renameColumn('template_shipping_template_id', 'template_shipping_id', true, false)
            ->commit();
    }

    //########################################
}
