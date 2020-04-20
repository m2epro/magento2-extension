<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Wizard\MigrationFromMagento1;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Wizard\MigrationFromMagento1\Installation
 */
abstract class Installation extends \Ess\M2ePro\Block\Adminhtml\Wizard\Installation
{
    //########################################

    protected function _construct()
    {
        parent::_construct();

        $this->updateButton('continue', 'onclick', 'MigrationFromMagento1Obj.continueStep();');
    }

    protected function _toHtml()
    {
        $this->js->add(<<<JS
    require([
        'M2ePro/Wizard/MigrationFromMagento1',
    ], function(){
        window.MigrationFromMagento1Obj = new MigrationFromMagento1();
    });
JS
        );

        return parent::_toHtml();
    }

    //########################################
}
