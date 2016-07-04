<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\Upgrade\Source;

use Magento\Framework\Module\Setup;

class Factory
{
    protected $objectManager;

    //########################################

    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager
    ) {
        $this->objectManager = $objectManager;
    }

    //########################################

    /**
     * @param $fromVersion
     * @param $toVersion
     * @return \Ess\M2ePro\Setup\Upgrade\Source\AbstractConfig
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getConfigObject($fromVersion, $toVersion)
    {
        $fromVersion = $this->prepareVersion($fromVersion);
        $toVersion   = $this->prepareVersion($toVersion);

        $object = $this->objectManager->create(
            '\Ess\M2ePro\Setup\Upgrade\Source\v'.$fromVersion.'__v'.$toVersion.'\Config'
        );

        if (!$object instanceof \Ess\M2ePro\Setup\Upgrade\Source\AbstractConfig) {
            throw new \Ess\M2ePro\Model\Exception\Logic(
                __('%1 doesn\'t extends \Ess\M2ePro\Setup\Upgrade\Source\AbstractConfig', get_class($object))
            );
        }

        return $object;
    }

    /**
     * @param $fromVersion
     * @param $toVersion
     * @param $featureName
     * @param Setup $installer
     * @return \Ess\M2ePro\Setup\Upgrade\Source\AbstractFeature
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getFeatureObject($fromVersion, $toVersion, $featureName, Setup $installer)
    {
        $fromVersion = $this->prepareVersion($fromVersion);
        $toVersion   = $this->prepareVersion($toVersion);

        $object = $this->objectManager->create(
            '\Ess\M2ePro\Setup\Upgrade\Source\v'.$fromVersion.'__v'.$toVersion.'\\'.$featureName,
            [
                'installer' => $installer
            ]
        );

        if (!$object instanceof \Ess\M2ePro\Setup\Upgrade\Source\AbstractFeature) {
            throw new \Ess\M2ePro\Model\Exception\Logic(
                __('%1 doesn\'t extends \Ess\M2ePro\Setup\Upgrade\Source\AbstractFeature', get_class($object))
            );
        }

        return $object;
    }

    //########################################

    private function prepareVersion($version)
    {
        return str_replace('.', '_', $version);
    }

    //########################################
}
