<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Listing\Product\Action\Type\ListAction;

class Validator extends \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Type\Validator
{
    protected $activeRecordFactory;
    protected $ebayFactory;
    protected $moduleConfig;

    //########################################

    function __construct(
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

    public function validate()
    {
        if (!$this->getListingProduct()->isListable()) {

            // M2ePro\TRANSLATIONS
            // Item is Listed or not available
            $this->addMessage('Item is Listed or not available');

            return false;
        }

        if (!$this->validateCategory()) {
            return false;
        }

        if (!$this->validateSameProductAlreadyListed()) {
            return false;
        }

        if (!$this->validateQty()) {
            return false;
        }

        if ($this->getEbayListingProduct()->isVariationsReady()) {

            return $this->validateVariationsFixedPrice();
        }

        if ($this->getEbayListingProduct()->isListingTypeAuction()) {
            if (!$this->validateStartPrice()) {
                return false;
            }

            if (!$this->validateReservePrice()) {
                return false;
            }

            if (!$this->validateBuyItNowPrice()) {
                return false;
            }
        } else {
            if (!$this->validateFixedPrice()) {
                return false;
            }
        }

        return true;
    }

    //########################################

    protected function validateSameProductAlreadyListed()
    {
        $params = $this->getParams();
        if ($params['status_changer'] == \Ess\M2ePro\Model\Listing\Product::STATUS_CHANGER_USER) {
            return true;
        }

        $config = $this->moduleConfig->getGroupValue(
            '/ebay/connector/listing/', 'check_the_same_product_already_listed'
        );

        if (empty($config)) {
            return true;
        }

        $listingTable = $this->activeRecordFactory->getObject('Listing')->getResource()->getMainTable();
        $listingProductCollection = $this->ebayFactory->getObject('Listing\Product')->getCollection();

        $listingProductCollection
            ->getSelect()
            ->join(array('l'=>$listingTable),'`main_table`.`listing_id` = `l`.`id`',array());

        $listingProductCollection
            ->addFieldToFilter('status',array('neq' => \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED))
            ->addFieldToFilter('product_id',$this->getListingProduct()->getProductId())
            ->addFieldToFilter('account_id',$this->getAccount()->getId())
            ->addFieldToFilter('marketplace_id',$this->getMarketplace()->getId());

        if (!empty($params['skip_check_the_same_product_already_listed_ids'])) {

            $listingProductCollection->addFieldToFilter(
                'listing_product_id', array('nin' => $params['skip_check_the_same_product_already_listed_ids'])
            );
        }

        $theSameListingProduct = $listingProductCollection->getFirstItem();

        if (!$theSameListingProduct->getId()) {
            return true;
        }

        $this->addMessage($this->activeRecordFactory->getObject('Log\AbstractLog')->encodeDescription(
            'There is another Item with the same eBay User ID, '.
            'Product ID and eBay Site presented in "%listing_title%" (%listing_id%) Listing.',
            array(
                '!listing_title' => $theSameListingProduct->getListing()->getTitle(),
                '!listing_id' => $theSameListingProduct->getListing()->getId()
            )
        ));

        return false;
    }

    //########################################
}