<?php

namespace Ess\M2ePro\Block\Adminhtml\Order;

use Ess\M2ePro\Block\Adminhtml\Magento\AbstractContainer;

class Debug extends AbstractContainer
{
    protected $_template = 'order/debug.phtml';

    protected $taxCalculator;
    protected $taxModel;
    protected $storeModel;

    public function __construct(
        \Magento\Tax\Model\Calculation $taxCalculator,
        \Magento\Tax\Model\ClassModel $taxModel,
        \Magento\Store\Model\Store $storeModel,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Widget $context,
        array $data = []
    )
    {
        $this->taxCalculator = $taxCalculator;
        $this->taxModel = $taxModel;
        $this->storeModel = $storeModel;

        parent::__construct($context, $data);
    }

    protected function _beforeToHtml()
    {
        /** @var $order \Ess\M2ePro\Model\Order */
        $order = $this->getHelper('Data\GlobalData')->getValue('order');

        $store = $this->storeModel->load($order->getStoreId());

        if (!is_null($store->getId())) {
            $this->setData(
                'store_tax_calculation_algorithm',
                $store->getConfig(\Magento\Tax\Model\Config::XML_PATH_ALGORITHM)
            );
            $this->setData(
                'store_tax_calculation_based_on',
                $store->getConfig(\Magento\Tax\Model\Config::CONFIG_XML_PATH_BASED_ON)
            );
            $this->setData(
                'store_price_includes_tax',
                $store->getConfig(\Magento\Tax\Model\Config::CONFIG_XML_PATH_PRICE_INCLUDES_TAX)
            );
            $this->setData(
                'store_shipping_price_includes_tax',
                $store->getConfig(\Magento\Tax\Model\Config::CONFIG_XML_PATH_SHIPPING_INCLUDES_TAX)
            );

            $taxClass = $this->taxModel->load(
                $store->getConfig(\Magento\Tax\Model\Config::CONFIG_XML_PATH_SHIPPING_TAX_CLASS)
            );
            $this->setData('store_shipping_tax_class', $taxClass->getClassName());

            // ---------------------------------------
            $request = new \Magento\Framework\DataObject([
                'product_class_id' => $store->getConfig(\Magento\Tax\Model\Config::CONFIG_XML_PATH_SHIPPING_TAX_CLASS)
            ]);

            $this->setData('store_shipping_tax_rate', $this->taxCalculator->getStoreRate($request, $store));
            // ---------------------------------------
        }

        $this->js->add('require(["M2ePro/Order/Debug"]);');
    }
}