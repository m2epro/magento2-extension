<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Wizard;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Wizard\Installation
 */
abstract class Installation extends AbstractWizard
{
    //########################################

    abstract protected function getStep();

    //########################################

    protected function _construct()
    {
        parent::_construct();

        $this->addButton('continue', [
            'label' => $this->__('Continue'),
            'class' => 'primary forward',
        ]);
    }

    protected function _beforeToHtml()
    {
        $this->setId('wizard' . $this->getNick() . $this->getStep());

        return parent::_beforeToHtml();
    }

    protected function _toHtml()
    {
        $this->jsUrl->addUrls($this->getHelper('Data')->getControllerActions(
            $this->nameBuilder->buildClassName([
                'Wizard', $this->getNick()
            ])
        ));

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

        return parent::_toHtml() .
            $stepsBlock->toHtml() .
            $helpBlock->toHtml() .
            $contentBlock->toHtml();
    }

    //########################################
}
