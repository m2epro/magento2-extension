<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Listing\Product\Action\Type\ListAction;

/**
 * Class \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Type\ListAction\Validator
 */
class Validator extends \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Type\Validator
{
    protected $isVerifyCall = false;

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
    ) {
        $this->activeRecordFactory = $activeRecordFactory;
        $this->ebayFactory = $ebayFactory;
        $this->moduleConfig = $moduleConfig;
        parent::__construct($helperFactory, $modelFactory);
    }

    //########################################

    public function validate()
    {
        if (!$this->getListingProduct()->isListable()) {
            // M2ePro\TRANSLATIONS
            // Item is Listed or not available
            $this->addMessage('Item is Listed or not available');

            return false;
        }

        if ($this->getListingProduct()->isHidden()) {
            $this->addMessage(
                'The List action cannot be executed for this Item as it has a Listed (Hidden) status.
                You have to stop Item manually first to run the List action for it.'
            );

            return false;
        }

        if (!$this->validateSameProductAlreadyListed()) {
            return false;
        }

        if (!$this->validateIsVariationProductWithoutVariations()) {
            return false;
        }

        if ($this->getEbayListingProduct()->isVariationsReady()) {
            if (!$this->validateVariationsOptions()) {
                return false;
            }
        }

        if (!$this->validateCategory()) {
            return false;
        }

        if (!$this->validatePrice()) {
            return false;
        }

        if (!$this->validateQty()) {
            return false;
        }

        return true;
    }

    //########################################

    protected function validateSameProductAlreadyListed()
    {
        if ($this->isVerifyCall) {
            return true;
        }

        $params = $this->getParams();
        if ($params['status_changer'] == \Ess\M2ePro\Model\Listing\Product::STATUS_CHANGER_USER) {
            return true;
        }

        $config = $this->moduleConfig->getGroupValue(
            '/ebay/connector/listing/',
            'check_the_same_product_already_listed'
        );

        if (empty($config)) {
            return true;
        }

        $listingTable = $this->activeRecordFactory->getObject('Listing')->getResource()->getMainTable();
        $listingProductCollection = $this->ebayFactory->getObject('Listing\Product')->getCollection();

        $listingProductCollection
            ->getSelect()
            ->join(['l'=>$listingTable], '`main_table`.`listing_id` = `l`.`id`', []);

        $listingProductCollection
            ->addFieldToFilter('status', ['neq' => \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED])
            ->addFieldToFilter('product_id', $this->getListingProduct()->getProductId())
            ->addFieldToFilter('account_id', $this->getAccount()->getId())
            ->addFieldToFilter('marketplace_id', $this->getMarketplace()->getId());

        if (!empty($params['skip_check_the_same_product_already_listed_ids'])) {
            $listingProductCollection->addFieldToFilter(
                'listing_product_id',
                ['nin' => $params['skip_check_the_same_product_already_listed_ids']]
            );
        }

        $theSameListingProduct = $listingProductCollection->getFirstItem();

        if (!$theSameListingProduct->getId()) {
            return true;
        }

        $this->addMessage($this->getHelper('Module\Log')->encodeDescription(
            'There is another Item with the same eBay User ID, '.
            'Product ID and eBay Site presented in "%listing_title%" (%listing_id%) Listing.',
            [
                '!listing_title' => $theSameListingProduct->getListing()->getTitle(),
                '!listing_id' => $theSameListingProduct->getListing()->getId()
            ]
        ));

        return false;
    }

    //########################################

    public function setIsVerifyCall($value)
    {
        $this->isVerifyCall = $value;
        return $this;
    }

    //########################################
}
