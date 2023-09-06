<?php

namespace Ess\M2ePro\Block\Adminhtml\Amazon\ShippingMap\Edit;

class Form extends \Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm
{
    /** @var \Ess\M2ePro\Helper\Component\Amazon $amazonHelper */
    protected $amazonHelper;
    /** @var \Magento\Shipping\Model\Config  */
    protected $shippingConfig;
    /** @var \Ess\M2ePro\Model\ResourceModel\Amazon\ShippingMap\CollectionFactory */
    protected $amazonShippingMapCollectionFactory;
    protected $shippingMapData = [];

    public function __construct(
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Shipping\Model\Config $shippingConfig,
        \Ess\M2ePro\Helper\Component\Amazon $amazonHelper,
        \Ess\M2ePro\Model\ResourceModel\Amazon\ShippingMap\CollectionFactory $amazonShippingMapCollectionFactory,
        array $data = []
    ) {
        $this->amazonShippingMapCollectionFactory = $amazonShippingMapCollectionFactory;
        $this->amazonHelper = $amazonHelper;
        $this->shippingConfig = $shippingConfig;

        parent::__construct($context, $registry, $formFactory, $data);
    }

    protected function _prepareForm()
    {
        $this->loadShippingMapData();
        $form = $this->_formFactory->create([
            'data' => [
                'id' => 'edit_form',
                'method' => 'post',
                'action' => $this->getUrl('*/*/save'),
                'enctype' => 'multipart/form-data',
            ],
        ]);
        $mappingData = [
            \Ess\M2ePro\Model\Amazon\ShippingMap\AmazonShippingMap::STANDARD => ['magento_code' => ''],
            \Ess\M2ePro\Model\Amazon\ShippingMap\AmazonShippingMap::FREE_ECONOMY => ['magento_code' => ''],
            \Ess\M2ePro\Model\Amazon\ShippingMap\AmazonShippingMap::EXPEDITED => ['magento_code' => ''],
            \Ess\M2ePro\Model\Amazon\ShippingMap\AmazonShippingMap::NEXT_DAY => ['magento_code' => ''],
            \Ess\M2ePro\Model\Amazon\ShippingMap\AmazonShippingMap::SAME_DAY => ['magento_code' => ''],
            \Ess\M2ePro\Model\Amazon\ShippingMap\AmazonShippingMap::SECOND_DAY => ['magento_code' => ''],
            \Ess\M2ePro\Model\Amazon\ShippingMap\AmazonShippingMap::SCHEDULED => ['magento_code' => ''],
        ];
        $shippingMethodsOptions = $this->getShippingMethodsOptions();

        foreach ($this->amazonHelper->getMarketplacesListByActiveAccounts() as $marketplaceId => $marketplaceTitle) {
            $fieldsetMarketplace = $form->addFieldset(
                'marketplace_' . $marketplaceId,
                [
                    'legend' => __($marketplaceTitle),
                    'class' => 'fieldset-wide',
                    'collapsable' => true,
                    'open' => false
                ]
            );

            foreach ([\Ess\M2ePro\Model\Amazon\ShippingMap\AmazonShippingMap::DOMESTIC, \Ess\M2ePro\Model\Amazon\ShippingMap\AmazonShippingMap::INTERNATIONAL] as $location) {
                $fieldsetLocation = $fieldsetMarketplace->addFieldset(
                    'location_' . $marketplaceId . '_' . $location,
                    [
                        'legend' => __($location),
                        'collapsable' => false,
                    ]
                );

                foreach ($mappingData as $amazonCode => $map) {
                    $mapValue = $this->getSavedMagentoCode($amazonCode, $marketplaceId, $location);
                    $fieldsetLocation->addField(
                        'magento_shipping_' . $marketplaceId . '_' . $location . '_' . $amazonCode,
                        'select',
                        [
                            'name' => 'amazon_shipping[' . $marketplaceId . '][' . $location . '][' . $amazonCode . '][magento_code]',
                            'label' => __($amazonCode),
                            'title' => __($amazonCode),
                            'value' => $mapValue,
                            'values' => $shippingMethodsOptions,
                        ]
                    );
                }
            }
        }

        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }

    public function _toHtml()
    {
        $this->js->addOnReadyJs(
            <<<JS
            require([
                'M2ePro/Amazon/ShippingMap/ShippingMap'
            ])
JS
        );
        return parent::_toHtml();
    }

    protected function getShippingMethodsOptions(): array
    {
        $options = [];
        $m2eproShippingOption = null;
        $shippingMethods = $this->shippingConfig->getActiveCarriers();
        foreach ($shippingMethods as $carrierCode => $carrierModel) {
            $carrierTitle = $carrierModel->getConfigData('title');
            $carrierMethods = $carrierModel->getAllowedMethods();

            foreach ($carrierMethods as $methodCode => $methodTitle) {
                $value = sprintf('%s_%s', $carrierCode, $methodCode);

                $option = [
                    'value' => $value,
                    'label' => $carrierTitle . ' - ' . $methodTitle,
                ];

                if ($value === \Ess\M2ePro\Model\Order\ProxyObject::DEFAULT_SHIPPING_CODE) {
                    $m2eproShippingOption = $option;
                } else {
                    $options[] = $option;
                }
            }
        }

        if ($m2eproShippingOption !== null) {
            array_unshift($options, $m2eproShippingOption);
        }

        return $options;
    }

    protected function loadShippingMapData()
    {
        $collection = $this->amazonShippingMapCollectionFactory->create();
        $data = $collection->getData();
        foreach ($data as $row) {
            $this->shippingMapData[$row['amazon_code']][$row['marketplace_id']][$row['location']] = $row['magento_code'];
        }
    }

    protected function getSavedMagentoCode($amazonCode, $marketplaceId, $location)
    {
        return $this->shippingMapData[$amazonCode][$marketplaceId][$location] ?? '';
    }
}
