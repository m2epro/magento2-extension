<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Wizard\MigrationToInnodb;

use Ess\M2ePro\Block\Adminhtml\Wizard\AbstractWizard;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Wizard\MigrationToInnodb\Installation
 */
abstract class Installation extends AbstractWizard
{
    //########################################

    abstract protected function getStep();

    //########################################

    protected function _construct()
    {
        parent::_construct();

        $this->addButton(
            'continue',
            [
                'id'      => 'update_all_marketplaces',
                'label'   => $this->__('Continue'),
                'onclick' => 'MigrationToInnodbObj.continueStep();',
                'class'   => 'primary forward',
            ]
        );
    }

    protected function _beforeToHtml()
    {
        $this->setId('wizard' . $this->getNick() . $this->getStep());

        return parent::_beforeToHtml();
    }

    protected function _prepareLayout()
    {
        $this->jsUrl->addUrls($this->getHelper('Data')->getControllerActions('Wizard\MigrationToInnodb'));

        $this->jsUrl->addUrls(
            [
                'ebay_marketplace/synchGetExecutingInfo'    => $this->getUrl(
                    '*/ebay_marketplace/synchGetExecutingInfo'
                ),
                'amazon_marketplace/synchGetExecutingInfo'  => $this->getUrl(
                    '*/amazon_marketplace/synchGetExecutingInfo'
                ),
                'walmart_marketplace/synchGetExecutingInfo' => $this->getUrl(
                    '*/walmart_marketplace/synchGetExecutingInfo'
                )
            ]
        );

        $this->js->addOnReadyJs(
            <<<JS
    require([
        'M2ePro/Plugin/AreaWrapper',
        'M2ePro/Plugin/ProgressBar',
        'M2ePro/Wizard/MigrationToInnodb/MarketplaceSynchProgress',
        'M2ePro/Wizard/MigrationToInnodb'
    ], function(){
        window.MarketplaceSynchProgressObj = new WizardMigrationToInnodbMarketplaceSynchProgress(
            new ProgressBar('marketplaces_progress_bar'),
            new AreaWrapper('marketplaces_content_container')
        );

        window.MigrationToInnodbObj = new WizardMigrationToInnodb();
    });
JS
        );

        return parent::_prepareLayout();
    }

    //########################################

    protected function _toHtml()
    {
        $helpBlock = $this->createBlock('HelpBlock', 'wizard.help.block')->setData(
            [
                'no_collapse' => true,
                'no_hide'     => true
            ]
        );

        $contentBlock = $this->createBlock(
            $this->nameBuilder->buildClassName(
                [
                    'Wizard',
                    $this->getNick(),
                    'Installation',
                    $this->getStep(),
                    'Content'
                ]
            )
        )->setData(
            [
                'nick' => $this->getNick()
            ]
        );

        return
            '<div id="marketplaces_progress_bar"></div>' .
            '<div id="marketplaces_content_container">' .
            parent::_toHtml() .
            $helpBlock->toHtml() .
            $contentBlock->toHtml() .
            '</div>';
    }

    //########################################
}
