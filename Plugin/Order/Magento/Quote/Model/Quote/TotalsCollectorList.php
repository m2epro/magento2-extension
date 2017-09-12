<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Plugin\Order\Magento\Quote\Model\Quote;

class TotalsCollectorList extends \Ess\M2ePro\Plugin\AbstractPlugin
{
    /**
     * @var \Magento\Quote\Model\Quote\Address\Total\CollectorFactory
     */
    protected $totalCollectorFactory;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    protected $collectorsByStores = [];

    //########################################

    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Quote\Model\Quote\Address\Total\CollectorFactory $totalCollectorFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory
    )
    {
        $this->totalCollectorFactory = $totalCollectorFactory;
        $this->storeManager          = $storeManager;

        parent::__construct($helperFactory, $modelFactory);
    }

    //########################################

    public function aroundGetCollectors($interceptor, \Closure $callback, ...$arguments)
    {
        return $this->execute('getCollectors', $interceptor, $callback, $arguments);
    }

    // ---------------------------------------

    /**
     * \Magento\Quote\Model\Quote\TotalsCollectorList may cache the totalCollector for the incorrect Store View
     * @param \Magento\Quote\Model\Quote\TotalsCollectorList $interceptor
     * @param \Closure $callback
     * @param array $arguments
     * @return mixed
     */
    protected function processGetCollectors($interceptor, \Closure $callback, array $arguments)
    {
        $storeId = isset($arguments[0]) ? $arguments[0] : NULL;

        if (is_null($storeId)) {
            return $callback(...$arguments);
        }

        if (empty($this->collectorsByStores[$storeId])) {

            /** @var \Magento\Quote\Model\Quote\Address\Total\Collector $totalCollector */
            $totalCollector = $this->totalCollectorFactory->create(
                ['store' => $this->storeManager->getStore($storeId)]
            );

            $this->collectorsByStores[$storeId] = $totalCollector->getCollectors();
        }

        return $this->collectorsByStores[$storeId];
    }

    //########################################
}