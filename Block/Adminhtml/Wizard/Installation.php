<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Wizard;

abstract class Installation extends AbstractWizard
{
    /** @var \Ess\M2ePro\Helper\Data */
    private $dataHelper;

    /**
     * @param \Ess\M2ePro\Helper\Data $dataHelper
     * @param \Ess\M2ePro\Helper\Module\Wizard $wizardHelper
     * @param \Ess\M2ePro\Block\Adminhtml\Magento\Context\Widget $context
     * @param array $data
     */
    public function __construct(
        \Ess\M2ePro\Helper\Data $dataHelper,
        \Ess\M2ePro\Helper\Module\Wizard $wizardHelper,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Widget $context,
        array $data = []
    ) {
        $this->dataHelper = $dataHelper;
        parent::__construct($dataHelper, $wizardHelper, $context, $data);
    }

    abstract protected function getStep();

    protected function _construct()
    {
        parent::_construct();

        $this->addButton(
            'continue',
            [
                'label' => $this->__('Continue'),
                'class' => 'primary forward',
            ],
            1,
            0
        );
    }

    protected function _beforeToHtml()
    {
        $this->setId('wizard' . $this->getNick() . $this->getStep());

        return parent::_beforeToHtml();
    }

    protected function _toHtml()
    {
        $this->jsUrl->addUrls(
            $this->dataHelper->getControllerActions(
                $this->nameBuilder->buildClassName(
                    [
                        'Wizard',
                        $this->getNick(),
                    ]
                ),
                [],
                true
            )
        );
        $this->jsUrl->addUrls([
            'wizard_registration/createLicense' => $this->getUrl('*/wizard_registration/createLicense'),
        ]);

        $stepsBlock = $this->getLayout()->createBlock(
            $this->nameBuilder->buildClassName(
                [
                    '\Ess\M2ePro\Block\Adminhtml\Wizard',
                    $this->getNick(),
                    'Breadcrumb',
                ]
            )
        )->setSelectedStep($this->getStep());

        $helpBlock = $this->getLayout()
                          ->createBlock(\Ess\M2ePro\Block\Adminhtml\HelpBlock::class, 'wizard.help.block')
                          ->setData(
                              [
                              'no_collapse' => true,
                              'no_hide'     => true,
                              ]
                          );

        $contentBlock = $this->getLayout()->createBlock(
            $this->nameBuilder->buildClassName(
                [
                    '\Ess\M2ePro\Block\Adminhtml\Wizard',
                    $this->getNick(),
                    'Installation',
                    $this->getStep(),
                    'Content',
                ]
            )
        )->setData(
            [
                'nick' => $this->getNick(),
            ]
        );

        return parent::_toHtml() .
            $stepsBlock->toHtml() .
            $helpBlock->toHtml() .
            $contentBlock->toHtml();
    }
}
