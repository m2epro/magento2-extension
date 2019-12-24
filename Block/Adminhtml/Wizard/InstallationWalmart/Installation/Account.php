<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Wizard\InstallationWalmart\Installation;

use Ess\M2ePro\Block\Adminhtml\Wizard\InstallationWalmart\Installation;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Wizard\InstallationWalmart\Installation\Account
 */
class Account extends Installation
{
    //########################################

    protected function getStep()
    {
        return 'account';
    }

    //########################################

    protected function _toHtml()
    {
        $this->jsUrl->addUrls($this->getHelper('Data')->getControllerActions('General', [
            'component' => \Ess\M2ePro\Helper\Component\Walmart::NICK
        ]));

        $this->jsUrl->addUrls($this->getHelper('Data')->getControllerActions('Walmart\Marketplace'));

        $this->js->addOnReadyJs(<<<JS
    require([
        'M2ePro/Wizard/Walmart/MarketplaceSynchProgress',
        'M2ePro/Plugin/ProgressBar',
        'M2ePro/Plugin/AreaWrapper'
    ], function(){
        window.WizardWalmartMarketplaceSynchProgressObj = new WizardWalmartMarketplaceSynchProgress(
            new ProgressBar('progress_bar'),
            new AreaWrapper('content_container')
        );
    });
JS
        );

        return
            '<div id="progress_bar"></div>' .
            '<div id="content_container">' . parent::_toHtml() . '</div>';
    }

    //########################################
}
