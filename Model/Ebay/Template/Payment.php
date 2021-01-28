<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

/**
 * @method \Ess\M2ePro\Model\ResourceModel\Ebay\Template\Payment getResource()
 */

namespace Ess\M2ePro\Model\Ebay\Template;

use Ess\M2ePro\Model\ActiveRecord\Factory;

/**
 * Class \Ess\M2ePro\Model\Ebay\Template\Payment
 */
class Payment extends \Ess\M2ePro\Model\ActiveRecord\Component\AbstractModel
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
        $this->_init('Ess\M2ePro\Model\ResourceModel\Ebay\Template\Payment');
    }

    /**
     * @return string
     */
    public function getNick()
    {
        return \Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_PAYMENT;
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
                ->addFieldToFilter('template_payment_id', $this->getId())
                ->getSize() ||
            (bool)$this->activeRecordFactory->getObject('Ebay_Listing_Product')
                ->getCollection()
                ->addFieldToFilter(
                    'template_payment_mode',
                    \Ess\M2ePro\Model\Ebay\Template\Manager::MODE_TEMPLATE
                )
                ->addFieldToFilter('template_payment_id', $this->getId())
                ->getSize();
    }

    //########################################

    public function save()
    {
        $this->getHelper('Data_Cache_Permanent')->removeTagValues('ebay_template_payment');
        return parent::save();
    }

    //########################################

    public function delete()
    {
        if ($this->isLocked()) {
            return false;
        }

        $services = $this->getServices(true);
        foreach ($services as $service) {
            $service->delete();
        }

        $this->marketplaceModel = null;

        $this->getHelper('Data_Cache_Permanent')->removeTagValues('ebay_template_payment');

        return parent::delete();
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

    /**
     * @param bool $asObjects
     * @param array $filters
     * @return array|\Ess\M2ePro\Model\Ebay\Template\Payment\Service[]
     */
    public function getServices($asObjects = false, array $filters = [])
    {
        return $this->getRelatedSimpleItems(
            'Ebay_Template_Payment_Service',
            'template_payment_id',
            $asObjects,
            $filters
        );
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

    /**
     * @return bool
     */
    public function isManagedPaymentsEnabled()
    {
        return (bool)$this->getData('managed_payments_mode');
    }

    //########################################

    /**
     * @return bool
     */
    public function isPayPalEnabled()
    {
        return (bool)$this->getData('pay_pal_mode');
    }

    public function getPayPalEmailAddress()
    {
        return $this->getData('pay_pal_email_address');
    }

    /**
     * @return bool
     */
    public function isPayPalImmediatePaymentEnabled()
    {
        return (bool)$this->getData('pay_pal_immediate_payment');
    }

    //########################################

    public function getCacheGroupTags()
    {
        return array_merge(parent::getCacheGroupTags(), ['template']);
    }

    public function isCacheEnabled()
    {
        return true;
    }

    //########################################
}
