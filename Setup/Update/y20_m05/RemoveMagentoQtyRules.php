<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\Update\y20_m05;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

/**
 * Class \Ess\M2ePro\Setup\Update\y20_m05\RemoveMagentoQtyRules
 */
class RemoveMagentoQtyRules extends AbstractFeature
{
    //########################################

    public function execute()
    {
        foreach (['ebay', 'amazon', 'walmart'] as $component) {
            $tableModifier = $this->getTableModifier("{$component}_template_synchronization");

            foreach (['list', 'relist', 'stop'] as $action) {
                if (!$tableModifier->isColumnExists("{$action}_qty_magento")) {
                    continue;
                }

                $this->getConnection()
                    ->update(
                        $tableModifier->getTableName(),
                        [
                            "{$action}_qty_calculated"       => new \Zend_Db_Expr("{$action}_qty_magento"),
                            "{$action}_qty_calculated_value" => new \Zend_Db_Expr("{$action}_qty_magento_value"),
                        ],
                        "{$action}_qty_calculated = 0 AND {$action}_qty_magento <> 0"
                    );

                $this->getConnection()
                    ->update(
                        $tableModifier->getTableName(),
                        ["{$action}_qty_calculated" => '1'],
                        "{$action}_qty_calculated <> 0"
                    );

                $tableModifier
                    ->dropColumn("{$action}_qty_magento", true, false)
                    ->dropColumn("{$action}_qty_magento_value", true, false)
                    ->dropColumn("{$action}_qty_magento_value_max", true, false)
                    ->dropColumn("{$action}_qty_calculated_value_max", true, false)
                    ->commit();
            }
        }
    }

    //########################################
}
