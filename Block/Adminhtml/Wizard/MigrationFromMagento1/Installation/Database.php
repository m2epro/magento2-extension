<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Wizard\MigrationFromMagento1\Installation;

use Ess\M2ePro\Block\Adminhtml\Wizard\MigrationFromMagento1\Installation;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Wizard\MigrationFromMagento1\Installation\Database
 */
class Database extends Installation
{
    //########################################

    protected function _construct()
    {
        parent::_construct();

        $this->jsUrl->addUrls([
            'migrationFromMagento1/complete' => $this->getUrl('m2epro/migrationFromMagento1/complete')
        ]);

        $this->updateButton('continue', 'onclick', 'MigrationFromMagento1Obj.complete();');
    }

    protected function getStep()
    {
        return 'database';
    }

    //########################################

    protected function _beforeToHtml()
    {
        return $this;
    }

    //########################################
}
