<?php

namespace Ess\M2ePro\Setup\Update\y23_m08;

class RemoveAmazonDescriptionPolicyRelatedData extends \Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature
{
    public function execute(): void
    {
        $this->getTableModifier('amazon_listing')
             ->dropColumn('auto_global_adding_description_template_id', true, false)
             ->dropColumn('auto_website_adding_description_template_id', true, false)
             ->dropColumn('image_main_mode', true, false)
             ->dropColumn('image_main_attribute', true, false)
             ->dropColumn('gallery_images_mode', true, false)
             ->dropColumn('gallery_images_limit', true, false)
             ->dropColumn('gallery_images_attribute', true, false)
             ->commit();

        $this->getTableModifier('amazon_listing_auto_category_group')
             ->dropColumn('adding_description_template_id');

        $this->getTableModifier('amazon_listing_product')
             ->dropColumn('online_images_data');

        $this->getTableModifier('amazon_template_synchronization')
             ->dropColumn('revise_update_images');

        $this->getConfigModifier('module')
             ->delete('/amazon/configuration/', 'product_id_override_mode');

        $this->getConfigModifier('module')
             ->delete('/amazon/listing/product/action/revise_images/', 'min_allowed_wait_interval');

        $this->getConnection()
             ->dropTable($this->getFullTableName('amazon_template_description'));

        $this->getConnection()
             ->dropTable($this->getFullTableName('amazon_template_description_specific'));

        $this->getConnection()
             ->dropTable($this->getFullTableName('amazon_template_description_definition'));
    }
}
