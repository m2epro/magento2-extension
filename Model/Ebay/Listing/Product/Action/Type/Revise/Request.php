<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Listing\Product\Action\Type\Revise;

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

    /**
     * @param array $data
     * @return array
     */
    protected function prepareFinalData(array $data)
    {
        $data = $this->processingReplacedAction($data);

        $data = $this->insertHasSaleFlagToVariations($data);
        $data = $this->removeImagesIfThereAreNoChanges($data);
        $data = $this->removeNodesIfItemHasTheSaleOrBid($data);
        $data = $this->removeDurationIfItCanNotBeChanged($data);

        return parent::prepareFinalData($data);
    }

    protected function afterBuildDataEvent(array $data)
    {
        $params = $this->getConfigurator()->getParams();

        if (!isset($params['replaced_action'])) {
            parent::afterBuildDataEvent($data);
            return;
        }

        if ($params['replaced_action'] == \Ess\M2ePro\Model\Listing\Product::ACTION_STOP) {

            $this->getConfigurator()->setPriority(
                \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Configurator::PRIORITY_REVISE_INSTEAD_OF_STOP
            );

        } elseif ($params['replaced_action'] == \Ess\M2ePro\Model\Listing\Product::ACTION_RELIST) {

            $this->getConfigurator()->setPriority(
                \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Configurator::PRIORITY_REVISE_INSTEAD_OF_RELIST
            );
        }

        parent::afterBuildDataEvent($data);
    }

    //########################################

    protected function insertOutOfStockControl(array $data)
    {
        $params = $this->getParams();

        $outOfStockControlCurrentState = $this->getEbayListingProduct()->getOutOfStockControl();
        $outOfStockControlTemplateState = $this->getEbayListingProduct()
                                               ->getEbaySellingFormatTemplate()
                                               ->getOutOfStockControl();

        if ($outOfStockControlCurrentState && !$outOfStockControlTemplateState) {

            // M2ePro_TRANSLATIONS
            // Although the Out of Stock Control option is disabled in Price, Quantity and Format Policy settings, for this eBay Item it is remain enabled. Disabling of the Out of Stock Control during the Revise action is not supported by eBay. That is why the Out of Stock Control option will still be enabled for this Item on eBay.
            $this->addWarningMessage(
                'Although the Out of Stock Control option is disabled in Price, Quantity and Format Policy settings,
                for this eBay Item it is remain enabled. Disabling of the Out of Stock Control during the Revise action
                is not supported by eBay. That is why the Out of Stock Control option will still be enabled for
                this Item on eBay.'
            );
        }

        $data['out_of_stock_control'] = $params['out_of_stock_control_current_state'];
        $data['out_of_stock_control_result'] = $params['out_of_stock_control_result'];

        return $data;
    }

    private function processingReplacedAction($data)
    {
        $params = $this->getConfigurator()->getParams();

        if (!isset($params['replaced_action'])) {
            return $data;
        }

        $this->insertReplacedActionMessage($params['replaced_action']);
        $data = $this->modifyQtyByReplacedAction($params['replaced_action'], $data);

        return $data;
    }

    private function insertReplacedActionMessage($replacedAction)
    {
        switch ($replacedAction) {

            case \Ess\M2ePro\Model\Listing\Product::ACTION_RELIST:

                $this->addWarningMessage(
                    'Revise was executed instead of Relist because \'Out Of Stock Control\' Option is enabled '.
                    'in the \'Price, Quantity and Format\' Policy'
                );

            break;

            case \Ess\M2ePro\Model\Listing\Product::ACTION_STOP:

                $this->addWarningMessage(
                    'Revise was executed instead of Stop because \'Out Of Stock Control\' Option is enabled '.
                    'in the \'Price, Quantity and Format\' Policy'
                );

            break;
        }
    }

    private function modifyQtyByReplacedAction($replacedAction, array $data)
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

    private function insertHasSaleFlagToVariations(array $data)
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

    private function removeNodesIfItemHasTheSaleOrBid(array $data)
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

            // M2ePro\TRANSLATIONS
            // %field_title% field(s) were ignored because eBay doesn't allow Revise the Item if it has sales, bids for Auction Type or less than 12 hours remain before the Item end.
            $this->addWarningMessage(
                $this->getHelper('Module\Translation')->__(
                    '%field_title% field(s) were ignored because eBay doesn\'t allow Revise the Item if it has sales, '.
                    'bids for Auction Type or less than 12 hours remain before the Item end.',
                    implode(', ', $warningMessageReasons)
                )
            );
        }

        return $data;
    }

    private function removeDurationIfItCanNotBeChanged(array $data)
    {
        if (isset($data['duration']) && isset($data['bestoffer_mode']) && $data['bestoffer_mode']) {

            // M2ePro\TRANSLATIONS
            // Duration field(s) was ignored because eBay doesn't allow Revise the Item if Best Offer is enabled.
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
                    'Duration value was not sent to eBay, because you are trying to change the Duration
                    of your Listing to \'Goot Till Cancelled\' which is not allowed by eBay.'
                )
            );
            unset($data['duration']);
        }

        return $data;
    }

    //########################################
}