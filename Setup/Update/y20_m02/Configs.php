<?php

namespace Ess\M2ePro\Setup\Update\y20_m02;

use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;

/**
 * Class \Ess\M2ePro\Setup\Update\y20_m02\Configs
 */
class Configs extends AbstractFeature
{
    //########################################

    public function execute()
    {
        $this->getConfigModifier('module')->delete('/view/ebay/motors_epids_attribute/');
        $this->getConfigModifier('module')->delete('/view/ebay/multi_currency_marketplace_2/');
        $this->getConfigModifier('module')->delete('/view/ebay/multi_currency_marketplace_19/');

        $this->getConfigModifier('module')
            ->getEntity('/logs/view/grouped/', 'max_last_handled_records_count')
            ->updateGroup('/logs/grouped/')
            ->updateKey('max_records_count');

        $this->getConfigModifier('module')
            ->getEntity('/support/', 'magento_marketplace_url')
            ->updateValue('https://marketplace.magento.com/m2e-ebay-amazon-magento2.html');

        $entity = $this->getConfigModifier('module')
            ->getEntity('/view/ebay/advanced/autoaction_popup/', 'shown');

        $this->getConfigModifier('cache')->insert(
            '/view/ebay/advanced/autoaction_popup/',
            'shown',
            $entity->getValue()
        );
        $entity->delete();
    }

    //########################################
}
