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

/**
 * Class \Ess\M2ePro\Setup\Update\y19\ChangeDevelopVersion_m01
 */
class ChangeDevelopVersion extends AbstractFeature
{
    /** @var \Magento\Framework\Module\ResourceInterface */
    protected $moduleResource;

    //########################################

    public function __construct(
        HelperFactory $helperFactory,
        ModelFactory $modelFactory,
        Setup $installer,
        \Magento\Framework\ObjectManagerInterface $objectManager
    ) {
        parent::__construct($helperFactory, $modelFactory, $installer);
        $this->moduleResource = $objectManager->create(\Magento\Framework\Module\ResourceInterface::class);
    }

    //########################################

    // todo Should NOT be added to Public version
    public function execute()
    {
        $this->moduleResource->setDbVersion(\Ess\M2ePro\Helper\Module::IDENTIFIER, '1.0.0');
        $this->moduleResource->setDataVersion(\Ess\M2ePro\Helper\Module::IDENTIFIER, '1.0.0');
    }

    //########################################
}
