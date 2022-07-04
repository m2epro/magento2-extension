<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Wizard;

use Ess\M2ePro\Block\Adminhtml\Magento\AbstractContainer;

abstract class AbstractWizard extends AbstractContainer
{
    /** @var \Ess\M2ePro\Helper\Data */
    private $dataHelper;
    /** @var \Ess\M2ePro\Helper\Module\Wizard */
    private $wizardHelper;

    public function __construct(
        \Ess\M2ePro\Helper\Data $dataHelper,
        \Ess\M2ePro\Helper\Module\Wizard $wizardHelper,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Widget $context,
        array $data = []
    ) {
        $this->dataHelper = $dataHelper;
        $this->wizardHelper = $wizardHelper;
        parent::__construct($context, $data);
    }

    protected function _prepareLayout()
    {
        $this->css->addFile('wizard.css');

        return parent::_prepareLayout();
    }


    protected function _beforeToHtml()
    {
        $this->jsPhp->addConstants(
            $this->dataHelper
                ->getClassConstants(\Ess\M2ePro\Helper\Module\Wizard::class)
        );

        $this->jsUrl->addUrls(
            [
                'setStep'   => $this->getUrl('*/wizard_' . $this->getNick() . '/setStep'),
                'setStatus' => $this->getUrl('*/wizard_' . $this->getNick() . '/setStatus'),
            ]
        );

        $this->jsTranslator->addTranslations(
            [
                'Step'      => $this->__('Step'),
                'Completed' => $this->__('Completed'),
            ]
        );

        $step = $this->wizardHelper->getStep($this->getNick());
        $steps = $this->dataHelper->jsonEncode(
            $this->wizardHelper->getWizard($this->getNick())->getSteps()
        );
        $status = $this->wizardHelper->getStatus($this->getNick());

        $this->js->add(
            <<<JS
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
