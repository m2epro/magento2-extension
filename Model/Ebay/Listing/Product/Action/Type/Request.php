<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Listing\Product\Action\Type;

use Ess\M2ePro\Model\Ebay\Listing\Product\Action\Request\Description;

abstract class Request extends \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Request
{
    /**
     * @var array
     */
    private $requestsTypes = array(
        'selling',
        'description',
        'categories',
        'variations',
        'shipping',
        'payment',
        'returnPolicy'
    );

    /**
     * @var array[Ess\M2ePro\Model\Ebay\Listing\Product\Action\Request\Abstract]
     */
    private $requests = array();

    protected $activeRecordFactory;
    protected $ebayFactory;
    protected $moduleConfig;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Model\Config\Manager\Module $moduleConfig,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory
    )
    {
        $this->activeRecordFactory = $activeRecordFactory;
        $this->ebayFactory = $ebayFactory;
        $this->moduleConfig = $moduleConfig;
        parent::__construct($helperFactory, $modelFactory);
    }

    //########################################

    /**
     * @return array
     */
    public function getRequestData()
    {
        $this->initializeVariations();
        $this->beforeBuildDataEvent();

        $data = $this->getActionData();
        $this->collectMetadata();

        $data = $this->prepareFinalData($data);

        $this->afterBuildDataEvent($data);
        $this->collectRequestsWarningMessages();

        return $data;
    }

    protected function collectMetadata()
    {
        foreach ($this->requests as $requestObject) {
            /** @var \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Type\Request $requestObject */

            $this->metaData = array_merge($this->metaData, $requestObject->getMetaData());
        }
    }

    // ---------------------------------------

    abstract protected function getActionData();

    //########################################

    protected function initializeVariations()
    {
        $this->setIsVariationItem($this->getEbayListingProduct()->isVariationsReady());
    }

    protected function beforeBuildDataEvent() {}

    protected function afterBuildDataEvent(array $data)
    {
        if ($this->getIsVariationItem() || isset($data['price_fixed'])) {

            $isListingTypeFixed = true;

        } elseif (isset($data['price_start'])) {

            $isListingTypeFixed = false;

        } elseif (!is_null($this->getEbayListingProduct()->isOnlineAuctionType())) {

            $isListingTypeFixed = !$this->getEbayListingProduct()->isOnlineAuctionType();

        } else {

            $isListingTypeFixed = $this->getEbayListingProduct()->isListingTypeFixed();
        }

        $this->addMetaData('is_listing_type_fixed', $isListingTypeFixed);
    }

    // ---------------------------------------

    protected function prepareFinalData(array $data)
    {
        $data['is_eps_ebay_images_mode'] = $this->getIsEpsImagesMode();
        $data['upload_images_mode'] = (int)$this->moduleConfig->getGroupValue(
            '/ebay/description/', 'upload_images_mode'
        );

        $data = $this->insertOutOfStockControl($data);
        $data = $this->replaceVariationSpecificsNames($data);
        $data = $this->replaceHttpsToHttpOfImagesUrls($data);
        $data = $this->resolveVariationAndItemSpecificsConflict($data);
        $data = $this->removeVariationsInstances($data);
        $data = $this->resolveVariationMpnIssue($data);

        return $data;
    }

    protected function insertOutOfStockControl(array $data)
    {
        $data['out_of_stock_control'] = $this->getEbayListingProduct()
                                             ->getEbaySellingFormatTemplate()
                                             ->getOutOfStockControl();
        $data['out_of_stock_control_result'] = $data['out_of_stock_control'] || $this->getEbayAccount()
                                                                                     ->getOutOfStockControl();
        return $data;
    }

    protected function replaceVariationSpecificsNames(array $data)
    {
        if (!$this->getIsVariationItem() || !$this->getMagentoProduct()->isConfigurableType() ||
            empty($data['variations_sets']) || !is_array($data['variations_sets'])) {

            return $data;
        }

        $additionalData = $this->getListingProduct()->getAdditionalData();

        if (empty($additionalData['variations_specifics_replacements'])) {
            return $data;
        }

        $data = $this->doReplaceVariationSpecifics($data, $additionalData['variations_specifics_replacements']);
        $this->addMetaData('variations_specifics_replacements', $additionalData['variations_specifics_replacements']);

        return $data;
    }

    protected function replaceHttpsToHttpOfImagesUrls(array $data)
    {
        if ($data['is_eps_ebay_images_mode'] === false ||
            (is_null($data['is_eps_ebay_images_mode']) &&
                $data['upload_images_mode'] ==
                   \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Request\Description::UPLOAD_IMAGES_MODE_SELF)) {
            return $data;
        }

        if (isset($data['images']['images'])) {
            foreach ($data['images']['images'] as &$imageUrl) {
                $imageUrl = str_replace('https://', 'http://', $imageUrl);
            }
        }

        if (isset($data['variation_image']['images'])) {
            foreach ($data['variation_image']['images'] as $attribute => &$imagesUrls) {
                foreach ($imagesUrls as &$imageUrl) {
                    $imageUrl = str_replace('https://', 'http://', $imageUrl);
                }
            }
        }

        return $data;
    }

    protected function resolveVariationAndItemSpecificsConflict(array $data)
    {
        if (!$this->getIsVariationItem() ||
            empty($data['item_specifics']) || !is_array($data['item_specifics']) ||
            empty($data['variations_sets']) || !is_array($data['variations_sets'])) {

            return $data;
        }

        $variationAttributes = array_keys($data['variations_sets']);
        $variationAttributes = array_map('strtolower', $variationAttributes);

        foreach ($data['item_specifics'] as $key => $itemSpecific) {

            if (!in_array(strtolower($itemSpecific['name']), $variationAttributes)) {
                continue;
            }

            unset($data['item_specifics'][$key]);

            $this->addWarningMessage(
                $this->getHelper('Module\Translation')->__(
                    'Attribute "%specific_name%" will be shown as Variation Specific instead of Item Specific.',
                    $itemSpecific['name']
                )
            );
        }

        return $data;
    }

    protected function removeVariationsInstances(array $data)
    {
        if (isset($data['variation']) && is_array($data['variation'])) {
            foreach ($data['variation'] as &$variation) {
                unset($variation['_instance_']);
            }
        }

        return $data;
    }

    /**
     * In M2e Pro version <= 6.4.1 (Magento 1) value MPN - 'Does Not Apply' was sent for variations always
     * (even if Brand was Unbranded). Due to eBay specific we can not stop sending it. So, for "old" items we need
     * set 'Does Not Apply', if real MPN is empty. New items has 'without_mpn_variation_issue' in additional data
     * (set by list response), it means that item was listed after fixing this issue.
     *
     * 1) form variation MPN value (from additional only for list action)
     *
     *       TRY TO RETRIEVE FROM ADDITIONAL DATA OF EACH VARIATION
     *       IF EMPTY: TRY TO RETRIEVE FROM DESCRIPTION POLICY SETTINGS
     *
     * 2) prepare variation MPN value (skip this for list action)
     *
     *   - item variations MPN flag == unknown (variation MPN value only from settings)
     *     [-> item variations MPN flag == without MPN, item variations MPN flag == with MPN]
     *     - without_mpn_variation_issue == NULL
     *        empty variation MPN value -> set "Does Not Apply"
     *        filled variation MPN value -> do nothing
     *     - without_mpn_variation_issue == true
     *        empty variation MPN value -> do nothing
     *        filled variation MPN value -> do nothing
     *
     *   - item variations MPN flag == without MPN (variation MPN value only from settings)
     *      [-> item variations MPN flag == with MPN]
     *      - without_mpn_variation_issue == NULL / without_mpn_variation_issue == true
     *         empty variation MPN value -> do nothing
     *         filled variation MPNvalue  -> do nothing
     *
     *   - item variations MPN flag == with MPN (variation MPN value from additional or settings) [->]
     *     - without_mpn_variation_issue == NULL
     *        empty variation MPN value -> set "Does Not Apply"
     *        filled variation MPN value -> do nothing
     *   - without_mpn_variation_issue == true
     *        empty variation MPN value -> do nothing
     *        filled variation MPN value -> do nothing
     *
     * 3) after revise/relist error use getItem (skip this for list action)
     *
     *       CONDITIONS:
     *       VARIATIONAL PRODUCT == true
     *       VARIATIONS WERE SENT == true
     *       ANY ERROR FROM LIST [in_array]
     *       item variations MPN flag == unknown
     *
     *       ACTIONS:
     *       set item variations MPN flag according to the request
     *       set variations additional MPN values if need
     *
     * @param array $data
     * @return array
     */
    protected function resolveVariationMpnIssue(array $data)
    {
        if (!$this->getIsVariationItem()) {
            return $data;
        }

        $additionalData = $this->getListingProduct()->getAdditionalData();
        if (!empty($additionalData['without_mpn_variation_issue'])) {
            $data['without_mpn_variation_issue'] = true;
            return $data;
        }

        foreach ($data['variation'] as &$variationData) {
            if (!empty($variationData['details']['mpn'])) {
                continue;
            }

            if (!isset($additionalData['is_variation_mpn_filled']) ||
                $additionalData['is_variation_mpn_filled'] === true
            ) {
                $variationData['details']['mpn'] = Description::PRODUCT_DETAILS_DOES_NOT_APPLY;
            }
        }

        return $data;
    }

    protected function doReplaceVariationSpecifics(array $data, array $replacements)
    {
        if (isset($data['variation_image']['specific'])) {

            foreach ($replacements as $findIt => $replaceBy) {

                if ($data['variation_image']['specific'] == $findIt) {
                    $data['variation_image']['specific'] = $replaceBy;
                }
            }
        }

        foreach ($data['variation'] as &$variationItem) {
            foreach ($replacements as $findIt => $replaceBy) {

                if (!isset($variationItem['specifics'][$findIt])) {
                   continue;
                }

                $variationItem['specifics'][$replaceBy] = $variationItem['specifics'][$findIt];
                unset($variationItem['specifics'][$findIt]);
            }
        }

        foreach ($replacements as $findIt => $replaceBy) {

            if (!isset($data['variations_sets'][$findIt])) {
                continue;
            }

            $data['variations_sets'][$replaceBy] = $data['variations_sets'][$findIt];
            unset($data['variations_sets'][$findIt]);

            // M2ePro\TRANSLATIONS
            // The Variational Attribute Label "%replaced_it%" was changed to "%replaced_by%". For Item Specific "%replaced_by%" you select an Attribute by which your Variational Item varies. As it is impossible to send a correct Value for this Item Specific, it’s Label will be used as Variational Attribute Label instead of "%replaced_it%". This replacement cannot be edit in future by Relist/Revise Actions.
            $this->addWarningMessage(
                $this->getHelper('Module\Translation')->__(
                    'The Variational Attribute Label "%replaced_it%" was changed to "%replaced_by%". For Item Specific
                    "%replaced_by%" you select an Attribute by which your Variational Item varies. As it is impossible
                    to send a correct Value for this Item Specific, it’s Label will be used as Variational Attribute
                    Label instead of "%replaced_it%". This replacement cannot be edit in future by
                    Relist/Revise Actions.',
                    $findIt, $replaceBy
                )
            );
        }

        return $data;
    }

    protected function removeImagesIfThereAreNoChanges(array $data)
    {
        /** @var \Ess\M2ePro\Helper\Component\Ebay\Images $imagesHelper */
        $imagesHelper = $this->getHelper('Component\Ebay\Images');

        $additionalData = $this->getListingProduct()->getAdditionalData();
        $metaData = $this->getMetaData();

        $key = 'ebay_product_images_hash';
        if (!empty($additionalData[$key]) && !empty($metaData[$key]) && isset($data['images']['images']) &&
            !$imagesHelper->isHashBelated($additionalData[$key]) &&
            $imagesHelper->areHashesTheSame($additionalData[$key], $metaData[$key])) {

            unset($data['images']['images']);
            unset($metaData[$key]);
            $this->setMetaData($metaData);
        }

        $key = 'ebay_product_variation_images_hash';
        if (!empty($additionalData[$key]) && !empty($metaData[$key]) && isset($data['variation_image']) &&
            !$imagesHelper->isHashBelated($additionalData[$key]) &&
            $imagesHelper->areHashesTheSame($additionalData[$key], $metaData[$key])) {

            unset($data['variation_image']);
            unset($metaData[$key]);
            $this->setMetaData($metaData);
        }

        return $data;
    }

    // ---------------------------------------

    protected function collectRequestsWarningMessages()
    {
        foreach ($this->requestsTypes as $requestType) {

            $messages = $this->getRequest($requestType)->getWarningMessages();

            foreach ($messages as $message) {
                $this->addWarningMessage($message);
            }
        }
    }

    // ---------------------------------------

    protected function getIsEpsImagesMode()
    {
        $additionalData = $this->getListingProduct()->getAdditionalData();

        if (!isset($additionalData['is_eps_ebay_images_mode'])) {
            return NULL;
        }

        return $additionalData['is_eps_ebay_images_mode'];
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Request\Selling
     */
    public function getRequestSelling()
    {
        return $this->getRequest('selling');
    }

    /**
     * @return \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Request\Description
     */
    public function getRequestDescription()
    {
        return $this->getRequest('description');
    }

    // ---------------------------------------

    /**
     * @return \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Request\Variations
     */
    public function getRequestVariations()
    {
        return $this->getRequest('variations');
    }

    /**
     * @return \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Request\Categories
     */
    public function getRequestCategories()
    {
        return $this->getRequest('categories');
    }

    // ---------------------------------------

    /**
     * @return \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Request\Payment
     */
    public function getRequestPayment()
    {
        return $this->getRequest('payment');
    }

    /**
     * @return \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Request\Shipping
     */
    public function getRequestShipping()
    {
        return $this->getRequest('shipping');
    }

    /**
     * @return \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Request\ReturnPolicy
     */
    public function getRequestReturn()
    {
        return $this->getRequest('returnPolicy');
    }

    //########################################

    /**
     * @param $type
     * @return \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Request\AbstractModel
     */
    private function getRequest($type)
    {
        if (!isset($this->requests[$type])) {

            /** @var \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Request\AbstractModel $request */
            $request = $this->modelFactory->getObject('Ebay\Listing\Product\Action\Request\\'.ucfirst($type));

            $request->setParams($this->getParams());
            $request->setListingProduct($this->getListingProduct());
            $request->setIsVariationItem($this->getIsVariationItem());
            $request->setConfigurator($this->getConfigurator());

            $this->requests[$type] = $request;
        }

        return $this->requests[$type];
    }

    //########################################
}