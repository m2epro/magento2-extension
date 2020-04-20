<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Order;

use Ess\M2ePro\Block\Adminhtml\Magento\AbstractContainer;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Order\Debug
 */
class Debug extends AbstractContainer
{
    protected $_template = 'order/debug.phtml';

    protected $taxCalculator;
    protected $taxModel;
    protected $storeModel;
    protected $quoteManager;

    public function __construct(
        \Magento\Tax\Model\Calculation $taxCalculator,
        \Magento\Tax\Model\ClassModel $taxModel,
        \Magento\Store\Model\Store $storeModel,
        \Ess\M2ePro\Model\Magento\Quote\Manager $quoteManager,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Widget $context,
        array $data = []
    ) {
        $this->taxCalculator = $taxCalculator;
        $this->taxModel = $taxModel;
        $this->storeModel = $storeModel;
        $this->quoteManager = $quoteManager;

        parent::__construct($context, $data);
    }

    protected function _beforeToHtml()
    {
        /** @var $order \Ess\M2ePro\Model\Order */
        $order = $this->getHelper('Data\GlobalData')->getValue('order');
        $store = $this->storeModel->load($order->getStoreId());

        /** @var \Ess\M2ePro\Model\Magento\Quote\Store\Configurator $storeConfigurator */
        $storeConfigurator = $this->modelFactory->getObject(
            'Magento_Quote_Store_Configurator',
            ['quote' => $this->quoteManager->getBlankQuote(), 'proxyOrder' => $order->getProxy()]
        );

        $this->setData(
            'product_price_includes_tax',
            $storeConfigurator->isPriceIncludesTax()
        );
        $this->setData(
            'shipping_price_includes_tax',
            $storeConfigurator->isShippingPriceIncludesTax()
        );
        $this->setData(
            'store_shipping_tax_class',
            $storeConfigurator->getShippingTaxClassId()
        );
        $this->setData(
            'store_tax_calculation_based_on',
            $storeConfigurator->getTaxCalculationBasedOn()
        );

        if ($store->getId() !== null) {
            $this->setData(
                'store_tax_calculation_algorithm',
                $store->getConfig(\Magento\Tax\Model\Config::XML_PATH_ALGORITHM)
            );

            // ---------------------------------------
            $request = new \Magento\Framework\DataObject([
                'product_class_id' => $store->getConfig(\Magento\Tax\Model\Config::CONFIG_XML_PATH_SHIPPING_TAX_CLASS)
            ]);

            $this->setData('store_shipping_tax_rate', $this->taxCalculator->getStoreRate($request, $store));
            // ---------------------------------------
        }
    }
}
