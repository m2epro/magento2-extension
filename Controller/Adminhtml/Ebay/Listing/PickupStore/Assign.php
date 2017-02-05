<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\PickupStore;

class Assign extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\PickupStore
{
    //########################################

    public function execute()
    {
        $productsIds = $this->getRequest()->getParam('products_ids');
        $storesIds = $this->getRequest()->getParam('stores_ids');

        if (empty($productsIds) || empty($storesIds)) {
            $this->setAjaxContent($this->getHelper('Data')->jsonEncode([
                'messages' => [[
                    'type' => 'error',
                    'text' => $this->__('You should provide correct parameters.')
                ]]
            ]), false);
            return $this->getResult();
        }

        !is_array($productsIds) && $productsIds = explode(',', $productsIds);
        !is_array($storesIds) && $storesIds = explode(',', $storesIds);

        $messages = [];
        if (empty($productsIds) || empty($storesIds)) {
            $messages[] = [
                'type' => 'warning',
                'text' => $this->__('Stores cannot be assigned')
            ];
        } else {
            $this->activeRecordFactory->getObject('Ebay\Listing\Product\PickupStore')
                                      ->getResource()->assignProductsToStores($productsIds, $storesIds);

            $messages[] = array(
                'type' => 'success',
                'text' => $this->__('Stores have been successfully assigned.')
            );
        }

        $this->setAjaxContent($this->getHelper('Data')->jsonEncode([
            'messages' => $messages
        ]), false);
        return $this->getResult();
    }

    //########################################
}