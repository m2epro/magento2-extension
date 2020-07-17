<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Wizard;

use Ess\M2ePro\Model\Wizard;

/**
 * Class \Ess\M2ePro\Model\Wizard\MigrationToInnodb
 */
class MigrationToInnodb extends Wizard
{
    protected $steps = [
        'marketplacesSynchronization'
    ];

    //########################################

    /**
     * @return string
     */
    public function getNick()
    {
        return 'migrationToInnodb';
    }

    //########################################

    public function isActive($view)
    {
        if ($view === null) {
            return true;
        }

        $collection = $this->activeRecordFactory->getObject('Marketplace')->getCollection();
        $collection->addFieldToFilter('status', \Ess\M2ePro\Model\Marketplace::STATUS_ENABLE);
        $collection->addFieldToFilter('component_mode', $view);

        foreach ($collection->getItems() as $marketplace) {
            /** @var \Ess\M2ePro\Model\Marketplace $marketplace */
            if (!$marketplace->getResource()->isDictionaryExist($marketplace)) {
                return true;
            }
        }

        return false;
    }

    //########################################

    public function rememberRefererUrl($url)
    {
        $this->getHelper('Module')->getRegistry()->setValue('/wizard/migration_to_innodb/referer_url/', $url);
    }

    public function getRefererUrl()
    {
        return $this->getHelper('Module')->getRegistry()->getValue('/wizard/migration_to_innodb/referer_url/');
    }

    public function clearRefererUrl()
    {
        $this->getHelper('Module')->getRegistry()->deleteValue('/wizard/migration_to_innodb/referer_url/');
    }

    //########################################
}
