<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Wizard\InstallationWalmart\Installation\Settings;

use Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Wizard\InstallationWalmart\Installation\Settings\Content
 */
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
    ) {
        $this->walmartFactory = $walmartFactory;

        parent::__construct($context, $registry, $formFactory, $data);
    }

    protected function _prepareLayout()
    {
        $this->getLayout()->getBlock('wizard.help.block')->setContent($this->__(<<<HTML
In this section, you can configure the general settings for interaction between M2E Pro and Walmart
Marketplaces including SKU, Product Identifiers, image URL settings.
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
        $settings = $this->createBlock('Walmart_Settings_Tabs_Main');

        $settings->toHtml();
        $form = $settings->getForm();
        $form->removeField('block_notice_general');

        $form->setData([
            'id' => 'edit_form',
            'method' => 'post'
        ]);

        $form->setUseContainer(true);
        $this->setForm($form);
    }

    //########################################
}
