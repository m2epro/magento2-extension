<?php

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Product;

use Ess\M2ePro\Model\Listing;

abstract class Add extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing
{
    protected $sessionKey = 'ebay_listing_product_add';

    //########################################

    protected function setSessionValue($key, $value)
    {
        $sessionData = $this->getSessionValue();
        $sessionData[$key] = $value;

        $this->getHelper('Data\Session')->setValue($this->sessionKey, $sessionData);

        return $this;
    }

    protected function getSessionValue($key = NULL)
    {
        $sessionData = $this->getHelper('Data\Session')->getValue($this->sessionKey);

        if (is_null($sessionData)) {
            $sessionData = array();
        }

        if (is_null($key)) {
            return $sessionData;
        }

        return isset($sessionData[$key]) ? $sessionData[$key] : NULL;
    }

    protected function clearSession()
    {
        $this->getHelper('Data\Session')->getValue($this->sessionKey, true);
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Listing
     */
    protected function getListing()
    {
        return $this->ebayFactory->getObjectLoaded('Listing',$this->getRequest()->getParam('id'));
    }

    //########################################

    protected function setWizardStep($step)
    {
        $wizardHelper = $this->getHelper('Module\Wizard');

        if (!$wizardHelper->isActive(\Ess\M2ePro\Helper\View\Ebay::WIZARD_INSTALLATION_NICK)) {
            return;
        }

        $wizardHelper->setStep(\Ess\M2ePro\Helper\View\Ebay::WIZARD_INSTALLATION_NICK,$step);
    }
}