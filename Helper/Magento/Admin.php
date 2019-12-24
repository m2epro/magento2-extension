<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Helper\Magento;

/**
 * Class \Ess\M2ePro\Helper\Magento\Admin
 */
class Admin extends AbstractHelper
{
    private $user;
    private $storeManager;
    private $authSession;

    //########################################

    public function __construct(
        \Magento\User\Model\User $user,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Backend\Model\Auth\Session $authSession,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Framework\App\Helper\Context $context
    ) {
        $this->user         = $user;
        $this->storeManager = $storeManager;
        $this->authSession  = $authSession;

        parent::__construct($objectManager, $helperFactory, $context);
    }

    //########################################

    public function getCurrentInfo()
    {
        $defaultStoreId = $this->getHelper('Magento\Store')->getDefaultStoreId();

        // ---------------------------------------
        $userId = $this->authSession->getUser()->getId();
        $userInfo = $this->user->load($userId)->getData();

        $tempPath = defined('Mage_Shipping_Model_Config::XML_PATH_ORIGIN_CITY')
            ? \Magento\Shipping\Model\Config::XML_PATH_ORIGIN_CITY : 'shipping/origin/city';
        $userInfo['city'] = $this->storeManager->getStore($defaultStoreId)->getConfig($tempPath);

        $tempPath = defined('Mage_Shipping_Model_Config::XML_PATH_ORIGIN_POSTCODE')
            ? \Magento\Shipping\Model\Config::XML_PATH_ORIGIN_POSTCODE : 'shipping/origin/postcode';
        $userInfo['postal_code'] = $this->storeManager->getStore($defaultStoreId)->getConfig($tempPath);

        $userInfo['country'] = $this->storeManager->getStore($defaultStoreId)->getConfig('general/country/default');
        // ---------------------------------------

        $requiredKeys = [
            'email',
            'firstname',
            'lastname',
            'country',
            'city',
            'postal_code',
        ];

        foreach ($userInfo as $key => $value) {
            if (!in_array($key, $requiredKeys)) {
                unset($userInfo[$key]);
            }
        }

        return $userInfo;
    }

    //########################################
}
