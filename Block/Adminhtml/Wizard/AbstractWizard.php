<?php

namespace Ess\M2ePro\Block\Adminhtml\Wizard;

use Ess\M2ePro\Block\Adminhtml\Magento\AbstractContainer;

abstract class AbstractWizard extends AbstractContainer
{
    protected function _prepareLayout()
    {
        $this->css->addFile('wizard.css');

        return parent::_prepareLayout();
    }

    protected function _beforeToHtml()
    {
        $this->jsPhp->addConstants($this->getHelper('Data')->getClassConstants('\Ess\M2ePro\Helper\Module\Wizard'));

        $this->jsUrl->addUrls([
            'setStep' => $this->getUrl('*/wizard_'.$this->getNick().'/setStep'),
            'setStatus' => $this->getUrl('*/wizard_'.$this->getNick().'/setStatus')
        ]);

        $this->jsTranslator->addTranslations([
            'Step' => $this->__('Step'),
            'Note: If you close the Wizard, it never starts again. You will be required to set all Settings manually.
            Press Cancel to continue working with Wizard.' => $this->__(
                'Note: If you close the Wizard, it never starts again.
                You will be required to set all Settings manually. Press Cancel to continue working with Wizard.'
            ),
            'Completed' => $this->__('Completed'),
        ]);

        $step = $this->getHelper('Module\Wizard')->getStep($this->getNick());
        $steps = $this->getHelper('Data')->jsonEncode(
            $this->getHelper('Module\Wizard')->getWizard($this->getNick())->getSteps()
        );
        $status = $this->getHelper('Module\Wizard')->getStatus($this->getNick());

        $this->js->add( <<<JS
    require([
        'M2ePro/Wizard',
    ], function(){
        window.WizardObj = new Wizard('{$status}', '{$step}');
        WizardObj.steps.all = {$steps};
    });
JS
        );

        return parent::_beforeToHtml();
    }
}