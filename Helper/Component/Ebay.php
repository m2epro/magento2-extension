<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Helper\Component;

use \Ess\M2ePro\Model\Listing\Product as ListingProduct;

class Ebay extends \Ess\M2ePro\Helper\AbstractHelper
{
    const NICK  = 'ebay';

    const MARKETPLACE_US     = 1;
    const MARKETPLACE_MOTORS = 9;
    const MARKETPLACE_AU = 4;
    const MARKETPLACE_UK = 3;
    const MARKETPLACE_DE = 8;
    const MARKETPLACE_IT = 10;

    const LISTING_DURATION_GTC = 100;
    const MAX_LENGTH_FOR_OPTION_VALUE = 50;

    private $ebayFactory;
    private $activeRecordFactory;
    private $moduleConfig;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\Config\Manager\Module $moduleConfig,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Framework\App\Helper\Context $context
    )
    {
        $this->ebayFactory = $ebayFactory;
        $this->activeRecordFactory = $activeRecordFactory;
        $this->moduleConfig = $moduleConfig;
        parent::__construct($helperFactory, $context);
    }

    //########################################

    public function getTitle()
    {
        return $this->getHelper('Module\Translation')->__('eBay');
    }

    public function getChannelTitle()
    {
        return $this->getHelper('Module\Translation')->__('eBay');
    }

    //########################################

    public function getHumanTitleByListingProductStatus($status) {
        $statuses = array(
            ListingProduct::STATUS_NOT_LISTED => $this->getHelper('Module\Translation')->__('Not Listed'),
            ListingProduct::STATUS_LISTED     => $this->getHelper('Module\Translation')->__('Listed'),
            ListingProduct::STATUS_HIDDEN     => $this->getHelper('Module\Translation')->__('Listed (Hidden)'),
            ListingProduct::STATUS_SOLD       => $this->getHelper('Module\Translation')->__('Sold'),
            ListingProduct::STATUS_STOPPED    => $this->getHelper('Module\Translation')->__('Stopped'),
            ListingProduct::STATUS_FINISHED   => $this->getHelper('Module\Translation')->__('Finished'),
            ListingProduct::STATUS_BLOCKED    => $this->getHelper('Module\Translation')->__('Pending')
        );

        if (!isset($statuses[$status])) {
            return NULL;
        }

        return $statuses[$status];
    }

    //########################################

    public function isEnabled()
    {
        return (bool)$this->moduleConfig->getGroupValue('/component/'.self::NICK.'/', 'mode');
    }

    //########################################

    public function getItemUrl($ebayItemId,
                               $accountMode = \Ess\M2ePro\Model\Ebay\Account::MODE_PRODUCTION,
                               $marketplaceId = NULL)
    {
        $marketplaceId = (int)$marketplaceId;
        if ($marketplaceId <= 0 || $marketplaceId == self::MARKETPLACE_MOTORS) {
            $marketplaceId = self::MARKETPLACE_US;
        }

        /** @var \Ess\M2ePro\Model\Marketplace $marketplace */
        $marketplace = $this->activeRecordFactory->getCachedObjectLoaded('Marketplace', $marketplaceId);
        $domain = $marketplace->getUrl();

        return $accountMode == \Ess\M2ePro\Model\Ebay\Account::MODE_SANDBOX
            ? 'http://cgi.sandbox.' .$domain. '/ws/eBayISAPI.dll?ViewItem&item=' .(double)$ebayItemId
            : 'http://www.' .$domain. '/itm/'.(double)$ebayItemId;
    }

    public function getMemberUrl($ebayMemberId, $accountMode = \Ess\M2ePro\Model\Ebay\Account::MODE_PRODUCTION)
    {
        $domain = 'ebay.com';
        if ($accountMode == \Ess\M2ePro\Model\Ebay\Account::MODE_SANDBOX) {
            $domain = 'sandbox.'.$domain;
        }
        return 'http://myworld.'.$domain.'/'.(string)$ebayMemberId;
    }

    //########################################

    public function isShowTaxCategory()
    {
        return (bool)$this->moduleConfig->getGroupValue(
            '/view/ebay/template/selling_format/', 'show_tax_category'
        );
    }

    public function getAvailableDurations()
    {
        return array(
            '1' => $this->getHelper('Module\Translation')->__('1 day'),
            '3' => $this->getHelper('Module\Translation')->__('3 days'),
            '5' => $this->getHelper('Module\Translation')->__('5 days'),
            '7' => $this->getHelper('Module\Translation')->__('7 days'),
            '10' => $this->getHelper('Module\Translation')->__('10 days'),
            '30' => $this->getHelper('Module\Translation')->__('30 days'),
            self::LISTING_DURATION_GTC => $this->getHelper('Module\Translation')->__('Good Till Cancelled'),
        );
    }

    public function getListingProductByEbayItem($ebayItem, $accountId)
    {
        /** @var $collection \Ess\M2ePro\Model\ResourceModel\Listing\Product\Collection */
        $collection = $this->ebayFactory->getObject('Listing\Product')->getCollection();

        $ebayItem  = $collection->getConnection()->quoteInto('?', $ebayItem);
        $accountId = $collection->getConnection()->quoteInto('?', $accountId);

        $collection->getSelect()->join(
            array('mei' => $this->activeRecordFactory->getObject('Ebay\Item')->getResource()->getMainTable()),
            "(second_table.ebay_item_id = mei.id AND mei.item_id = {$ebayItem}
                                                 AND mei.account_id = {$accountId})",
            array()
        );

        if ($collection->getSize() == 0) {
            return NULL;
        }

        return $collection->getFirstItem();
    }

    // ---------------------------------------

    public function getCurrencies()
    {
        return array(
            'AUD' => 'Australian Dollar',
            'GBP' => 'British Pound',
            'CAD' => 'Canadian Dollar',
            'CNY' => 'Chinese Renminbi',
            'EUR' => 'Euro',
            'HKD' => 'Hong Kong Dollar',
            'INR' => 'Indian Rupees',
            'MYR' => 'Malaysian Ringgit',
            'PHP' => 'Philippines Peso',
            'PLN' => 'Polish Zloty',
            'SGD' => 'Singapore Dollar',
            'SEK' => 'Sweden Krona',
            'CHF' => 'Swiss Franc',
            'TWD' => 'Taiwanese Dollar',
            'USD' => 'US Dollar',
        );
    }

    public function getCarriers()
    {
        return array(
            'dhl'   => 'DHL',
            'fedex' => 'FedEx',
            'ups'   => 'UPS',
            'usps'  => 'USPS'
        );
    }

    public function getCarrierTitle($carrierCode, $title = null)
    {
        $carriers = $this->getCarriers();
        $carrierCode = strtolower($carrierCode);

        if (isset($carriers[$carrierCode])) {
            return $carriers[$carrierCode];
        }

        if ($title == '' || filter_var($title, FILTER_VALIDATE_URL) !== false) {
            return 'Other';
        }

        return $title;
    }

    // ---------------------------------------

    public function reduceOptionsForVariations(array $options)
    {
        foreach ($options['set'] as &$optionsSet) {
            foreach ($optionsSet as &$singleOption) {
                $singleOption = $this->getHelper('Data')->reduceWordsInString(
                    $singleOption, self::MAX_LENGTH_FOR_OPTION_VALUE
                );
            }
        }

        foreach ($options['variations'] as &$variation) {
            foreach ($variation as &$singleOption) {
                $singleOption['option'] = $this->getHelper('Data')->reduceWordsInString(
                    $singleOption['option'], self::MAX_LENGTH_FOR_OPTION_VALUE
                );
            }
        }

        return $options;
    }

    public function reduceOptionsForOrders(array $options)
    {
        foreach ($options as &$singleOption) {
            if ($singleOption instanceof \Magento\Catalog\Model\Product) {
                $reducedName = $this->getHelper('Data')->reduceWordsInString(
                    $singleOption->getName(), self::MAX_LENGTH_FOR_OPTION_VALUE
                );
                $singleOption->setData('name', $reducedName);

                continue;
            }

            foreach ($singleOption['values'] as &$singleOptionValue) {
                foreach ($singleOptionValue['labels'] as &$singleOptionLabel) {
                    $singleOptionLabel = $this->getHelper('Data')->reduceWordsInString(
                        $singleOptionLabel, self::MAX_LENGTH_FOR_OPTION_VALUE
                    );
                }
            }
        }

        return $options;
    }

    //########################################

    public function getTranslationServices()
    {
        return array(
            'silver'   => $this->getHelper('Module\Translation')->__('Silver Product Translation'),
            'gold'     => $this->getHelper('Module\Translation')->__('Gold Product Translation'),
            'platinum' => $this->getHelper('Module\Translation')->__('Platinum Product Translation'),
        );
    }

    public function getDefaultTranslationService()
    {
        return 'silver';
    }

    public function isAllowedTranslationService($service)
    {
        $translationServices = $this->getTranslationServices();
        return isset($translationServices[$service]);
    }

    //########################################

    public function clearCache()
    {
        $this->getHelper('Data\Cache\Permanent')->removeTagsValues(self::NICK);
    }

    //########################################
}