<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\Update\y23_m02;

class AddErrorCodeColumnForTags extends \Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature
{
    /**
     * @return void
     * @throws \Ess\M2ePro\Model\Exception\Setup
     * @throws \Zend_Db_Adapter_Exception
     */
    public function execute()
    {
        $modifier = $this->getTableModifier('tag');

        if ($modifier->isColumnExists('error_code')) {
            return;
        }

        $modifier->addColumn(
            'error_code',
            'VARCHAR(100)',
            'NULL',
            'nick',
            false,
            false
        );
        $modifier->commit();

        $this->getConnection()->update(
            $this->getFullTableName('tag'),
            ['error_code' => '21919303'],
            ['nick = (?)' => 'missing_item_specific']
        );
    }
}
