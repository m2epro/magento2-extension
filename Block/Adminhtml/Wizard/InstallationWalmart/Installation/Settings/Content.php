<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Wizard\InstallationWalmart\Installation\Settings;

use Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm;

class Content extends AbstractForm
{
    //########################################

    protected $walmartFactory;

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        array $data = []
    )
    {
        $this->walmartFactory = $walmartFactory;

        parent::__construct($context, $registry, $formFactory, $data);
    }

    protected function _prepareLayout()
    {
        $this->getLayout()->getBlock('wizard.help.block')->setContent($this->__(<<<HTML
In this Section you can choose the Walmart Settings, on which you are going to sell your Items.
HTML
        ));

        parent::_prepareLayout();
    }

    //########################################

    public function _construct()
    {
        parent::_construct();
        $this->setId('wizardInstallationSettings');
    }

    //########################################

    protected function _prepareForm()
    {
        /** @var \Ess\M2ePro\Block\Adminhtml\Walmart\Settings\Tabs\Main $settings */
        $settings = $this->createBlock('Walmart\Settings\Tabs\Main');

        $settings->toHtml();
        $form = $settings->getForm();

        $form->setData([
            'id' => 'edit_form',
            'method' => 'post'
        ]);

        $form->setUseContainer(true);
        $this->setForm($form);
    }

    //########################################
}