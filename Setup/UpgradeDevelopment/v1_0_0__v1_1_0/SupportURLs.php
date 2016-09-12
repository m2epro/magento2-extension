<?php

namespace Ess\M2ePro\Setup\UpgradeDevelopment\v1_0_0__v1_1_0;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

class SupportURLs extends AbstractFeature
{
    //########################################

    public function execute()
    {
        $this->getConfigModifier('module')->insert(
            '/support/', 'knowledge_ebay_url', 'https://support.m2epro.com/knowledgebase/ebay', NULL
        );
        $this->getConfigModifier('module')->insert(
            '/support/', 'knowledge_amazon_url', 'https://support.m2epro.com/knowledgebase/amazon', NULL
        );
        $this->getConfigModifier('module')->insert(
            '/support/', 'community_ebay_url', 'https://community.m2epro.com/forum/12-ebay-integration/'
        );
        $this->getConfigModifier('module')->insert(
            '/support/', 'community_amazon_url', 'https://community.m2epro.com/forum/13-amazon-integration/'
        );
        $this->getConfigModifier('module')->insert(
            '/support/', 'ideas_ebay_url', 'https://support.m2epro.com/ideas/ebay'
        );
        $this->getConfigModifier('module')->insert(
            '/support/', 'ideas_amazon_url', 'https://support.m2epro.com/ideas/amazon'
        );

        $this->getConfigModifier('module')->getEntity('/support/', 'ideas')->updateKey('ideas_base_url');
        $this->getConfigModifier('module')->getEntity('/support/', 'community')->updateKey('community_base_url');
    }

    //########################################
}