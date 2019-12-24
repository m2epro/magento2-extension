<?php
/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\Update\y19_m01;

use Ess\M2ePro\Helper\Factory as HelperFactory;
use Ess\M2ePro\Model\Factory as ModelFactory;
use Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature;
use Magento\Framework\Module\Setup;
use \Magento\Framework\App\Filesystem\DirectoryList;

/**
 * Class \Ess\M2ePro\Setup\Update\y19\NewUpgradesEngine_m01
 */
class NewUpgradesEngine extends AbstractFeature
{
    /** @var \Magento\Framework\Filesystem */
    protected $filesystem;

    //########################################

    public function __construct(
        HelperFactory $helperFactory,
        ModelFactory $modelFactory,
        Setup $installer,
        \Magento\Framework\Filesystem $filesystem
    ) {
        parent::__construct($helperFactory, $modelFactory, $installer);
        $this->filesystem = $filesystem;
    }

    //########################################

    public function execute()
    {
        $this->filesystem->getDirectoryWrite(DirectoryList::VAR_DIR)->writeFile(
            'M2ePro/development/installed_upgrades.json',
            '[]'
        );
    }

    //########################################
}
