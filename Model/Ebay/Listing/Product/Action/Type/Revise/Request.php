<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Listing\Product\Action\Type\Revise;

/**
 * Class \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Type\Revise\Request
 */
class Request extends \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Type\Request
{
    //########################################

    /**
     * @return array
     */
    public function getActionData()
    {
        $data = array_merge(
            [
                'item_id' => $this->getEbayListingProduct()->getEbayItemIdReal()
            ],
            $this->getGeneralData(),
            $this->getQtyData(),
            $this->getPriceData(),
            $this->getTitleData(),
            $this->getSubtitleData(),
            $this->getDescriptionData(),
            $this->getImagesData(),
            $this->getCategoriesData(),
            $this->getPartsData(),
            $this->getPaymentData(),
            $this->getReturnData(),
            $this->getShippingData(),
            $this->getVariationsData(),
            $this->getOtherData()
        );

        if ($this->getConfigurator()->isGeneralAllowed()) {
            $data['sku'] = $this->getSku();
        }

        return $data;
    }

    /**
     * @param array $data
     * @return array
     */
    protected function prepareFinalData(array $data)
    {
        $data = $this->processingReplacedAction($data);

        $data = $this->insertHasSaleFlagToVariations($data);
        $data = $this->removeNodesIfItemHasTheSaleOrBid($data);
        $data = $this->removeDurationIfItCanNotBeChanged($data);

        $data = $this->removePriceFromVariationsIfNotAllowed($data);

        return parent::prepareFinalData($data);
    }

    //########################################

    protected function processingReplacedAction($data)
    {
        $params = $this->getConfigurator()->getParams();

        if (!isset($params['replaced_action'])) {
            return $data;
        }

        $this->insertReplacedActionMessage($params['replaced_action']);
        $data = $this->modifyQtyByReplacedAction($params['replaced_action'], $data);

        return $data;
    }

    protected function insertReplacedActionMessage($replacedAction)
    {
        switch ($replacedAction) {
            case \Ess\M2ePro\Model\Listing\Product::ACTION_RELIST:
                $this->addWarningMessage(
                    'Revise was executed instead of Relist because \'Out Of Stock Control\' Option is enabled '.
                    'for this item.'
                );

                break;

            case \Ess\M2ePro\Model\Listing\Product::ACTION_STOP:
                $this->addWarningMessage(
                    'Revise was executed instead of Stop because \'Out Of Stock Control\' Option is enabled '.
                    'for this item.'
                );

                break;
        }
    }

    protected function modifyQtyByReplacedAction($replacedAction, array $data)
    {
        if ($replacedAction != \Ess\M2ePro\Model\Listing\Product::ACTION_STOP) {
            return $data;
        }

        if (!$this->getIsVariationItem()) {
            $data['qty'] = 0;
            return $data;
        }

        if (!isset($data['variation']) || !is_array($data['variation'])) {
            return $data;
        }

        foreach ($data['variation'] as &$variation) {
            $variation['qty'] = 0;
        }

        return $data;
    }

    // ---------------------------------------

    protected function insertHasSaleFlagToVariations(array $data)
    {
        if (!isset($data['variation']) || !is_array($data['variation'])) {
            return $data;
        }

        foreach ($data['variation'] as &$variation) {
            if (!empty($variation['delete']) && isset($variation['qty']) && (int)$variation['qty'] <= 0) {

                /** @var \Ess\M2ePro\Model\Ebay\Listing\Product\Variation $ebayVariation */
                $ebayVariation = $variation['_instance_']->getChildObject();

                if ($ebayVariation->getOnlineQtySold() || $ebayVariation->hasSales()) {
                    $variation['has_sales'] = true;
                }
            }
        }

        return $data;
    }

    protected function removeNodesIfItemHasTheSaleOrBid(array $data)
    {
        if (!isset($data['title']) && !isset($data['subtitle']) &&
            !isset($data['duration']) && !isset($data['is_private'])) {
            return $data;
        }

        $deleteByAuctionFlag = $this->getEbayListingProduct()->isListingTypeAuction() &&
                               $this->getEbayListingProduct()->getOnlineBids() > 0;

        $deleteByFixedFlag = $this->getEbayListingProduct()->isListingTypeFixed() &&
                             $this->getEbayListingProduct()->getOnlineQtySold() > 0;

        if (isset($data['title']) && $deleteByAuctionFlag) {
            $warningMessageReasons[] = $this->getHelper('Module\Translation')->__('Title');
            unset($data['title']);
        }

        if (isset($data['subtitle']) && $deleteByAuctionFlag) {
            $warningMessageReasons[] = $this->getHelper('Module\Translation')->__('Subtitle');
            unset($data['subtitle']);
        }

        if (isset($data['duration']) && ($deleteByAuctionFlag || $deleteByFixedFlag)) {
            $warningMessageReasons[] = $this->getHelper('Module\Translation')->__('Duration');
            unset($data['duration']);
        }

        if (isset($data['is_private']) && ($deleteByAuctionFlag || $deleteByFixedFlag)) {
            $warningMessageReasons[] = $this->getHelper('Module\Translation')->__('Private Listing');
            unset($data['is_private']);
        }

        if (!empty($warningMessageReasons)) {
            $this->addWarningMessage(
                $this->getHelper('Module\Translation')->__(
                    '%field_title% field(s) were ignored because eBay doesn\'t allow Revise the Item if it has ' .
                    'sales, bids for Auction Type or less than 12 hours remain before the Item end.',
                    implode(', ', $warningMessageReasons)
                )
            );
        }

        return $data;
    }

    protected function removeDurationIfItCanNotBeChanged(array $data)
    {
        if (isset($data['duration']) && isset($data['bestoffer_mode']) && $data['bestoffer_mode']) {
            $this->addWarningMessage(
                $this->getHelper('Module\Translation')->__(
                    'Duration field(s) was ignored because '.
                    'eBay doesn\'t allow Revise the Item if Best Offer is enabled.'
                )
            );
            unset($data['duration']);
        }

        if (isset($data['duration']) && $data['duration'] == \Ess\M2ePro\Helper\Component\Ebay::LISTING_DURATION_GTC &&
            $this->getEbayListingProduct()->getOnlineDuration() &&
            !$this->getEbayListingProduct()->isOnlineDurationGtc()) {
            $this->addWarningMessage(
                $this->getHelper('Module\Translation')->__(
                    'Duration value was not sent to eBay, because you are trying to change the Duration of your
                    Listing to \'Goot Till Cancelled\' which is not allowed by eBay.'
                )
            );
            unset($data['duration']);
        }

        return $data;
    }

    //########################################
}
