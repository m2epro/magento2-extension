<?php

namespace Ess\M2ePro\Setup\Update\y22_m09;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

class AddIsCriticalErrorReceivedFlag extends AbstractFeature
{
    /**
     * @return void
     * @throws \Ess\M2ePro\Model\Exception\Setup
     */
    public function execute()
    {
        $this->getTableModifier('ebay_feedback')
             ->addColumn(
                 'is_critical_error_received',
                 'SMALLINT UNSIGNED NOT NULL',
                 0,
                 'last_response_attempt_date',
                 false,
                 false
             )
             ->commit();
    }
}
