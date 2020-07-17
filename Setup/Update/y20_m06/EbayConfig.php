<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\Update\y20_m06;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

/**
 * Class \Ess\M2ePro\Setup\Update\y20_m06\EbayConfig
 */
class EbayConfig extends AbstractFeature
{
    //########################################

    public function execute()
    {
        $config = $this->getConfigModifier('module');

        $config->getEntity('/view/ebay/template/selling_format/', 'show_tax_category')
            ->updateGroup('/ebay/configuration/')
            ->updateKey('view_template_selling_format_show_tax_category');

        $config->getEntity('/view/ebay/feedbacks/notification/', 'mode')
            ->updateGroup('/ebay/configuration/')
            ->updateKey('feedback_notification_mode');

        $config->getEntity('/view/ebay/feedbacks/notification/', 'last_check')
            ->updateGroup('/ebay/configuration/')
            ->updateKey('feedback_notification_last_check');

        $config->getEntity('/ebay/description/', 'should_be_ulrs_secure')
            ->updateGroup('/general/configuration/')
            ->updateKey('secure_image_url_in_item_description_mode');

        $config->getEntity('/ebay/description/', 'upload_images_mode')
            ->updateGroup('/ebay/configuration/');

        $config->getEntity('/ebay/motors/', 'epids_motor_attribute')
            ->updateGroup('/ebay/configuration/')
            ->updateKey('motors_epids_attribute');

        $config->getEntity('/ebay/motors/', 'epids_uk_attribute')
            ->updateGroup('/ebay/configuration/')
            ->updateKey('uk_epids_attribute');

        $config->getEntity('/ebay/motors/', 'epids_de_attribute')
            ->updateGroup('/ebay/configuration/')
            ->updateKey('de_epids_attribute');

        $config->getEntity('/ebay/motors/', 'epids_au_attribute')
            ->updateGroup('/ebay/configuration/')
            ->updateKey('au_epids_attribute');

        $config->getEntity('/ebay/motors/', 'ktypes_attribute')
            ->updateGroup('/ebay/configuration/');

        $config->getEntity('/ebay/sell_on_another_marketplace/', 'tutorial_shown')
            ->updateGroup('/ebay/configuration/')
            ->updateKey('sell_on_another_marketplace_tutorial_shown');

        $config->getEntity('/ebay/connector/listing/', 'check_the_same_product_already_listed')
            ->updateGroup('/ebay/configuration/')
            ->updateKey('prevent_item_duplicates_mode');

        $config->getEntity('/component/ebay/variation/', 'mpn_can_be_changed')
            ->updateGroup('/ebay/configuration/')
            ->updateKey('variation_mpn_can_be_changed');
    }

    //########################################
}
