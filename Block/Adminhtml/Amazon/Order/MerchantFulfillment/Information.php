<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Order\MerchantFulfillment;

use Ess\M2ePro\Helper\Component\Amazon\MerchantFulfillment;

/**
 * Class Ess\M2ePro\Block\Adminhtml\Amazon\Order\MerchantFulfillment\Information
 */
class Information extends \Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm
{
    /**
     * @var \Magento\Framework\Locale\CurrencyInterface
     */
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
        $this->setId('amazonOrderMerchantFulfillmentInformation');
    }

    //########################################

    protected function _prepareForm()
    {
        $fulfillmentData = $this->getData('fulfillment_details');

        $form = $this->_formFactory->create(
            [
                'data' => [
                    'id' => 'information_form',
                ]
            ]
        );

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
        } else {
            $fieldset = $form->addFieldset(
                'general_fieldset',
                [
                    'legend' => $this->__('General')
                ]
            );

            $statusHtml = '';

            switch ($fulfillmentData['status']) {
                case MerchantFulfillment::STATUS_PURCHASED:
                    $statusHtml = <<<HTML
<span style="color: green;">{$this->__('Purchased')}</span>
HTML;
                    break;
                case MerchantFulfillment::STATUS_REFUND_APPLIED:
                    $statusHtml = $this->__('Refund Applied');
                    break;
                case MerchantFulfillment::STATUS_REFUND_PENDING:
                    $statusHtml = $this->__('Refund Pending');
                    break;
                case MerchantFulfillment::STATUS_REFUND_REJECTED:
                    $statusHtml = $this->__('Refund Rejected');
                    break;

            }

            $statusBtnHtml = '';

            if ($fulfillmentData['status'] == MerchantFulfillment::STATUS_PURCHASED) {
                $statusBtnHtml = $this->createBlock('Magento\Button')->setData(
                    [
                        'label'   => $this->__('Cancel'),
                        'onclick' => 'AmazonOrderMerchantFulfillmentObj.cancelShippingOfferAction()',
                        'class'   => 'action-primary'
                    ]
                )->toHtml();
            } elseif ($this->getData('fulfillment_not_wizard') !== null) {
                $statusBtnHtml = $this->createBlock('Magento\Button')->setData(
                    [
                        'label'   => $this->__('Refresh'),
                        'onclick' => 'AmazonOrderMerchantFulfillmentObj.refreshDataAction()',
                        'class'   => 'action-primary'
                    ]
                )->toHtml();
            }

            $fieldset->addField(
                'status',
                self::CUSTOM_CONTAINER,
                [
                    'label' => $this->__('Status'),
                    'text'  => <<<HTML
<strong>{$statusHtml}</strong>&nbsp;{$statusBtnHtml}
HTML
                ]
            );

            $fieldset->addField(
                'carrier',
                'label',
                [
                    'label' => $this->__('Carrier'),
                    'value' => $fulfillmentData['shipping_service']['carrier_name']
                ]
            );

            $fieldset->addField(
                'service',
                'label',
                [
                    'label' => $this->__('Service'),
                    'value' => $fulfillmentData['shipping_service']['name']
                ]
            );

            $fieldset->addField(
                'tracking_number',
                'label',
                [
                    'label' => $this->__('Tracking Number'),
                    'value' => $fulfillmentData['tracking_id']
                ]
            );

            $fieldset->addField(
                'rate',
                'label',
                [
                    'label' => $this->__('Rate'),
                    'value' => $this->localeCurrency
                        ->getCurrency($fulfillmentData['shipping_service']['rate']['currency_code'])
                        ->toCurrency($fulfillmentData['shipping_service']['rate']['amount'])
                ]
            );

            $fieldset->addField(
                'ship_date',
                'label',
                [
                    'label' => $this->__('Ship Date'),
                    'value' => $this->_localeDate->formatDate(
                        $fulfillmentData['shipping_service']['date']['ship'],
                        \IntlDateFormatter::MEDIUM,
                        true
                    )
                ]
            );

            if ($fulfillmentData['shipping_service']['date']['estimated_delivery']['latest']) {
                $fieldset->addField(
                    'estimate_delivery_date',
                    'label',
                    [
                        'label' => $this->__('Estimate Delivery Date'),
                        'value' => $this->_localeDate->formatDate(
                            $fulfillmentData['shipping_service']['date']['estimated_delivery']['latest'],
                            \IntlDateFormatter::MEDIUM,
                            true
                        )
                    ]
                );
            }

            $fieldset->addField(
                'insurance',
                'label',
                [
                    'label' => $this->__('Insurance'),
                    'value' => $this->localeCurrency
                        ->getCurrency($fulfillmentData['insurance']['currency_code'])
                        ->toCurrency($fulfillmentData['insurance']['amount'])
                ]
            );

            $fieldset->addField(
                'creation_date',
                'label',
                [
                    'label' => $this->__('Creation Date'),
                    'value' => $this->_localeDate->formatDate(
                        $fulfillmentData['date']['created'],
                        \IntlDateFormatter::MEDIUM,
                        true
                    )
                ]
            );

            $fieldset = $form->addFieldset(
                'package_fieldset',
                [
                    'legend' => $this->__('Package')
                ]
            );

            $dimensionValue = '';

            if ($fulfillmentData['package']['predefined_dimensions']) {
                $predefinedPackageDimensions = $this->getHelper('Component_Amazon_MerchantFulfillment')
                    ->getPredefinedPackageDimensions();
                foreach ($predefinedPackageDimensions as $predefinedPackageDimensionGroup) {
                    foreach ($predefinedPackageDimensionGroup as $predefinedPackageDimensionCode =>
                             $predefinedPackageDimensionTitle) {
                        if ($fulfillmentData['package']['predefined_dimensions'] == $predefinedPackageDimensionCode) {
                            $dimensionValue = $predefinedPackageDimensionTitle;
                        }
                    }
                }
            } else {
                $dimensionValue = <<<HTML
{$fulfillmentData['package']['dimensions']['length']} x
{$fulfillmentData['package']['dimensions']['width']} x
{$fulfillmentData['package']['dimensions']['height']} 
HTML;
                if ($fulfillmentData['package']['dimensions']['unit_of_measure'] ==
                    MerchantFulfillment::DIMENSION_MEASURE_INCHES) {
                    $dimensionValue .= $this->__('in');
                } elseif ($fulfillmentData['package']['dimensions']['DIMENSION_MEASURE_CENTIMETERS'] ==
                    MerchantFulfillment::DIMENSION_MEASURE_INCHES) {
                    $dimensionValue .= $this->__('cm');
                } else {
                    $dimensionValue .= $fulfillmentData['package']['dimensions']['unit_of_measure'];
                }
            }

            $fieldset->addField(
                'dimension',
                'label',
                [
                    'label' => $this->__('Dimension'),
                    'value' => $dimensionValue
                ]
            );

            $weightValue = $fulfillmentData['package']['weight']['value'];

            if ($fulfillmentData['package']['weight']['unit_of_measure'] ==
                MerchantFulfillment::WEIGHT_MEASURE_OUNCES) {
                $weightValue .= $this->__('oz');
            } elseif ($fulfillmentData['package']['weight']['unit_of_measure'] ==
                MerchantFulfillment::WEIGHT_MEASURE_GRAMS) {
                $weightValue .= $this->__('g');
            } else {
                $weightValue .= $fulfillmentData['package']['weight']['unit_of_measure'];
            }

            $fieldset->addField(
                'weight',
                'label',
                [
                    'label' => $this->__('Weight'),
                    'value' => $weightValue
                ]
            );

            if (!empty($fulfillmentData['label'])) {
                $labelHtml = $this->createBlock('Magento\Button')->setData(
                    [
                        'label'   => $this->__('Print'),
                        'onclick' => 'AmazonOrderMerchantFulfillmentObj.getShippingLabelAction()',
                        'class'   => 'action-primary'
                    ]
                )->toHtml();
            } else {
                $labelHtml = $this->__('Label is Not Available');
            }

            $fieldset->addField(
                'fulfillment_label',
                self::CUSTOM_CONTAINER,
                [
                    'label' => $this->__('Label'),
                    'text'  => $labelHtml
                ]
            );

            $fieldset = $form->addFieldset(
                'products_fieldset',
                [
                    'legend' => $this->__('Products')
                ]
            );

            $productsHtml = '';

            foreach ($this->getOrderItems() as $orderItem) {
                $productsHtml .= <<<HTML
    <tr>
        <td>{$orderItem['title']}</td>
        <td>{$orderItem['sku']}</td>
        <td>{$orderItem['asin']}</td>
        <td>{$orderItem['qty']}</td>
        <td>{$this->localeCurrency->getCurrency($orderItem['currency'])->toCurrency($orderItem['price'])}</td>
    </tr>
HTML;
            }

            $html = <<<HTML
    <table class="border data-grid data-grid-not-hovered" cellpadding="0" cellspacing="0">
        <thead>
            <tr class="headings">
                <th class="data-grid-th">{$this->__('Title')}</th>
                <th class="data-grid-th" style="width: 150px;">{$this->__('SKU')}</th>
                <th class="data-grid-th" style="width: 150px;">{$this->__('ASIN')}</th>
                <th class="data-grid-th" style="width: 100px;">{$this->__('Quantity')}</th>
                <th class="data-grid-th" style="width: 200px;">{$this->__('Price')}</th>
            </tr>
        </thead>
        <tbody>
            {$productsHtml}
        </tbody>
    </table>
HTML;

            $fieldset->addField(
                'products_grid',
                self::CUSTOM_CONTAINER,
                [
                    'text'      => $html,
                    'css_class' => 'm2epro-custom-container-full-width'
                ]
            );

            $fieldset = $form->addFieldset(
                'ship_to_address_fieldset',
                [
                    'legend' => $this->__('Ship To Address')
                ]
            );

            $fieldset->addField(
                'name',
                'label',
                [
                    'label' => $this->__('Name'),
                    'value' => $fulfillmentData['address']['to']['info']['name']
                ]
            );

            $fieldset->addField(
                'country',
                'label',
                [
                    'label' => $this->__('Country'),
                    'value' => $this->getHelper('Magento')->getTranslatedCountryName(
                        $fulfillmentData['address']['to']['physical']['country']
                    )
                ]
            );

            if ($fulfillmentData['address']['to']['physical']['state'] !== null
                || $fulfillmentData['address']['to']['physical']['region'] !== null
            ) {
                $regionState = '';
                if ($fulfillmentData['address']['to']['physical']['state'] !== null) {
                    $regionState = $fulfillmentData['address']['to']['physical']['state'];
                } elseif ($fulfillmentData['address']['to']['physical']['region'] !== null) {
                    $regionState = $fulfillmentData['address']['to']['physical']['region'];
                }

                $fieldset->addField(
                    'region_state',
                    'label',
                    [
                        'label' => $this->__('Region/State'),
                        'value' => $regionState
                    ]
                );
            }

            $fieldset->addField(
                'city',
                'label',
                [
                    'label' => $this->__('City'),
                    'value' => $fulfillmentData['address']['to']['physical']['city']
                ]
            );

            $fieldset->addField(
                'street_address_1',
                'label',
                [
                    'label' => $this->__('Street Address'),
                    'value' => $fulfillmentData['address']['to']['physical']['address_1']
                ]
            );

            if ($fulfillmentData['address']['to']['physical']['address_2']) {
                $fieldset->addField(
                    'street_address_2',
                    'label',
                    [
                        'label' => '',
                        'value' => $fulfillmentData['address']['to']['physical']['address_2']
                    ]
                );
            }

            $fieldset->addField(
                'postal_code',
                'label',
                [
                    'label' => 'Postal Code',
                    'value' => $fulfillmentData['address']['to']['physical']['postal_code']
                ]
            );

            $fieldset = $form->addFieldset(
                'shipping_origin_fieldset',
                [
                    'legend' => $this->__('Shipping Origin')
                ]
            );

            $fieldset->addField(
                'from_name',
                'label',
                [
                    'label' => 'Name',
                    'value' => $fulfillmentData['address']['from']['info']['name']
                ]
            );

            if ($fulfillmentData['address']['from']['info']['email'] !== null) {
                $fieldset->addField(
                    'from_email',
                    'label',
                    [
                        'label' => 'Email',
                        'value' => $fulfillmentData['address']['from']['info']['email']
                    ]
                );
            }

            $fieldset->addField(
                'from_phone',
                'label',
                [
                    'label' => 'Phone',
                    'value' => $fulfillmentData['address']['from']['info']['phone']
                ]
            );

            $fieldset->addField(
                'from_country',
                'label',
                [
                    'label' => $this->__('Country'),
                    'value' => $this->getHelper('Magento')->getTranslatedCountryName(
                        $fulfillmentData['address']['from']['physical']['country']
                    )
                ]
            );

            if ($fulfillmentData['address']['from']['physical']['state'] !== null
                || $fulfillmentData['address']['from']['physical']['region'] !== null
            ) {
                $regionState = '';
                if ($fulfillmentData['address']['from']['physical']['state'] !== null) {
                    $regionState = $fulfillmentData['address']['from']['physical']['state'];
                } elseif ($fulfillmentData['address']['from']['physical']['region'] !== null) {
                    $regionState = $fulfillmentData['address']['from']['physical']['region'];
                }

                $fieldset->addField(
                    'from_region_state',
                    'label',
                    [
                        'label' => $this->__('Region/State'),
                        'value' => $regionState
                    ]
                );
            }

            $fieldset->addField(
                'from_city',
                'label',
                [
                    'label' => 'City',
                    'value' => $fulfillmentData['address']['from']['physical']['city']
                ]
            );

            $fieldset->addField(
                'from_street_address_1',
                'label',
                [
                    'label' => $this->__('Street Address'),
                    'value' => $fulfillmentData['address']['from']['physical']['address_1']
                ]
            );

            if ($fulfillmentData['address']['from']['physical']['address_2']) {
                $fieldset->addField(
                    'from_street_address_2',
                    'label',
                    [
                        'label' => '',
                        'value' => $fulfillmentData['address']['from']['physical']['address_2']
                    ]
                );
            }

            $fieldset->addField(
                'from_postal_code',
                'label',
                [
                    'label' => 'Postal Code',
                    'value' => $fulfillmentData['address']['from']['physical']['postal_code']
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
        $this->jsTranslator->add('Use Amazon\'s Shipping Services', $this->__('Use Amazon\'s Shipping Services'));

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
Below you can find Amazon’s Shipping Service information, such as e.g. Status, Carrier, Package Information,
Product Information, Ship to Address, and Shipping Origin.<br/>
Press '<strong>Cancel</strong>' button or '<strong>Refund</strong>' to annul the Shipping Settings.<br/>
Please, note you can repeat the action again after the Shipping Settings are canceled.<br/>
To <strong>update</strong> the Status you should press '<strong>Refresh</strong>' button.<br/>
Also, you can <strong>print the label</strong> by pressing '<strong>Print</strong>' button.
HTML
                )
            ]
        );

        $html = $helpBlock->toHtml();

        if ($this->getData('fulfillment_not_wizard') === null) {
            $breadcrumb = $this->createBlock('Amazon_Order_MerchantFulfillment_Breadcrumb')
                ->setSelectedStep(3);

            $html .= $breadcrumb->toHtml();
        }

        return $html . parent::_toHtml();
    }

    //########################################

    public function getOrderItems()
    {
        $data = [];

        foreach ($this->getData('order_items') as $parentOrderItem) {
            /**
             * @var $parentOrderItem \Ess\M2ePro\Model\Order\Item
             */
            $orderItem = $parentOrderItem->getChildObject();

            $data[] = [
                'title'    => $orderItem->getTitle(),
                'sku'      => $orderItem->getSku(),
                'asin'     => $orderItem->getGeneralId(),
                'qty'      => $orderItem->getQtyPurchased(),
                'price'    => $orderItem->getPrice(),
                'currency' => $orderItem->getCurrency(),
            ];
        }

        return $data;
    }

    //########################################
}
