<?php

namespace Ess\M2ePro\Setup\UpgradeData\v1_0_0__v1_1_0;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

class SupportURLs extends AbstractFeature
{
    //########################################

    public function getBackupTables()
    {
        return ['module_config'];
    }

    public function execute()
    {
        $this->getConfigModifier('module')->getEntity('/support/', 'community')->updateKey('forum_url');
        $this->getConfigModifier('module')->getEntity('/support/', 'main_website_url')->updateKey('website_url');
        $this->getConfigModifier('module')->getEntity('/support/', 'main_support_url')->updateKey('support_url');
        $this->getConfigModifier('module')->getEntity('/support/', 'documentation_url')->updateValue(
            'https://docs.m2epro.com/'
        );
        $this->getConfigModifier('module')->getEntity('/support/', 'knowledge_base_url')->delete();
        $this->getConfigModifier('module')->getEntity('/support/', 'ideas')->delete();
    }

    //########################################
}