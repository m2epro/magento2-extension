<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\Update\dev;

use Ess\M2ePro\Helper\Factory as HelperFactory;
use Ess\M2ePro\Model\Factory as ModelFactory;
use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;
use Magento\Framework\Module\Setup;

/**
 * Class \Ess\M2ePro\Setup\Update\dev\ModuleConfigs
 * @codingStandardsIgnoreFile
 */
class ModuleConfigs extends AbstractFeature
{
    protected $filesystem;

    //########################################

    public function __construct (
        HelperFactory $helperFactory,
        ModelFactory $modelFactory,
        Setup $installer,
        \Magento\Framework\Filesystem $filesystem
    ) {
        $this->filesystem = $filesystem;
        parent::__construct($helperFactory, $modelFactory, $installer);
    }

    //########################################

    public function execute()
    {
        $fileName = $this->filesystem->getDirectoryRead(\Magento\Framework\App\Filesystem\DirectoryList::ROOT)
            ->getAbsolutePath('reinstall_module/ChangeServerLocation.php');

        if (!is_file($fileName)) {
            return;
        }

        $fileContent = '<?php
$myHost = $_SERVER["HTTP_HOST"];
$this->resourceConnection->getConnection()->update(
    $this->resourceConnection->getTableName("m2epro_config"),
    ["value" => "http://{$myHost}/server/worker/public/"],
    [
        "`group` = ?" => "/server/location/1/",
        "`key` = ?"   => "baseurl"
    ]
);

$this->resourceConnection->getConnection()->update(
    $this->resourceConnection->getTableName("m2epro_config"),
    ["value" => $myHost],
    [
        "`group` = ?" => "/server/location/1/",
        "`key` = ?"   => "hostname"
    ]
);';

        file_put_contents($fileName, $fileContent);
    }

    //########################################
}
