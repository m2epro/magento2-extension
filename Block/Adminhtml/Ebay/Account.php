<?php

namespace Ess\M2ePro\Block\Adminhtml\Ebay;

class Account extends \Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractContainer
{
    /** @var \Ess\M2ePro\Helper\Data */
    private $dataHelper;

    public function __construct(
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Widget $context,
        \Ess\M2ePro\Helper\Data $dataHelper,
        array $data = []
    ) {
        $this->dataHelper = $dataHelper;
        parent::__construct($context, $data);
    }

    protected function _construct()
    {
        parent::_construct();
        $this->_controller = 'adminhtml_ebay_account';

        $this->removeButton('add');

        $saveButtons = [
            'id' => 'add-ebay-account',
            'label' => __('Add Account'),
            'class' => 'add-ebay-account',
            'style' => 'pointer-events: none',
            'class_name' => \Ess\M2ePro\Block\Adminhtml\Magento\Button\SplitButton::class,
            'options' => [
                'production' => [
                    'label' => __('Live Account'),
                    'id' => 'production',
                    'onclick' => 'setLocation(this.getAttribute("data-url"))',
                    'data_attribute' => [
                        'url' => $this->getUrl(
                            '*/ebay_account/beforeGetSellApiToken',
                            ['mode' => \Ess\M2ePro\Model\Ebay\Account::MODE_PRODUCTION]
                        ),
                    ],
                ],
                'sandbox' => [
                    'label' => __('Sandbox Account'),
                    'id' => 'sandbox',
                    'onclick' => 'setLocation(this.getAttribute("data-url"))',
                    'data_attribute' => [
                        'url' => $this->getUrl(
                            '*/ebay_account/beforeGetSellApiToken',
                            ['mode' => \Ess\M2ePro\Model\Ebay\Account::MODE_SANDBOX]
                        ),
                    ],
                ],
            ],
        ];

        $this->addButton('add', $saveButtons);
    }

    protected function _prepareLayout()
    {
        $this->appendHelpBlock([
            'content' => $this->__(
                <<<HTML
<p>On this Page you can find information about eBay Accounts which can be managed via M2E Pro.</p><br>
<p>Settings for such configurations as eBay Orders along with Magento Order creation conditions,
Unmanaged Listings import including options of Linking them to Magento Products and Moving them to M2E Pro Listings,
etc. can be specified for each Account separately.</p><br>
<p><strong>Note:</strong> eBay Account can be deleted only if it is not being used for any of M2E Pro Listings.</p>
HTML
            ),
        ]);

        $this->jsUrl->addUrls($this->dataHelper->getControllerActions('Ebay_Account_Feedback'));
        $this->js->add(
            <<<JS
    require([
        'M2ePro/Ebay/Account'
    ], function(){
        window.EbayAccountObj = new EbayAccount();
    });
JS
        );

        $this->jsTranslator->addTranslations([
            'Should be between 2 and 80 characters long.' => $this->__('Should be between 2 and 80 characters long.'),
            'Select Account Mode' => __('Select Account Mode')
        ]);

        $this->css->addFile('ebay/account/feedback.css');

        return parent::_prepareLayout();
    }

    protected function _toHtml()
    {
        $this->js->add(
            <<<JS

    require([
        'M2ePro/Ebay/Account/Grid'
    ], function(){

        window.EbayAccountGridObj = new EbayAccountGrid(
            '{$this->getChildBlock('grid')->getId()}'
        );
    });
JS
        );

        return parent::_toHtml();
    }
}
