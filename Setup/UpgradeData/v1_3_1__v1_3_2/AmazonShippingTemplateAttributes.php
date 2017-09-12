<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2016 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\UpgradeData\v1_3_1__v1_3_2;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

class AmazonShippingTemplateAttributes extends AbstractFeature
{
    //########################################

    public function getBackupTables()
    {
        return ['amazon_template_shipping_template'];
    }

    public function execute()
    {
        if ($this->getTableModifier('amazon_template_shipping_template')->isColumnExists('template_name')) {

            $this->getTableModifier('amazon_template_shipping_template')
                ->dropIndex('template_name', false)
                ->renameColumn(
                    'template_name', 'template_name_value', false, false
                )
                ->addColumn(
                    'template_name_mode', 'SMALLINT(5) UNSIGNED NOT NULL', '0', 'title', false, false
                )
                ->addColumn(
                    'template_name_attribute', 'VARCHAR(255) NOT NULL', NULL, 'template_name_value', false, false
                )
                ->commit();

            $this->getConnection()->update(
                $this->getFullTableName('amazon_template_shipping_template'),
                ['template_name_mode' => 1]
            );
        }
    }

    //########################################
}