<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\Update\y22_m10;

class RemoveWalmartLegacySettings extends \Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature
{
    /**
     * @return void
     * @throws \Ess\M2ePro\Model\Exception\Setup
     */
    public function execute(): void
    {
        $this->removeMinimumAdvertisedPrice();
        $this->removeTaxCodes();
        $this->removeKeywords();
    }

    /**
     * @return void
     * @throws \Ess\M2ePro\Model\Exception\Setup
     */
    private function removeMinimumAdvertisedPrice(): void
    {
        $this->getTableModifier('walmart_template_selling_format')
             ->dropColumn('map_price_mode', true, false)
             ->dropColumn('map_price_custom_attribute', true, false)
             ->commit();
    }

    /**
     * @return void
     * @throws \Ess\M2ePro\Model\Exception\Setup
     */
    private function removeTaxCodes(): void
    {
        $this->getTableModifier('m2epro_walmart_dictionary_marketplace')
             ->dropColumn('tax_codes', true, false)
             ->commit();

        $this->getTableModifier('walmart_template_selling_format')
             ->dropColumn('product_tax_code_mode', true, false)
             ->dropColumn('product_tax_code_custom_value', true, false)
             ->dropColumn('product_tax_code_custom_attribute', true, false)
             ->commit();
    }

    /**
     * @return void
     * @throws \Ess\M2ePro\Model\Exception\Setup
     */
    private function removeKeywords(): void
    {
        $this->getTableModifier('walmart_template_description')
             ->dropColumn('keywords_mode', true, false)
             ->dropColumn('keywords_custom_value', true, false)
             ->dropColumn('keywords_custom_attribute', true, false)
             ->commit();
    }
}
