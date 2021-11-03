<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Listing\Product\Action\Type\Relist;

/**
 * Class \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Type\Relist\Request
 */
class Request extends \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Type\Request
{
    //########################################

    protected function beforeBuildDataEvent()
    {
        parent::beforeBuildDataEvent();

        $additionalData = $this->getListingProduct()->getAdditionalData();

        unset($additionalData['synch_template_list_rules_note']);
        unset($additionalData['item_duplicate_action_required']);

        $this->getListingProduct()->setSettings('additional_data', $additionalData);
        $this->getEbayListingProduct()->setData('is_duplicate', 0);

        $this->getListingProduct()->save();
    }

    //########################################

    /**
     * @return array
     */
    public function getActionData()
    {
        if (!$uuid = $this->getEbayListingProduct()->getItemUUID()) {
            $uuid = $this->getEbayListingProduct()->generateItemUUID();
            $this->getEbayListingProduct()->setData('item_uuid', $uuid)->save();
        }

        $data = array_merge(
            [
                'item_id'   => $this->getEbayListingProduct()->getEbayItemIdReal(),
                'item_uuid' => $uuid
            ],
            $this->getQtyData(),
            $this->getPriceData(),
            $this->getCategoriesData(),
            $this->getPartsData(),
            $this->getVariationsData()
        );

        if ($this->getConfigurator()->isGeneralAllowed()) {
            $data['sku'] = $this->getSku();
        }

        return $data;
    }

    protected function prepareFinalData(array $data)
    {
        $data = $this->addConditionIfItIsNecessary($data);
        $data = $this->removePriceFromVariationsIfNotAllowed($data);

        return parent::prepareFinalData($data);
    }

    //########################################

    protected function addConditionIfItIsNecessary(array $data)
    {
        $additionalData = $this->getListingProduct()->getAdditionalData();

        if (!isset($additionalData['is_need_relist_condition']) ||
            !$additionalData['is_need_relist_condition'] ||
            isset($data['item_condition'])) {
            return $data;
        }

        $otherData = $this->getOtherData();

        $data['item_condition'] = $otherData['item_condition'];

        return $data;
    }

    //########################################
}
