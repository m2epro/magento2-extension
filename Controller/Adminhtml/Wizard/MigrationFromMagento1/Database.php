<?php

namespace Ess\M2ePro\Controller\Adminhtml\Wizard\MigrationFromMagento1;

use Ess\M2ePro\Controller\Adminhtml\Wizard\BaseMigrationFromMagento1;

class Database extends BaseMigrationFromMagento1
{
    //########################################

    public function execute()
    {
        $result = $this->resultPageFactory->create();

        $result->getConfig()->addPageAsset("Ess_M2ePro::css/style.css");
        $result->getConfig()->addPageAsset("Ess_M2ePro::css/wizard.css");

        $result->getConfig()->getTitle()->set(__(
            'M2E Pro Module Migration from Magento v1.x'
        ));

        /** @var \Ess\M2ePro\Block\Adminhtml\Wizard\MigrationFromMagento1\Installation\Database $block */
        $block = $result->getLayout()->createBlock(
            '\Ess\M2ePro\Block\Adminhtml\Wizard\MigrationFromMagento1\Installation\Database'
        );
        $block->setData('nick', 'migrationFromMagento1');

        $this->_addContent($block);

        return $result;
    }

    //########################################
}