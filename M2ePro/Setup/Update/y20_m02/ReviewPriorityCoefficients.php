<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */
// @codingStandardsIgnoreFile

namespace Ess\M2ePro\Setup\Update\y20_m02;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

/**
 * Class \Ess\M2ePro\Setup\Update\y20_m02\ReviewPriorityCoefficients
 */
class ReviewPriorityCoefficients extends AbstractFeature
{
    //########################################

    public function execute()
    {
        $this->getConnection()->delete($this->getFullTableName('module_config'), [
            '`key` IN (?)' => ['wait_increase_coefficient', 'priority_coefficient']
        ]);
    }
    //########################################
}