<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\MigrationFromMagento1\PreconditionsChecker;

use Ess\M2ePro\Model\Wizard\MigrationFromMagento1;

/**
 * Class \Ess\M2ePro\Setup\MigrationFromMagento1\PreconditionsChecker\AbstractModel
 */
abstract class AbstractModel
{
    protected $supportedVersionPatterns = [
        '6.8.*',
        '6.9.*',
        '6.10.*',
        '6.11.*',
        '6.12.*',
        '6.13.*',
        '6.14.*',
        '6.15.*',
        '6.16.*',
        '6.17.*',
        '6.18.*',
        '6.19.*',
        '6.20.*',
        '6.21.*',
        '6.22.*',
        '6.23.*',
        '6.24.*',
        '6.25.*',
        '6.26.*',
        '6.27.*',
        '6.28.*',
        '6.29.*',
        '6.30.*',
        '6.31.*',
        '6.32.*',
        '6.33.*',
        '6.34.*',
        '6.35.*',
        '6.36.*',
        '6.37.*',
        '6.38.*',
        '6.39.*',
        '6.40.*',
        '6.41.*',
        '6.42.*',
        '6.43.*',
        '6.44.*',
        '6.45.*',
        '6.46.*',
        '6.47.*',
        '6.48.*',
        '6.49.*',
        '6.50.*',
        '6.51.*',
        '6.52.*',
        '6.53.*',
        '6.54.*',
        '6.55.*',
        '6.56.*',
        '6.57.*',
        '6.58.*',
        '6.59.*',
        '6.60.*',
        '6.61.*',
        '6.62.*',
        '6.63.*',
        '6.64.*',
        '6.65.*',
        '6.66.*',
        '6.67.*',
        '6.68.*',
        '6.69.*',
        '6.70.*',
        '6.71.*',
        '6.73.*',
        '6.74.*',
        '6.75.*',
        '6.76.*',
        '6.77.*',
        '6.78.*',
        '6.79.*',
        '6.80.*',
        '6.81.*',
        '6.82.*',
        '6.83.*',
    ];

    /** @var \Ess\M2ePro\Model\ActiveRecord\Factory */
    protected $activeRecordFactory;

    /** @var \Ess\M2ePro\Helper\Factory */
    protected $helperFactory;

    /** @var \Ess\M2ePro\Model\Factory */
    protected $modelFactory;

    /** @var \Magento\Framework\App\ResourceConnection */
    protected $resourceConnection;

    /** @var string */
    protected $oldTablesPrefix;

    //########################################

    public function __construct(
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Magento\Framework\App\ResourceConnection $resourceConnection
    ) {
        $this->activeRecordFactory = $activeRecordFactory;
        $this->helperFactory       = $helperFactory;
        $this->modelFactory        = $modelFactory;
        $this->resourceConnection  = $resourceConnection;
    }

    abstract public function checkPreconditions();

    /**
     * @param $version
     * @return bool
     */
    protected function compareVersions($version)
    {
        foreach ($this->supportedVersionPatterns as $supportedVersionPattern) {
            if ($this->compareVersion($version, $supportedVersionPattern)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param $version
     * @param $supportedVersionPattern
     * @return bool
     *
     * Example: v6.0.0 and v6.0.10 will pass 6.0.*
     */
    protected function compareVersion($version, $supportedVersionPattern)
    {
        $pattern = explode('.', $supportedVersionPattern);

        foreach (explode('.', $version) as $vIndex => $vPart) {
            if (!isset($pattern[$vIndex])) {
                return false;
            }

            if ($pattern[$vIndex] === '*') {
                return true;
            }

            if ($pattern[$vIndex] != $vPart) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return mixed
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function getOldTablesPrefix()
    {
        if (empty($this->oldTablesPrefix)) {
            /** @var \Ess\M2ePro\Model\Wizard\MigrationFromMagento1 $wizard */
            $wizard = $this->helperFactory->getObject('Module_Wizard')->getWizard(MigrationFromMagento1::NICK);
            $this->oldTablesPrefix = $wizard->getM1TablesPrefix();
        }

        return $this->oldTablesPrefix;
    }

    /**
     * @return \Magento\Framework\DB\Adapter\AdapterInterface
     */
    protected function getConnection()
    {
        return $this->resourceConnection->getConnection();
    }

    //########################################
}
