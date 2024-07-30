<?php

/**
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
    public const ACTION_UNKNOWN = 1;
    public const _ACTION_UNKNOWN = 'System';

    public const ACTION_ADD_LISTING = 2;
    public const _ACTION_ADD_LISTING = 'Add new Listing';
    public const ACTION_DELETE_LISTING = 3;
    public const _ACTION_DELETE_LISTING = 'Delete existing Listing';

    public const ACTION_ADD_PRODUCT_TO_LISTING = 4;
    public const _ACTION_ADD_PRODUCT_TO_LISTING = 'Add Product to Listing';
    public const ACTION_DELETE_PRODUCT_FROM_LISTING = 5;
    public const _ACTION_DELETE_PRODUCT_FROM_LISTING = 'Delete Product from Listing';

    public const ACTION_ADD_NEW_CHILD_LISTING_PRODUCT = 35;
    public const _ACTION_ADD_NEW_CHILD_LISTING_PRODUCT = 'Add New Child Product';

    public const ACTION_ADD_PRODUCT_TO_MAGENTO = 6;
    public const _ACTION_ADD_PRODUCT_TO_MAGENTO = 'Add new Product to Magento Store';
    public const ACTION_DELETE_PRODUCT_FROM_MAGENTO = 7;
    public const _ACTION_DELETE_PRODUCT_FROM_MAGENTO = 'Delete existing Product from Magento Store';

    public const ACTION_CHANGE_PRODUCT_PRICE = 8;
    public const _ACTION_CHANGE_PRODUCT_PRICE = 'Change of Product Price in Magento Store';
    public const ACTION_CHANGE_PRODUCT_SPECIAL_PRICE = 9;
    public const _ACTION_CHANGE_PRODUCT_SPECIAL_PRICE = 'Change of Product Special Price in Magento Store';
    public const ACTION_CHANGE_PRODUCT_QTY = 10;
    public const _ACTION_CHANGE_PRODUCT_QTY = 'Change of Product QTY in Magento Store';
    public const ACTION_CHANGE_PRODUCT_STOCK_AVAILABILITY = 11;
    public const _ACTION_CHANGE_PRODUCT_STOCK_AVAILABILITY = 'Change of Product Stock availability in Magento Store';
    public const ACTION_CHANGE_PRODUCT_STATUS = 12;
    public const _ACTION_CHANGE_PRODUCT_STATUS = 'Change of Product status in Magento Store';

    public const ACTION_LIST_PRODUCT_ON_COMPONENT = 13;
    public const _ACTION_LIST_PRODUCT_ON_COMPONENT = 'List Product on Channel';
    public const ACTION_RELIST_PRODUCT_ON_COMPONENT = 14;
    public const _ACTION_RELIST_PRODUCT_ON_COMPONENT = 'Relist Product on Channel';
    public const ACTION_REVISE_PRODUCT_ON_COMPONENT = 15;
    public const _ACTION_REVISE_PRODUCT_ON_COMPONENT = 'Revise Product on Channel';
    public const ACTION_STOP_PRODUCT_ON_COMPONENT = 16;
    public const _ACTION_STOP_PRODUCT_ON_COMPONENT = 'Stop Product on Channel';
    public const ACTION_DELETE_PRODUCT_FROM_COMPONENT = 24;
    public const _ACTION_DELETE_PRODUCT_FROM_COMPONENT = 'Remove Product from Channel';
    public const ACTION_STOP_AND_REMOVE_PRODUCT = 17;
    public const _ACTION_STOP_AND_REMOVE_PRODUCT = 'Stop on Channel / Remove from Listing';
    public const ACTION_DELETE_AND_REMOVE_PRODUCT = 23;
    public const _ACTION_DELETE_AND_REMOVE_PRODUCT = 'Remove from Channel & Listing';
    public const ACTION_SWITCH_TO_AFN_ON_COMPONENT = 29;
    public const _ACTION_SWITCH_TO_AFN_ON_COMPONENT = 'Switching Fulfillment to AFN';
    public const ACTION_SWITCH_TO_MFN_ON_COMPONENT = 30;
    public const _ACTION_SWITCH_TO_MFN_ON_COMPONENT = 'Switching Fulfillment to MFN';
    public const ACTION_RESET_BLOCKED_PRODUCT = 32;
    public const _ACTION_RESET_BLOCKED_PRODUCT = 'Reset Incomplete Item';

    public const ACTION_CHANGE_PRODUCT_SPECIAL_PRICE_FROM_DATE = 19;
    public const _ACTION_CHANGE_PRODUCT_SPECIAL_PRICE_FROM_DATE = 'Change of Product Special Price from date in Magento Store';

    public const ACTION_CHANGE_PRODUCT_SPECIAL_PRICE_TO_DATE = 20;
    public const _ACTION_CHANGE_PRODUCT_SPECIAL_PRICE_TO_DATE = 'Change of Product Special Price to date in Magento Store';

    public const ACTION_CHANGE_CUSTOM_ATTRIBUTE = 18;
    public const _ACTION_CHANGE_CUSTOM_ATTRIBUTE = 'Change of Product Custom Attribute in Magento Store';

    public const ACTION_CHANGE_PRODUCT_TIER_PRICE = 31;
    public const _ACTION_CHANGE_PRODUCT_TIER_PRICE = 'Change of Product Tier Price in Magento Store';

    public const ACTION_MOVE_TO_LISTING = 21;
    public const _ACTION_MOVE_TO_LISTING = 'Move to another Listing';

    public const ACTION_MOVE_FROM_OTHER_LISTING = 22;
    public const _ACTION_MOVE_FROM_OTHER_LISTING = 'Move from Unmanaged Listing';

    public const ACTION_SELL_ON_ANOTHER_SITE = 33;
    public const _ACTION_SELL_ON_ANOTHER_SITE = 'Sell On Another Marketplace';

    public const ACTION_CHANNEL_CHANGE = 25;
    public const _ACTION_CHANNEL_CHANGE = 'External Change';

    public const ACTION_REMAP_LISTING_PRODUCT = 34;
    public const _ACTION_REMAP_LISTING_PRODUCT = 'Relink';

    public const ACTION_REPRICER = 36;
    public const _ACTION_REPRICER = 'Repricer';

    public const ACTION_CHANGE_PRODUCT_QTY_IN_MAGENTO_SOURCE = 37;
    public const _ACTION_CHANGE_PRODUCT_QTY_IN_MAGENTO_SOURCE = 'Update of FBA Product QTY in Magento Store';

    public const ACTION_PROMOTION = 38;
    public const _ACTION_PROMOTION = 'Promotion';

    public const ACTION_VIDEO = 39;
    public const _ACTION_VIDEO = 'Upload Product Video on Channel';

    protected function _construct(): void
    {
        parent::_construct();
        $this->_init(\Ess\M2ePro\Model\ResourceModel\Listing\Log::class);
    }

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

    public function clearMessages($listingId = null): void
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

    protected function createMessage($dataForAdd): void
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
        $type = self::TYPE_INFO,
        array $additionalData = []
    ): array {
        return [
            'listing_id' => (int)$listingId,
            'initiator' => $initiator,
            'product_id' => $productId,
            'listing_product_id' => $listingProductId,
            'action_id' => $actionId,
            'action' => $action,
            'description' => $description,
            'type' => $type,
            'additional_data' => \Ess\M2ePro\Helper\Json::encode($additionalData),
        ];
    }
}
