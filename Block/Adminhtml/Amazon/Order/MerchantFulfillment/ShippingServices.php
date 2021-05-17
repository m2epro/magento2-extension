<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Order\MerchantFulfillment;

/**
 * Class Ess\M2ePro\Block\Adminhtml\Amazon\Order\MerchantFulfillment\ShippingServices
 */
class ShippingServices extends \Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm
{
    private $localeCurrency;

    //########################################

    public function __construct(
        \Magento\Framework\Locale\CurrencyInterface $localeCurrency,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        array $data = []
    ) {
        $this->localeCurrency = $localeCurrency;

        parent::__construct($context, $registry, $formFactory, $data);
    }

    //########################################

    public function _construct()
    {
        parent::_construct();
        $this->setId('amazonOrderMerchantFulfillmentShippingServices');
    }

    //########################################

    protected function _prepareForm()
    {
        $form = $this->_formFactory->create(
            [
                'data' => [
                    'id' => 'shippingServices_form',
                ]
            ]
        );

        $shippingServices = $this->getData('shipping_services');

        if ($this->getData('error_message') !== null) {
            $form->addField(
                'error_message',
                self::MESSAGES,
                [
                    'messages' => [
                        [
                            'type'    => \Magento\Framework\Message\MessageInterface::TYPE_WARNING,
                            'content' => $this->getData('error_message')
                        ]
                    ]
                ]
            );
        } elseif (empty($shippingServices['items']['available']) &&
            empty($shippingServices['items']['unavailable']) &&
            empty($shippingServices['not_accepted'])
        ) {
            $form->addField(
                'amazon_template_description_definition',
                self::HELP_BLOCK,
                [
                    'content'     => $this->__(
                        <<<HTML
There were <strong>no</strong> suitable <strong>Shipping Services</strong> found according to the provided 
<strong>Configuration Settings</strong>.<br/>
You can press '<strong>Back</strong>' Button and Return to the <strong>Previous Page</strong> to adjust the 
Settings. We recommend you to edit '<strong>Carrier Will Pick Up</strong>' and '<strong>Delivery Experience</strong>'
Conditions.
HTML
                        ,
                        $this->getHelper('Module\Support')->getDocumentationArticleUrl('x/OAYtAQ')
                    ),
                    'no_hide'     => true,
                    'no_collapse' => true
                ]
            );
        } else {
            $gridRowsHtml = '';
            $requeired = '<span class="required">*</span>';

            if (empty($shippingServices['items']['available'])) {
                $requeired = '';
            }

            foreach ($shippingServices['items']['available'] as $shippingService) {
                $gridRowsHtml .= <<<HTML
    <tr>
        <td class="radio-input">
            <input id="{$shippingService['id']}" value="{$shippingService['id']}" 
            class="shipping-services-radio-input" name="shipping_service_id" type="radio" />
        </td>
        <td>
            <label for="{$shippingService['id']}">
                <strong>
                    {$shippingService['name']}
                </strong>
                <div class="shipping-service-details">
                    <div>
                        <span class="shipping-service-prop">{$this->__('Ship Date')}:</span>
                        {$this->_localeDate->formatDate(
                    $shippingService['date']['ship'],
                    \IntlDateFormatter::MEDIUM,
                    true
                )}
                    </div>
                    <div>
                        <span class="shipping-service-prop">{$this->__('Estimated Delivery Date')}:</span>
                        {$this->_localeDate->formatDate(
                    $shippingService['date']['estimated_delivery']['latest'],
                    \IntlDateFormatter::MEDIUM,
                    true
                )}
                    </div>
                    <div>
                        <span class="shipping-service-prop">{$this->__('Rate')}:</span>
                        {$this->localeCurrency->getCurrency($shippingService['rate']['currency_code'])
                    ->toCurrency($shippingService['rate']['amount'])}
                    </div>
                </div>
            </label>
        </td>
    </tr>
HTML;
            }

            foreach ($shippingServices['items']['unavailable'] as $notAcceptedCarrier) {
                $gridRowsHtml .= <<<HTML
    <tr>
        <td></td>
        <td>
            <strong>
                {$notAcceptedCarrier}
            </strong>
            <div class="shipping-service-details conditions-not-accepted-details">
                {$this->__(
                    'The Terms and Conditions for this Carrier have not been accepted by the Seller
                    in Amazon Seller Central Account.<br/><br/>
                    Please, <strong>Modify the Settings</strong> in your Seller Central Account
                    in order to <strong>use this Carrier</strong>.'
                )}
            </div>
        </td>
    </tr>
HTML;
            }

            foreach ($shippingServices['not_accepted'] as $temporarilyUnavailableCarrier) {
                $gridRowsHtml .= <<<HTML
    <tr>
        <td></td>
        <td>
            <strong>
                {$temporarilyUnavailableCarrier}
            </strong>
            <div class="shipping-service-details temporarily-unavailable-details">
                {$this->__(
                    'A Carrier is temporarily unavailable, most likely due to a service outage experienced by the 
                    carrier. Please, try again later.'
                )}
            </div>
        </td>
    </tr>
HTML;
            }

            $gridHtml = <<<HTML
    <table class="border data-grid data-grid-not-hovered" cellpadding="0" cellspacing="0">
        <thead>
            <tr class="headings">
                <th class="data-grid-th" style="width: 10px;"></th>
                <th class="data-grid-th">{$this->__('Shipping Service')} {$requeired}</th>
            </tr>
        </thead>
        <tbody>
            {$gridRowsHtml}
        </tbody>
    </table>
HTML;

            $form->addField(
                'products_grid',
                self::CUSTOM_CONTAINER,
                [
                    'text'      => $gridHtml,
                    'css_class' => 'm2epro-custom-container-full-width'
                ]
            );
        }

        $form->setUseContainer(true);
        $this->setForm($form);

        return $this;
    }

    //########################################

    protected function _prepareLayout()
    {
        $this->css->add(
            <<<CSS
    .radio-input {
        vertical-align: middle;
        text-align: center;
    }

    .shipping-service-prop {
        color: grey;
    }

    .shipping-service-details {
        font-size: 11px;
        margin-top: 5px;
    }

    .temporarily-unavailable-details {
        color: #860000;
    }

    .conditions-not-accepted-details {
        color: #825600;
    }
CSS
        );

        $this->js->add(
            <<<JS
    $$('.shipping-services-radio-input').invoke(
        'observe',
        'change',
        AmazonOrderMerchantFulfillmentObj.shippingServicesChange
    );
JS
        );

        return parent::_prepareLayout();
    }

    //########################################

    protected function _toHtml()
    {
        $helpBlock = $this->createBlock('HelpBlock');
        $helpBlock->setData(
            [
                'title'   => $this->__('Amazon\'s Shipping Services'),
                'content' => $this->__(
                    <<<HTML
<p>Amazon's Shipping Services offer a variety of <strong>Shipping Benefits</strong>, including several Shipping Options 
if you need to expedite your delivery.</p>
<br/>
<p>This Tool provides <strong>programmatic access</strong> to Amazon’s Shipping Services for Sellers, including 
competitive rates from Amazon-partnered Carriers. Sellers can find out what Shipping Service offers are available by 
<strong>submitting information</strong> about a proposed Shipment, such as <strong>Package Size</strong> and 
<strong>Weight</strong>, <strong>Shipment Origin</strong>, and <strong>Delivery Date</strong> requirements. Sellers 
can choose from the Shipping Service offers returned by Amazon, and then Purchase Shipping Labels for Fulfilling 
their Orders.</p>
<br/> 
<p>For more information about Amazon's Shipping Services Program, see the Seller Central Help.</p>
<br/>
<p>Amazon's Shipping Service tool is required to be used for Amazon Prime Orders.</p>
HTML
                )
            ]
        );

        $breadcrumb = $this->createBlock('Amazon_Order_MerchantFulfillment_Breadcrumb')
            ->setSelectedStep(2);

        return $helpBlock->toHtml() .
            $breadcrumb->toHtml() .
            parent::_toHtml();
    }

    //########################################
}
