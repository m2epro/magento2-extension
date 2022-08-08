<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Wizard\InstallationEbay\Installation\Settings;

use Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm;

class Content extends AbstractForm
{
    public function _construct()
    {
        parent::_construct();
        $this->setId('wizardInstallationSettings');
    }

    protected function _prepareLayout()
    {
        $this->getLayout()->getBlock('wizard.help.block')->setContent(
            $this->__('
               In this section, you can configure the general settings for the interaction between M2E Pro and eBay
               Marketplaces.<br><br>Anytime you can change these settings under <b>eBay > Configuration > General</b>.
               ')
        );

        parent::_prepareLayout();
    }

    protected function _prepareForm()
    {
        $settings = $this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\Ebay\Settings\Tabs\Main::class);

        $settings->toHtml();
        $form = $settings->getForm();

        $form->setData([
            'id'     => 'edit_form',
            'method' => 'post'
        ]);

        $form->setUseContainer(true);
        $this->setForm($form);
    }
}
