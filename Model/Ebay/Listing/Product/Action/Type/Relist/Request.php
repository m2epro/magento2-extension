<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Listing\Product\Action\Type\Relist;

class Request extends \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Type\Request
{
    //########################################

    /**
     * @return array
     */
    public function getActionData()
    {
        $data = array_merge(
            array(
                'item_id' => $this->getEbayListingProduct()->getEbayItemIdReal()
            ),
            $this->getRequestVariations()->getRequestData()
        );

        if ($this->getConfigurator()->isGeneralAllowed()) {

            $data['sku'] = $this->getEbayListingProduct()->getSku();

            $data = array_merge(

                $data,

                $this->getRequestPayment()->getRequestData(),
                $this->getRequestReturn()->getRequestData()
            );
        }

        return array_merge(
            $data,
            $this->getRequestCategories()->getRequestData(),
            $this->getRequestShipping()->getRequestData(),
            $this->getRequestSelling()->getRequestData(),
            $this->getRequestDescription()->getRequestData()
        );
    }

    protected function prepareFinalData(array $data)
    {
        $data = $this->addConditionIfItIsNecessary($data);
        $data = $this->removeImagesIfThereAreNoChanges($data);
        return parent::prepareFinalData($data);
    }

    //########################################

    private function addConditionIfItIsNecessary(array $data)
    {
        $additionalData = $this->getListingProduct()->getAdditionalData();

        if (!isset($additionalData['is_need_relist_condition']) ||
            !$additionalData['is_need_relist_condition'] ||
            isset($data['item_condition'])) {
            return $data;
        }

        $data = array_merge($data, $this->getRequestDescription()->getConditionData());

        return $data;
    }

    //########################################
}