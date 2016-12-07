<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Setup;

use Ess\M2ePro\Model\AbstractModel;

class PublicVersionsChecker extends AbstractModel
{
    /** @var \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory */
    protected $activeRecordFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory
    ) {
        parent::__construct($helperFactory, $modelFactory);

        $this->activeRecordFactory = $activeRecordFactory;
    }

    //########################################

    public function doCheck()
    {
        $cache = $this->getHelper('Data\Cache\Permanent');

        if ($cache->getValue('files_version_is_checked')) {
            return;
        }

        $currentVersion = $this->getHelper('Module')->getPublicVersion();
        $lastVersion = $this->activeRecordFactory->getObject('VersionsHistory')
            ->getResource()
            ->getLastItem()
            ->getVersionTo();

        if ($currentVersion != $lastVersion) {

            $historyObject = $this->activeRecordFactory->getObject('VersionsHistory');
            $historyObject->setData([
                'version_from' => $lastVersion,
                'version_to'   => $currentVersion,
            ]);
            $historyObject->save();
        }

        $cache->setValue('files_version_is_checked', 'true');
    }

    //########################################
}
