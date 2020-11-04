<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2017 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Listing;

class Transferring extends \Ess\M2ePro\Model\AbstractModel
{
    //########################################

    const PARAM_LISTING_ID_DESTINATION_CREATE_NEW = 'create-new';

    protected $sessionPrefix = 'amazon_listing_transferring';

    /** @var \Ess\M2ePro\Model\Listing */
    protected $listing;

    //########################################

    public function setListing(\Ess\M2ePro\Model\Listing $listing)
    {
        $this->listing = $listing;
    }

    public function getListing()
    {
        return $this->listing;
    }

    //########################################

    public function setProductsIds($products)
    {
        $this->setSessionValue('products_ids', $products);
        return $this;
    }

    public function setTargetListingId($listingId)
    {
        $this->setSessionValue('to_listing_id', $listingId);
        return $this;
    }

    public function setErrorsCount($count)
    {
        $this->setSessionValue('errors_count', $count);
        return $this;
    }

    //----------------------------------------

    public function getProductsIds()
    {
        return $this->getSessionValue('products_ids');
    }

    public function getTargetListingId()
    {
        return $this->getSessionValue('to_listing_id');
    }

    public function getErrorsCount()
    {
        return (int)$this->getSessionValue('errors_count');
    }

    public function isTargetListingNew()
    {
        return $this->getTargetListingId() === self::PARAM_LISTING_ID_DESTINATION_CREATE_NEW;
    }

    //########################################

    public function setSessionValue($key, $value)
    {
        $sessionData = $this->getSessionValue();

        if ($key === null) {
            $sessionData = $value;
        } else {
            $sessionData[$key] = $value;
        }

        $this->getHelper('Data_Session')->setValue($this->sessionPrefix . $this->listing->getId(), $sessionData);
        return $this;
    }

    public function getSessionValue($key = null)
    {
        $sessionData = $this->getHelper('Data_Session')->getValue($this->sessionPrefix . $this->listing->getId());

        if ($sessionData === null) {
            $sessionData = [];
        }

        if ($key === null) {
            return $sessionData;
        }

        return isset($sessionData[$key]) ? $sessionData[$key] : null;
    }

    public function clearSession()
    {
        $this->getHelper('Data_Session')->getValue($this->sessionPrefix . $this->listing->getId(), true);
    }

    //########################################
}
