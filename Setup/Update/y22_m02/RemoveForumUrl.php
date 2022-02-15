<?php

namespace Ess\M2ePro\Setup\Update\y22_m02;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

/**
 * Class \Ess\M2ePro\Setup\Update\y22_m02\RemoveForumUrl
 */
class RemoveForumUrl extends AbstractFeature
{
    //########################################

    public function execute()
    {
        $moduleConfig = $this->getConfigModifier('module');

        $moduleConfig->delete('/support/', 'forum_url');
    }

    //########################################
}
