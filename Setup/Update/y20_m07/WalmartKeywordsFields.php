<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\Update\y20_m07;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

/**
 * Class  \Ess\M2ePro\Setup\Update\y20_m07\WalmartKeywordsFields
 */
class WalmartKeywordsFields extends AbstractFeature
{
    //########################################

    public function execute()
    {
        $this->getTableModifier('walmart_template_description')
            ->changeColumn('keywords_custom_value', 'TEXT', 'NULL', null, false)
            ->changeColumn('keywords_custom_attribute', 'TEXT', 'NULL', null, false)
            ->commit();
    }

    //########################################
}
