<?php

namespace Ess\M2ePro\Block\Adminhtml\Wizard\InstallationEbay;

abstract class Installation extends \Ess\M2ePro\Block\Adminhtml\Wizard\Installation
{
    //########################################

    protected function _construct()
    {
        parent::_construct();

        $this->updateButton('continue', 'onclick', 'InstallationEbayWizardObj.continueStep();');
    }

    protected function _toHtml()
    {
        $this->js->add( <<<JS
    require([
        'M2ePro/Wizard/InstallationEbay',
    ], function(){
        window.InstallationEbayWizardObj = new WizardInstallationEbay();
    });
JS
        );

        return parent::_toHtml();
    }

    //########################################
}