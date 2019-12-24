<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Account\PickupStore;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Ebay\Account\PickupStore\ValidateLocation
 */
class ValidateLocation extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Account
{
    //########################################

    public function execute()
    {
        $locationData = [
            'country',
            'region',
            'city',
            'address_1',
            'address_2',
            'postal_code',
            'latitude',
            'longitude',
            'utc_offset',
        ];

        $pickupStoreCollection = $this->activeRecordFactory->getObject('Ebay_Account_PickupStore')
                                      ->getCollection();

        $idValue = (int)$this->getRequest()->getParam('id', 0);
        if (!empty($idValue)) {
            $pickupStoreCollection->addFieldToFilter('id', ['nin'=>[$idValue]]);
        }

        foreach ($locationData as $locationItem) {
            $tempField = $this->getRequest()->getParam($locationItem, '');
            if (!empty($tempField)) {
                if ($locationItem == 'latitude' || $locationItem == 'longitude') {
                    $pickupStoreCollection->addFieldToFilter($locationItem, ['like' => $tempField]);
                    continue;
                }

                $pickupStoreCollection->addFieldToFilter($locationItem, $tempField);
            }
        }

        $this->setJsonContent(['result'=>!(bool)$pickupStoreCollection->getSize()]);
        return $this->getResult();
    }

    //########################################
}
