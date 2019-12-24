<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

/**
 * @method \Ess\M2ePro\Model\ResourceModel\Ebay\Template\ReturnPolicy getResource()
 */
namespace Ess\M2ePro\Model\Ebay\Template;

use Ess\M2ePro\Model\ActiveRecord\Factory;

/**
 * Class \Ess\M2ePro\Model\Ebay\Template\ReturnPolicy
 */
class ReturnPolicy extends \Ess\M2ePro\Model\ActiveRecord\Component\AbstractModel
{
    private $ebayParentFactory;

    /**
     * @var \Ess\M2ePro\Model\Marketplace
     */
    private $marketplaceModel = null;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayParentFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->ebayParentFactory = $ebayParentFactory;

        parent::__construct(
            $modelFactory,
            $activeRecordFactory,
            $helperFactory,
            $context,
            $registry,
            $resource,
            $resourceCollection,
            $data
        );
    }

    //########################################

    public function _construct()
    {
        parent::_construct();
        $this->_init('Ess\M2ePro\Model\ResourceModel\Ebay\Template\ReturnPolicy');
    }

    /**
     * @return string
     */
    public function getNick()
    {
        return \Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_RETURN_POLICY;
    }

    //########################################

    /**
     * @return bool
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function isLocked()
    {
        if (parent::isLocked()) {
            return true;
        }

        return (bool)$this->activeRecordFactory->getObject('Ebay\Listing')
                            ->getCollection()
                            ->addFieldToFilter(
                                'template_return_policy_mode',
                                \Ess\M2ePro\Model\Ebay\Template\Manager::MODE_TEMPLATE
                            )
                            ->addFieldToFilter('template_return_policy_id', $this->getId())
                            ->getSize() ||
               (bool)$this->activeRecordFactory->getObject('Ebay_Listing_Product')
                            ->getCollection()
                            ->addFieldToFilter(
                                'template_return_policy_mode',
                                \Ess\M2ePro\Model\Ebay\Template\Manager::MODE_TEMPLATE
                            )
                            ->addFieldToFilter('template_return_policy_id', $this->getId())
                            ->getSize();
    }

    //########################################

    public function save()
    {
        $this->getHelper('Data_Cache_Permanent')->removeTagValues('ebay_template_return');
        return parent::save();
    }

    //########################################

    public function delete()
    {
        $temp = parent::delete();
        $temp && $this->marketplaceModel = null;

        $this->getHelper('Data_Cache_Permanent')->removeTagValues('ebay_template_return');

        return $temp;
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Marketplace
     */
    public function getMarketplace()
    {
        if ($this->marketplaceModel === null) {
            $this->marketplaceModel = $this->ebayParentFactory->getCachedObjectLoaded(
                'Marketplace',
                $this->getMarketplaceId()
            );
        }

        return $this->marketplaceModel;
    }

    /**
     * @param \Ess\M2ePro\Model\Marketplace $instance
     */
    public function setMarketplace(\Ess\M2ePro\Model\Marketplace $instance)
    {
         $this->marketplaceModel = $instance;
    }

    //########################################

    public function getTitle()
    {
        return $this->getData('title');
    }

    /**
     * @return bool
     */
    public function isCustomTemplate()
    {
        return (bool)$this->getData('is_custom_template');
    }

    /**
     * @return int
     */
    public function getMarketplaceId()
    {
        return (int)$this->getData('marketplace_id');
    }

    // ---------------------------------------

    public function getCreateDate()
    {
        return $this->getData('create_date');
    }

    public function getUpdateDate()
    {
        return $this->getData('update_date');
    }

    //########################################

    public function getAccepted()
    {
        return $this->getData('accepted');
    }

    public function getOption()
    {
        return $this->getData('option');
    }

    public function getWithin()
    {
        return $this->getData('within');
    }

    /**
     * @return bool
     */
    public function isHolidayEnabled()
    {
        return (bool)$this->getData('holiday_mode');
    }

    public function getShippingCost()
    {
        return $this->getData('shipping_cost');
    }

    public function getRestockingFee()
    {
        return $this->getData('restocking_fee');
    }

    public function getDescription()
    {
        return $this->getData('description');
    }

    //########################################

    /**
     * @return array
     */
    public function getTrackingAttributes()
    {
        return [];
    }

    /**
     * @return array
     */
    public function getUsedAttributes()
    {
        return [];
    }

    //########################################

    /**
     * @return array
     */
    public function getDefaultSettingsSimpleMode()
    {
        return [
            'accepted'       => 'ReturnsAccepted',
            'option'         => '',
            'within'         => '',
            'holiday_mode'   => 0,
            'shipping_cost'  => '',
            'restocking_fee' => '',
            'description'    => ''
        ];
    }

    /**
     * @return array
     */
    public function getDefaultSettingsAdvancedMode()
    {
        return $this->getDefaultSettingsSimpleMode();
    }

    //########################################

    /**
     * @param bool $asArrays
     * @param string|array $columns
     * @return array
     */
    public function getAffectedListingsProducts($asArrays = true, $columns = '*')
    {
        $templateManager = $this->modelFactory->getObject('Ebay_Template_Manager');
        $templateManager->setTemplate(\Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_RETURN_POLICY);

        $listingsProducts = $templateManager->getAffectedOwnerObjects(
            \Ess\M2ePro\Model\Ebay\Template\Manager::OWNER_LISTING_PRODUCT,
            $this->getId(),
            $asArrays,
            $columns
        );

        $listings = $templateManager->getAffectedOwnerObjects(
            \Ess\M2ePro\Model\Ebay\Template\Manager::OWNER_LISTING,
            $this->getId(),
            false
        );

        foreach ($listings as $listing) {
            $tempListingsProducts = $listing->getChildObject()
                                            ->getAffectedListingsProductsByTemplate(
                                                \Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_RETURN_POLICY,
                                                $asArrays,
                                                $columns
                                            );

            foreach ($tempListingsProducts as $listingProduct) {
                if (!isset($listingsProducts[$listingProduct['id']])) {
                    $listingsProducts[$listingProduct['id']] = $listingProduct;
                }
            }
        }

        return $listingsProducts;
    }

    public function setSynchStatusNeed($newData, $oldData)
    {
        $listingsProducts = $this->getAffectedListingsProducts(true, ['id']);
        if (empty($listingsProducts)) {
            return;
        }

        $this->getResource()->setSynchStatusNeed($newData, $oldData, $listingsProducts);
    }

    //########################################

    public function isCacheEnabled()
    {
        return true;
    }

    //########################################
}
