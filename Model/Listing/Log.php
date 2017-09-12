<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
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
    const ACTION_UNKNOWN = 1;
    const _ACTION_UNKNOWN = 'System';

    const ACTION_ADD_LISTING = 2;
    const _ACTION_ADD_LISTING = 'Add new Listing';
    const ACTION_DELETE_LISTING = 3;
    const _ACTION_DELETE_LISTING = 'Delete existing Listing';

    const ACTION_ADD_PRODUCT_TO_LISTING = 4;
    const _ACTION_ADD_PRODUCT_TO_LISTING = 'Add Product to Listing';
    const ACTION_DELETE_PRODUCT_FROM_LISTING = 5;
    const _ACTION_DELETE_PRODUCT_FROM_LISTING = 'Delete Product from Listing';

    const ACTION_ADD_PRODUCT_TO_MAGENTO = 6;
    const _ACTION_ADD_PRODUCT_TO_MAGENTO = 'Add new Product to Magento Store';
    const ACTION_DELETE_PRODUCT_FROM_MAGENTO = 7;
    const _ACTION_DELETE_PRODUCT_FROM_MAGENTO = 'Delete existing Product from Magento Store';

    const ACTION_CHANGE_PRODUCT_PRICE = 8;
    const _ACTION_CHANGE_PRODUCT_PRICE = 'Change of Product Price in Magento Store';
    const ACTION_CHANGE_PRODUCT_SPECIAL_PRICE = 9;
    const _ACTION_CHANGE_PRODUCT_SPECIAL_PRICE = 'Change of Product Special Price in Magento Store';
    const ACTION_CHANGE_PRODUCT_QTY = 10;
    const _ACTION_CHANGE_PRODUCT_QTY = 'Change of Product QTY in Magento Store';
    const ACTION_CHANGE_PRODUCT_STOCK_AVAILABILITY = 11;
    const _ACTION_CHANGE_PRODUCT_STOCK_AVAILABILITY = 'Change of Product Stock availability in Magento Store';
    const ACTION_CHANGE_PRODUCT_STATUS = 12;
    const _ACTION_CHANGE_PRODUCT_STATUS = 'Change of Product status in Magento Store';

    const ACTION_LIST_PRODUCT_ON_COMPONENT = 13;
    const _ACTION_LIST_PRODUCT_ON_COMPONENT = 'List Product on Channel';
    const ACTION_RELIST_PRODUCT_ON_COMPONENT = 14;
    const _ACTION_RELIST_PRODUCT_ON_COMPONENT = 'Relist Product on Channel';
    const ACTION_REVISE_PRODUCT_ON_COMPONENT = 15;
    const _ACTION_REVISE_PRODUCT_ON_COMPONENT = 'Revise Product on Channel';
    const ACTION_STOP_PRODUCT_ON_COMPONENT = 16;
    const _ACTION_STOP_PRODUCT_ON_COMPONENT = 'Stop Product on Channel';
    const ACTION_DELETE_PRODUCT_FROM_COMPONENT = 24;
    const _ACTION_DELETE_PRODUCT_FROM_COMPONENT = 'Remove Product from Channel';
    const ACTION_STOP_AND_REMOVE_PRODUCT = 17;
    const _ACTION_STOP_AND_REMOVE_PRODUCT = 'Stop on Channel / Remove from Listing';
    const ACTION_DELETE_AND_REMOVE_PRODUCT = 23;
    const _ACTION_DELETE_AND_REMOVE_PRODUCT = 'Remove from Channel & Listing';
    const ACTION_SWITCH_TO_AFN_ON_COMPONENT = 29;
    const _ACTION_SWITCH_TO_AFN_ON_COMPONENT = 'Switching Fulfillment to AFN';
    const ACTION_SWITCH_TO_MFN_ON_COMPONENT = 30;
    const _ACTION_SWITCH_TO_MFN_ON_COMPONENT = 'Switching Fulfillment to MFN';

    const ACTION_CHANGE_PRODUCT_SPECIAL_PRICE_FROM_DATE = 19;
    const _ACTION_CHANGE_PRODUCT_SPECIAL_PRICE_FROM_DATE = 'Change of Product Special Price from date in Magento Store';

    const ACTION_CHANGE_PRODUCT_SPECIAL_PRICE_TO_DATE = 20;
    const _ACTION_CHANGE_PRODUCT_SPECIAL_PRICE_TO_DATE = 'Change of Product Special Price to date in Magento Store';

    const ACTION_CHANGE_CUSTOM_ATTRIBUTE = 18;
    const _ACTION_CHANGE_CUSTOM_ATTRIBUTE = 'Change of Product Custom Attribute in Magento Store';

    const ACTION_CHANGE_PRODUCT_TIER_PRICE = 31;
    const _ACTION_CHANGE_PRODUCT_TIER_PRICE = 'Change of Product Tier Price in Magento Store';

    const ACTION_MOVE_TO_LISTING = 21;
    const _ACTION_MOVE_TO_LISTING = 'Move to another Listing';

    const ACTION_MOVE_FROM_OTHER_LISTING = 22;
    const _ACTION_MOVE_FROM_OTHER_LISTING = 'Move from 3rd Party Listing';

    const ACTION_CHANNEL_CHANGE = 25;
    const _ACTION_CHANNEL_CHANGE = 'Change Product on Channel';

    const ACTION_TRANSLATE_PRODUCT = 28;
    const _ACTION_TRANSLATE_PRODUCT = 'Translation';

    //########################################

    public function _construct()
    {
        parent::_construct();
        $this->_init('Ess\M2ePro\Model\ResourceModel\Listing\Log');
    }

    //########################################

    public function addListingMessage($listingId,
                                      $initiator = \Ess\M2ePro\Helper\Data::INITIATOR_UNKNOWN,
                                      $actionId = NULL,
                                      $action = NULL,
                                      $description = NULL,
                                      $type = NULL,
                                      $priority = NULL,
                                      array $additionalData = array())
    {
        $dataForAdd = $this->makeDataForAdd($listingId,
                                            $initiator,
                                            NULL,
                                            NULL,
                                            $actionId,
                                            $action,
                                            $description,
                                            $type,
                                            $priority,
                                            $additionalData);

        $this->createMessage($dataForAdd);
    }

    public function addProductMessage($listingId,
                                      $productId,
                                      $listingProductId,
                                      $initiator = \Ess\M2ePro\Helper\Data::INITIATOR_UNKNOWN,
                                      $actionId = NULL,
                                      $action = NULL,
                                      $description = NULL,
                                      $type = NULL,
                                      $priority = NULL,
                                      array $additionalData = array())
    {
        $dataForAdd = $this->makeDataForAdd($listingId,
                                            $initiator,
                                            $productId,
                                            $listingProductId,
                                            $actionId,
                                            $action,
                                            $description,
                                            $type,
                                            $priority,
                                            $additionalData);

        $this->createMessage($dataForAdd);
    }

    // ---------------------------------------

    public function clearMessages($listingId = NULL)
    {
        $filters = array();

        if (!is_null($listingId)) {
            $filters['listing_id'] = $listingId;
        }
        if (!is_null($this->componentMode)) {
            $filters['component_mode'] = $this->componentMode;
        }

        $this->getResource()->clearMessages($filters);
    }

    //########################################

    protected function createMessage($dataForAdd)
    {
        /** @var \Ess\M2ePro\Model\Listing $listing */
        $listing = $this->parentFactory->getCachedObjectLoaded(
            $this->getComponentMode(), 'Listing', $dataForAdd['listing_id']
        );

        $dataForAdd['account_id']     = $listing->getAccountId();
        $dataForAdd['marketplace_id'] = $listing->getMarketplaceId();
        $dataForAdd['listing_title']  = $listing->getTitle();

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

    protected function makeDataForAdd($listingId,
                                      $initiator = \Ess\M2ePro\Helper\Data::INITIATOR_UNKNOWN,
                                      $productId = NULL,
                                      $listingProductId = NULL,
                                      $actionId = NULL,
                                      $action = NULL,
                                      $description = NULL,
                                      $type = NULL,
                                      $priority = NULL,
                                      array $additionalData = array())
    {
        $dataForAdd = array();

        $dataForAdd['listing_id'] = (int)$listingId;
        $dataForAdd['initiator'] = $initiator;

        if (!is_null($productId)) {
            $dataForAdd['product_id'] = (int)$productId;
        } else {
            $dataForAdd['product_id'] = NULL;
        }

        if (!is_null($listingProductId)) {
            $dataForAdd['listing_product_id'] = (int)$listingProductId;
        } else {
            $dataForAdd['listing_product_id'] = NULL;
        }

        if (!is_null($actionId)) {
            $dataForAdd['action_id'] = (int)$actionId;
        } else {
            $dataForAdd['action_id'] = $this->getResource()->getNextActionId();
        }

        if (!is_null($action)) {
            $dataForAdd['action'] = (int)$action;
        } else {
            $dataForAdd['action'] = self::ACTION_UNKNOWN;
        }

        if (!is_null($description)) {
            $dataForAdd['description'] = $description;
        } else {
            $dataForAdd['description'] = NULL;
        }

        if (!is_null($type)) {
            $dataForAdd['type'] = (int)$type;
        } else {
            $dataForAdd['type'] = self::TYPE_NOTICE;
        }

        if (!is_null($priority)) {
            $dataForAdd['priority'] = (int)$priority;
        } else {
            $dataForAdd['priority'] = self::PRIORITY_LOW;
        }

        $dataForAdd['additional_data'] = $this->getHelper('Data')->jsonEncode($additionalData);

        return $dataForAdd;
    }

    //########################################
}