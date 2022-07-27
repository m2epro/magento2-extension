<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Order;

class GetCountryRegions extends \Ess\M2ePro\Controller\Adminhtml\Order
{
    /** @var \Magento\Directory\Model\ResourceModel\Region\Collection */
    private $regionCollection;

    public function __construct(
        \Magento\Directory\Model\ResourceModel\Region\Collection $regionCollection,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($context);

        $this->regionCollection = $regionCollection;
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
