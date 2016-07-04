<?php

namespace Ess\M2ePro\Controller\Adminhtml\Order;

use Ess\M2ePro\Controller\Adminhtml\Context;
use Ess\M2ePro\Controller\Adminhtml\Order;

class GetCountryRegions extends Order
{
    protected $regionCollection;

    public function __construct(
        \Magento\Directory\Model\ResourceModel\Region\Collection $regionCollection,
        Context $context
    )
    {
        $this->regionCollection = $regionCollection;

        parent::__construct($context);
    }

    public function execute()
    {
        $country = $this->getRequest()->getParam('country');
        $regions = array();

        if (!empty($country)) {
            $regionsCollection = $this->regionCollection
                ->addCountryFilter($country)
                ->load();

            foreach ($regionsCollection as $region) {
                $regions[] = array(
                    'id'    => $region->getData('region_id'),
                    'value' => $region->getData('code'),
                    'label' => $region->getData('default_name')
                );
            }

            if (count($regions) > 0) {
                array_unshift($regions, array(
                    'value' => '',
                    'label' => $this->__('-- Please select --')
                ));
            }
        }

        $this->setJsonContent($regions);

        return $this->getResult();
    }
}