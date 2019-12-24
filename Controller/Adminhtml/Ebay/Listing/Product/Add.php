<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Product;

use Ess\M2ePro\Model\Listing;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Product\Add
 */
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

    protected function getSessionValue($key = null)
    {
        $sessionData = $this->getHelper('Data\Session')->getValue($this->sessionKey);

        if ($sessionData === null) {
            $sessionData = [];
        }

        if ($key === null) {
            return $sessionData;
        }

        return isset($sessionData[$key]) ? $sessionData[$key] : null;
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
        return $this->ebayFactory->getObjectLoaded('Listing', $this->getRequest()->getParam('id'));
    }

    //########################################

    protected function setWizardStep($step)
    {
        $wizardHelper = $this->getHelper('Module\Wizard');

        if (!$wizardHelper->isActive(\Ess\M2ePro\Helper\View\Ebay::WIZARD_INSTALLATION_NICK)) {
            return;
        }

        $wizardHelper->setStep(\Ess\M2ePro\Helper\View\Ebay::WIZARD_INSTALLATION_NICK, $step);
    }
}
