<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\Update\y20_m07;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

/**
 * Class \Ess\M2ePro\Setup\Update\y20_m07\EbayTemplateStoreCategory
 */
class EbayTemplateStoreCategory extends AbstractFeature
{
    //########################################

    public function execute()
    {
        $this->getTableModifier('ebay_template_store_category')
            ->changeColumn('category_id', 'DECIMAL(20, 0) UNSIGNED NOT NULL', null, 'account_id');
    }

    //########################################
}
