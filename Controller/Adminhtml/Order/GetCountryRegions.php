<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Order;

use Ess\M2ePro\Controller\Adminhtml\Context;
use Ess\M2ePro\Controller\Adminhtml\Order;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Order\GetCountryRegions
 */
class GetCountryRegions extends Order
{
    protected $regionCollection;

    public function __construct(
        \Magento\Directory\Model\ResourceModel\Region\Collection $regionCollection,
        Context $context
    ) {
        $this->regionCollection = $regionCollection;

        parent::__construct($context);
    }

    public function execute()
    {
        $country = $this->getRequest()->getParam('country');
        $regions = [];

        if (!empty($country)) {
            $regionsCollection = $this->regionCollection
                ->addCountryFilter($country)
                ->load();

            foreach ($regionsCollection as $region) {
                $regions[] = [
                    'id'    => $region->getData('region_id'),
                    'value' => $region->getData('code'),
                    'label' => $region->getData('default_name')
                ];
            }

            if (!empty($regions)) {
                array_unshift($regions, [
                    'value' => '',
                    'label' => $this->__('-- Please select --')
                ]);
            }
        }

        $this->setJsonContent($regions);

        return $this->getResult();
    }
}
