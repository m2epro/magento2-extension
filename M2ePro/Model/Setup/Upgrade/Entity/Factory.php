<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Setup\Upgrade\Entity;

/**
 * Class \Ess\M2ePro\Model\Setup\Upgrade\Entity\Factory
 */
class Factory
{
    private $objectManager;

    //########################################

    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager
    ) {
        $this->objectManager = $objectManager;
    }

    //########################################

    /**
     * @param $featureName string
     * @param $fromVersion string|null
     * @param $toVersion string|null
     * @return \Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getFeatureObject($featureName, $fromVersion = null, $toVersion = null)
    {
        if (strpos($featureName, '/') !== false && strpos($featureName, '@') === 0) {
            $featureName = explode('/', substr($featureName, 1));
            $className = '\Ess\M2ePro\Setup\Update\\' . $featureName[0] . '\\' . $featureName[1];
        } elseif ($fromVersion !== null && $toVersion !== null) {
            $fromVersion = $this->prepareVersion($fromVersion);
            $toVersion   = $this->prepareVersion($toVersion);
            $className = '\Ess\M2ePro\Setup\Upgrade\v'.$fromVersion.'__v'.$toVersion.'\\'.$featureName;
        } else {
            $className = '\Ess\M2ePro\Setup\Update\\' . $featureName;
        }

        $object = $this->objectManager->create($className);

        if (!$object instanceof \Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature) {
            throw new \Ess\M2ePro\Model\Exception\Logic(
                __('%1 doesn\'t extends \Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature', get_class($object))
            );
        }

        return $object;
    }

    /**
     * @param $fromVersion
     * @param $toVersion
     * @return \Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractConfig
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getConfigObject($fromVersion, $toVersion)
    {
        $fromVersion = $this->prepareVersion($fromVersion);
        $toVersion   = $this->prepareVersion($toVersion);

        $object = $this->objectManager->create(
            '\Ess\M2ePro\Setup\Upgrade\v'.$fromVersion.'__v'.$toVersion.'\Config'
        );

        if (!$object instanceof \Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractConfig) {
            throw new \Ess\M2ePro\Model\Exception\Logic(
                __('%1 doesn\'t extends \Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractConfig', get_class($object))
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
