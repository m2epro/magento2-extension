<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Wizard\InstallationWalmart;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Wizard\InstallationWalmart\Installation
 */
abstract class Installation extends \Ess\M2ePro\Block\Adminhtml\Wizard\Installation
{
    //########################################

    protected function _construct()
    {
        parent::_construct();

        $this->updateButton('continue', 'onclick', 'InstallationWalmartWizardObj.continueStep();');
    }

    protected function _toHtml()
    {
        $this->js->add(<<<JS
    require([
        'M2ePro/Wizard/InstallationWalmart',
    ], function(){
        window.InstallationWalmartWizardObj = new WizardInstallationWalmart();
    });
JS
        );

        return parent::_toHtml();
    }

    //########################################
}
