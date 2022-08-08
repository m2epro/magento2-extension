<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Wizard\InstallationAmazon\Installation\Account;

use Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm;

class Content extends AbstractForm
{
    /** @var \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory */
    private $amazonFactory;
    /** @var \Ess\M2ePro\Helper\Data */
    private $dataHelper;

    /**
     * @param \Ess\M2ePro\Helper\Data $dataHelper
     * @param \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory
     * @param \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param array $data
     */
    public function __construct(
        \Ess\M2ePro\Helper\Data $dataHelper,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        array $data = []
    ) {
        $this->amazonFactory = $amazonFactory;
        $this->dataHelper = $dataHelper;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    protected function _prepareLayout()
    {
        $this->getLayout()->getBlock('wizard.help.block')->setContent(
            $this->__(
                <<<HTML
On this step, you should link your Amazon Account with your M2E Pro.<br/><br/>
Please, select the Marketplace you are going to sell on and click on Continue button.
HTML
            )
        );

        parent::_prepareLayout();
    }

    protected function _prepareForm()
    {
        $form = $this->_formFactory->create([
            'data' => [
                'id' => 'edit_form',
            ],
        ]);

        $fieldset = $form->addFieldset(
            'marketplaces',
            [
            ]
        );

        $marketplacesCollection = $this->amazonFactory->getObject('Marketplace')->getCollection()
                                                      ->addFieldToFilter('developer_key', ['notnull' => true])
                                                      ->setOrder('sorder', 'ASC');

        $marketplaces = [
            [
                'value' => '',
                'label' => '',
            ],
        ];
        foreach ($marketplacesCollection->getItems() as $item) {
            $marketplace = array_merge($item->getData(), $item->getChildObject()->getData());
            $marketplaces[$marketplace['id']] = $marketplace['title'];
        }

        $fieldset->addField(
            'marketplace_id',
            'select',
            [
                'label' => $this->__('What the Marketplace do You Want to Onboard?'),
                'css_class' => 'account-mode-choose',
                'name' => 'marketplace_id',
                'values' => $marketplaces,
            ]
        );

        $this->jsUrl->addUrls($this->dataHelper->getControllerActions('Wizard\InstallationAmazon'));
        $this->jsUrl->addUrls($this->dataHelper->getControllerActions('Amazon\Account'));

        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }

    protected function _beforeToHtml()
    {
        $this->jsTranslator->add(
            'Please select Marketplace first.',
            $this->__('Please select Marketplace first.')
        );

        $this->jsTranslator->add(
            'An error during of account creation.',
            $this->__('The Amazon token obtaining is currently unavailable. Please try again later.')
        );

        return parent::_beforeToHtml();
    }
}
