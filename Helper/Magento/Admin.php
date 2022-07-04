<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Helper\Magento;

class Admin extends AbstractHelper
{
    /** @var \Magento\User\Model\User */
    private $user;
    /** @var \Magento\Store\Model\StoreManagerInterface */
    private $storeManager;
    /** @var \Magento\Backend\Model\Auth\Session */
    private $authSession;
    /** @var \Ess\M2ePro\Helper\Magento\Store */
    private $magentoStore;

    /**
     * @param \Ess\M2ePro\Helper\Magento\Store $magentoStore
     * @param \Magento\User\Model\User $user
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Backend\Model\Auth\Session $authSession
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     */
    public function __construct(
        \Ess\M2ePro\Helper\Magento\Store $magentoStore,
        \Magento\User\Model\User $user,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Backend\Model\Auth\Session $authSession,
        \Magento\Framework\ObjectManagerInterface $objectManager
    ) {
        parent::__construct($objectManager);
        $this->user = $user;
        $this->storeManager = $storeManager;
        $this->authSession = $authSession;
        $this->magentoStore = $magentoStore;
    }

    /**
     * @return array|mixed|null
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getCurrentInfo()
    {
        $defaultStoreId = $this->magentoStore->getDefaultStoreId();

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
}
