<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Walmart;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Walmart\Account
 */
class Account extends \Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractContainer
{
    //########################################

    protected function _construct()
    {
        parent::_construct();

        // Set buttons actions
        // ---------------------------------------
        $this->removeButton('back');
        $this->removeButton('reset');
        $this->removeButton('delete');
        $this->removeButton('save');
        $this->removeButton('edit');
        $this->removeButton('add');

        // ---------------------------------------

        $this->_controller = 'adminhtml_walmart_account';
        $this->addAccountButton();
    }

    private function addAccountButton()
    {
        $options = $this->getDropdownOptions();

        $this->buttonList->add(
            'add',
            [
                'label' => __('Add Account'),
                'class' => 'action-primary',
                'class_name' => \Magento\Backend\Block\Widget\Button\SplitButton::class,
                'options' => $options,
            ]
        );
    }

    private function getDropdownOptions(): array
    {
        return [
            [
                'label' => __('United States'),
                'id' => 'account-us',
                'onclick' => 'setLocation(this.getAttribute("data-url"))',
                'data_attribute' => [
                    'url' => $this->getUrl(
                        '*/walmart_account_unitedStates/beforeGetToken',
                        [
                            '_current' => true,
                            'marketplace_id' => \Ess\M2ePro\Helper\Component\Walmart::MARKETPLACE_US
                        ]
                    ),
                ],
            ],
            [
                'label' => __('Canada'),
                'id' => 'account-ca',
                'on_click' => '',
                'data_attribute' => [
                    'mage-init' => [
                        'M2ePro/Walmart/Account/AddButton' => [
                            'checkAuthUrl' => $this->getUrl('*/walmart_account/checkAuth', [
                                'marketplace_id' => \Ess\M2ePro\Helper\Component\Walmart::MARKETPLACE_CA,
                                '_current' => true,
                            ]),
                        ],
                    ],
                ],
            ],
        ];
    }

    protected function _prepareLayout()
    {
        $this->appendHelpBlock([
            'content' => __(
                <<<HTML
            In this section, you can create, edit and delete Accounts for Walmart integration.
            Please be advised that Account is created per Marketplace using the relevant API credentials.
HTML
            ),
        ]);

        return parent::_prepareLayout();
    }

    //########################################
}
