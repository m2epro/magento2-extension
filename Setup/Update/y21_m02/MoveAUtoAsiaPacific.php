<?php

namespace Ess\M2ePro\Setup\Update\y21_m02;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

/**
 * Class \Ess\M2ePro\Setup\Update\y21_m02\MoveAUtoAsiaPacific
 */
class MoveAUtoAsiaPacific extends AbstractFeature
{
    //########################################

    public function execute()
    {
        $marketplaces = $this->getConnection()->select()
            ->from($this->getFullTableName('marketplace'))
            ->where('group_title = ?', 'Australia Region')
            ->query();

        while ($row = $marketplaces->fetch()) {
            $this->getConnection()->update(
                $this->getFullTableName('marketplace'),
                ['group_title' => 'Asia / Pacific'],
                ['id = ?' => (int)$row['id']]
            );
        }
    }

    //########################################
}
