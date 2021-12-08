<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Listing;

/**
 * @method \Ess\M2ePro\Model\ResourceModel\Listing\Log getResource()
 */

/**
 * @method \Ess\M2ePro\Model\ResourceModel\Listing\Log\Collection getCollection()
 */
class Log extends \Ess\M2ePro\Model\Log\AbstractModel
{
    const ACTION_UNKNOWN  = 1;
    const _ACTION_UNKNOWN = 'System';

    const ACTION_ADD_LISTING     = 2;
    const _ACTION_ADD_LISTING    = 'Add new Listing';
    const ACTION_DELETE_LISTING  = 3;
    const _ACTION_DELETE_LISTING = 'Delete existing Listing';

    const ACTION_ADD_PRODUCT_TO_LISTING       = 4;
    const _ACTION_ADD_PRODUCT_TO_LISTING      = 'Add Product to Listing';
    const ACTION_DELETE_PRODUCT_FROM_LISTING  = 5;
    const _ACTION_DELETE_PRODUCT_FROM_LISTING = 'Delete Product from Listing';

    const ACTION_ADD_NEW_CHILD_LISTING_PRODUCT  = 35;
    const _ACTION_ADD_NEW_CHILD_LISTING_PRODUCT = 'Add New Child Product';

    const ACTION_ADD_PRODUCT_TO_MAGENTO       = 6;
    const _ACTION_ADD_PRODUCT_TO_MAGENTO      = 'Add new Product to Magento Store';
    const ACTION_DELETE_PRODUCT_FROM_MAGENTO  = 7;
    const _ACTION_DELETE_PRODUCT_FROM_MAGENTO = 'Delete existing Product from Magento Store';

    const ACTION_CHANGE_PRODUCT_PRICE               = 8;
    const _ACTION_CHANGE_PRODUCT_PRICE              = 'Change of Product Price in Magento Store';
    const ACTION_CHANGE_PRODUCT_SPECIAL_PRICE       = 9;
    const _ACTION_CHANGE_PRODUCT_SPECIAL_PRICE      = 'Change of Product Special Price in Magento Store';
    const ACTION_CHANGE_PRODUCT_QTY                 = 10;
    const _ACTION_CHANGE_PRODUCT_QTY                = 'Change of Product QTY in Magento Store';
    const ACTION_CHANGE_PRODUCT_STOCK_AVAILABILITY  = 11;
    const _ACTION_CHANGE_PRODUCT_STOCK_AVAILABILITY = 'Change of Product Stock availability in Magento Store';
    const ACTION_CHANGE_PRODUCT_STATUS              = 12;
    const _ACTION_CHANGE_PRODUCT_STATUS             = 'Change of Product status in Magento Store';

    const ACTION_LIST_PRODUCT_ON_COMPONENT      = 13;
    const _ACTION_LIST_PRODUCT_ON_COMPONENT     = 'List Product on Channel';
    const ACTION_RELIST_PRODUCT_ON_COMPONENT    = 14;
    const _ACTION_RELIST_PRODUCT_ON_COMPONENT   = 'Relist Product on Channel';
    const ACTION_REVISE_PRODUCT_ON_COMPONENT    = 15;
    const _ACTION_REVISE_PRODUCT_ON_COMPONENT   = 'Revise Product on Channel';
    const ACTION_STOP_PRODUCT_ON_COMPONENT      = 16;
    const _ACTION_STOP_PRODUCT_ON_COMPONENT     = 'Stop Product on Channel';
    const ACTION_DELETE_PRODUCT_FROM_COMPONENT  = 24;
    const _ACTION_DELETE_PRODUCT_FROM_COMPONENT = 'Remove Product from Channel';
    const ACTION_STOP_AND_REMOVE_PRODUCT        = 17;
    const _ACTION_STOP_AND_REMOVE_PRODUCT       = 'Stop on Channel / Remove from Listing';
    const ACTION_DELETE_AND_REMOVE_PRODUCT      = 23;
    const _ACTION_DELETE_AND_REMOVE_PRODUCT     = 'Remove from Channel & Listing';
    const ACTION_SWITCH_TO_AFN_ON_COMPONENT     = 29;
    const _ACTION_SWITCH_TO_AFN_ON_COMPONENT    = 'Switching Fulfillment to AFN';
    const ACTION_SWITCH_TO_MFN_ON_COMPONENT     = 30;
    const _ACTION_SWITCH_TO_MFN_ON_COMPONENT    = 'Switching Fulfillment to MFN';
    const ACTION_RESET_BLOCKED_PRODUCT          = 32;
    const _ACTION_RESET_BLOCKED_PRODUCT         = 'Reset Inactive (Blocked) Item';

    const ACTION_CHANGE_PRODUCT_SPECIAL_PRICE_FROM_DATE  = 19;
    const _ACTION_CHANGE_PRODUCT_SPECIAL_PRICE_FROM_DATE = 'Change of Product Special Price from date in Magento Store';

    const ACTION_CHANGE_PRODUCT_SPECIAL_PRICE_TO_DATE  = 20;
    const _ACTION_CHANGE_PRODUCT_SPECIAL_PRICE_TO_DATE = 'Change of Product Special Price to date in Magento Store';

    const ACTION_CHANGE_CUSTOM_ATTRIBUTE  = 18;
    const _ACTION_CHANGE_CUSTOM_ATTRIBUTE = 'Change of Product Custom Attribute in Magento Store';

    const ACTION_CHANGE_PRODUCT_TIER_PRICE  = 31;
    const _ACTION_CHANGE_PRODUCT_TIER_PRICE = 'Change of Product Tier Price in Magento Store';

    const ACTION_MOVE_TO_LISTING  = 21;
    const _ACTION_MOVE_TO_LISTING = 'Move to another Listing';

    const ACTION_MOVE_FROM_OTHER_LISTING  = 22;
    const _ACTION_MOVE_FROM_OTHER_LISTING = 'Move from Unmanaged Listing';

    const ACTION_SELL_ON_ANOTHER_SITE  = 33;
    const _ACTION_SELL_ON_ANOTHER_SITE = 'Sell On Another Marketplace';

    const ACTION_CHANNEL_CHANGE  = 25;
    const _ACTION_CHANNEL_CHANGE = 'External Change';

    const ACTION_REMAP_LISTING_PRODUCT = 34;
    const _ACTION_REMAP_LISTING_PRODUCT = 'Relink';

    //########################################

    public function _construct()
    {
        parent::_construct();
        $this->_init('Ess\M2ePro\Model\ResourceModel\Listing\Log');
    }

    //########################################

    public function addListingMessage(
        $listingId,
        $initiator = \Ess\M2ePro\Helper\Data::INITIATOR_UNKNOWN,
        $actionId = null,
        $action = null,
        $description = null,
        $type = null,
        array $additionalData = []
    ) {
        $dataForAdd = $this->makeDataForAdd(
            $listingId,
            $initiator,
            null,
            null,
            $actionId,
            $action,
            $description,
            $type,
            $additionalData
        );

        $this->createMessage($dataForAdd);
    }

    public function addProductMessage(
        $listingId,
        $productId,
        $listingProductId,
        $initiator = \Ess\M2ePro\Helper\Data::INITIATOR_UNKNOWN,
        $actionId = null,
        $action = null,
        $description = null,
        $type = null,
        array $additionalData = []
    ) {
        $dataForAdd = $this->makeDataForAdd(
            $listingId,
            $initiator,
            $productId,
            $listingProductId,
            $actionId,
            $action,
            $description,
            $type,
            $additionalData
        );

        $this->createMessage($dataForAdd);
    }

    // ---------------------------------------

    public function clearMessages($listingId = null)
    {
        $filters = [];

        if ($listingId !== null) {
            $filters['listing_id'] = $listingId;
        }
        if ($this->componentMode !== null) {
            $filters['component_mode'] = $this->componentMode;
        }

        $this->getResource()->clearMessages($filters);
    }

    //########################################

    protected function createMessage($dataForAdd)
    {
        /** @var \Ess\M2ePro\Model\Listing $listing */
        $listing = $this->parentFactory->getCachedObjectLoaded(
            $this->getComponentMode(),
            'Listing',
            $dataForAdd['listing_id']
        );

        $dataForAdd['account_id'] = $listing->getAccountId();
        $dataForAdd['marketplace_id'] = $listing->getMarketplaceId();
        $dataForAdd['listing_title'] = $listing->getTitle();

        if (isset($dataForAdd['product_id'])) {
            $dataForAdd['product_title'] = $this->modelFactory->getObject('Magento\Product')
                ->getNameByProductId($dataForAdd['product_id']);
        } else {
            unset($dataForAdd['product_title']);
        }

        $dataForAdd['component_mode'] = $this->getComponentMode();

        $this->activeRecordFactory->getObject('Listing\Log')
            ->setData($dataForAdd)
            ->save()
            ->getId();
    }

    protected function makeDataForAdd(
        $listingId,
        $initiator = \Ess\M2ePro\Helper\Data::INITIATOR_UNKNOWN,
        $productId = null,
        $listingProductId = null,
        $actionId = null,
        $action = self::ACTION_UNKNOWN,
        $description = null,
        $type = self::TYPE_NOTICE,
        array $additionalData = []
    ) {
        return [
            'listing_id'         => (int)$listingId,
            'initiator'          => $initiator,
            'product_id'         => $productId,
            'listing_product_id' => $listingProductId,
            'action_id'          => $actionId,
            'action'             => $action,
            'description'        => $description,
            'type'               => $type,
            'additional_data'    => $this->getHelper('Data')->jsonEncode($additionalData)
        ];
    }

    //########################################
}
