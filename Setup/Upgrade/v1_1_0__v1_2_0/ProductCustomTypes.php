<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\Upgrade\v1_1_0__v1_2_0;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

class ProductCustomTypes extends AbstractFeature
{
    //########################################

    public function getBackupTables()
    {
        return ['module_config'];
    }

    public function execute()
    {
        $this->getConfigModifier('module')->insert(
            '/magento/product/simple_type/', 'custom_types', '', 'Magento product custom types'
        );
        $this->getConfigModifier('module')->insert(
            '/magento/product/configurable_type/', 'custom_types', '', 'Magento product custom types'
        );
        $this->getConfigModifier('module')->insert(
            '/magento/product/bundle_type/', 'custom_types', '', 'Magento product custom types'
        );
        $this->getConfigModifier('module')->insert(
            '/magento/product/grouped_type/', 'custom_types', '', 'Magento product custom types'
        );
    }

    //########################################
}