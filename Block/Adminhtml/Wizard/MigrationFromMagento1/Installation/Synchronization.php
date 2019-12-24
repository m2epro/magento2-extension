<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Wizard\MigrationFromMagento1\Installation;

use Ess\M2ePro\Block\Adminhtml\Wizard\MigrationFromMagento1\Installation;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Wizard\MigrationFromMagento1\Installation\Synchronization
 */
class Synchronization extends Installation
{
    protected function getStep()
    {
        return 'synchronization';
    }

    protected function _toHtml()
    {
        $this->js->add(<<<JS
    require([
        'M2ePro/Wizard/MigrationFromMagento1',
        'M2ePro/Wizard/MigrationFromMagento1/MarketplaceSynchProgress',
        'M2ePro/Plugin/ProgressBar',
    ], function(){
        window.MarketplaceSynchProgressObj = new MigrationFromMagento1MarketplaceSynchProgress(
            new ProgressBar('progress_bar')
        );
        window.MigrationFromMagento1Obj = new MigrationFromMagento1();
    });
JS
        );

        $this->jsUrl->addUrls($this->getHelper('Data')->getControllerActions(
            $this->nameBuilder->buildClassName([
                'Wizard', $this->getNick()
            ])
        ));

        $this->jsTranslator->add(
            'Please wait while Synchronization is finished.',
            $this->__('Please wait while Synchronization is finished.')
        );
        $this->jsTranslator->add(
            'Preparing to start. Please wait ...',
            $this->__('Preparing to start. Please wait ...')
        );

        $stepsBlock = $this->createBlock(
            $this->nameBuilder->buildClassName([
                'Wizard', $this->getNick(), 'Breadcrumb'
            ])
        )->setSelectedStep($this->getStep());

        $helpBlock = $this->createBlock('HelpBlock', 'wizard.help.block')->setData([
            'no_collapse' => true,
            'no_hide' => true
        ]);

        $contentBlock = $this->createBlock(
            $this->nameBuilder->buildClassName([
                'Wizard', $this->getNick(), 'Installation', $this->getStep(), 'Content'
            ])
        )->setData([
            'nick' => $this->getNick()
        ]);

        return
            \Ess\M2ePro\Block\Adminhtml\Magento\AbstractContainer::_toHtml() .
            $stepsBlock->toHtml() .
            '<div id="progress_bar"></div>' .
            $helpBlock->toHtml() .
            $contentBlock->toHtml();
    }
}
