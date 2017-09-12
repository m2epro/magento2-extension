<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Helper\Component\Ebay\Template\Switcher;

class DataLoader extends \Ess\M2ePro\Helper\AbstractHelper
{
    private $ebayFactory;
    private $storeManager;
    private $templateManager;
    private $templateManagerFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Ess\M2ePro\Model\Ebay\Template\Manager $templateManager,
        \Ess\M2ePro\Model\Ebay\Template\ManagerFactory $templateManagerFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Framework\App\Helper\Context $context
    )
    {
        $this->ebayFactory = $ebayFactory;
        $this->storeManager = $storeManager;
        $this->templateManager = $templateManager;
        $this->templateManagerFactory = $templateManagerFactory;
        parent::__construct($helperFactory, $context);
    }

    //########################################

    public function load($source, array $params = array())
    {
        $data = NULL;

        if ($source instanceof \Ess\M2ePro\Helper\Data\Session) {
            $data = $this->getDataFromSession($source, $params);
        }
        if ($source instanceof \Ess\M2ePro\Model\Listing) {
            $data = $this->getDataFromListing($source, $params);
        }
        if ($source instanceof \Ess\M2ePro\Model\ResourceModel\Listing\Product\Collection) {
            $data = $this->getDataFromListingProducts($source, $params);
        }
        if ($this->isTemplateInstance($source)) {
            $data = $this->getDataFromTemplate($source, $params);
        }
        if ($source instanceof \Magento\Framework\App\RequestInterface) {
            $data = $this->getDataFromRequest($source, $params);
        }

        if (is_null($data)) {
            throw new \InvalidArgumentException('Data source is invalid.');
        }

        $account = NULL;
        if ($data['account_id']) {
            $account = $this->ebayFactory->getCachedObjectLoaded('Account', $data['account_id']);
        }

        $marketplace = NULL;
        if ($data['marketplace_id']) {
            $marketplace = $this->ebayFactory->getCachedObjectLoaded('Marketplace', $data['marketplace_id']);
        }

        $storeId = (int)$data['store_id'];

        $attributeSets = $data['attribute_sets'];
        $attributes = $this->getHelper('Magento\Attribute')->getGeneralFromAttributeSets($attributeSets);

        $displayUseDefaultOption = $data['display_use_default_option'];

        $global = $this->getHelper('Data\GlobalData');

        $global->setValue('ebay_account', $account);
        $global->setValue('ebay_marketplace', $marketplace);
        $global->setValue('ebay_store', $this->storeManager->getStore($storeId));
        $global->setValue('ebay_attribute_sets', $attributeSets);
        $global->setValue('ebay_attributes', $attributes);
        $global->setValue('ebay_display_use_default_option', $displayUseDefaultOption);

        foreach ($data['templates'] as $nick => $templateData) {

            $template = $this->templateManager->setTemplate($nick)->getTemplateModel();

            if ($templateData['id']) {
                $template->load($templateData['id']);
            }

            $global->setValue("ebay_template_{$nick}", $template);
            $global->setValue("ebay_template_mode_{$nick}", $templateData['mode']);
            $global->setValue("ebay_template_force_parent_{$nick}", $templateData['force_parent']);
        }
    }

    //########################################

    private function getDataFromSession(\Ess\M2ePro\Helper\Data\Session $source, array $params = array())
    {
        if (!isset($params['session_key'])) {
            throw new \Ess\M2ePro\Model\Exception\Logic('Session key is not defined.');
        }
        $sessionKey = $params['session_key'];
        $sessionData = $source->getValue($sessionKey);

        $accountId = isset($sessionData['account_id']) ? $sessionData['account_id'] : NULL;
        $marketplaceId = isset($sessionData['marketplace_id']) ? $sessionData['marketplace_id'] : NULL;
        $storeId = isset($sessionData['store_id']) ? $sessionData['store_id'] : NULL;
        $attributeSets = $this->getHelper('Magento\AttributeSet')
            ->getAll(\Ess\M2ePro\Helper\Magento\AbstractHelper::RETURN_TYPE_IDS);

        $templates = array();

        foreach ($this->templateManager->getAllTemplates() as $nick) {
            $templateId = isset($sessionData["template_id_{$nick}"]) ? $sessionData["template_id_{$nick}"] : NULL;
            $templateMode = isset($sessionData["template_id_{$nick}"]) ? $sessionData["template_mode_{$nick}"] : NULL;

            if (empty($templateMode)) {
                $templateMode = \Ess\M2ePro\Model\Ebay\Template\Manager::MODE_CUSTOM;
            }

            $templates[$nick] = array(
                'id' => $templateId,
                'mode' => $templateMode,
                'force_parent' => false
            );
        }

        return array(
            'account_id'                 => $accountId,
            'marketplace_id'             => $marketplaceId,
            'store_id'                   => $storeId,
            'attribute_sets'             => $attributeSets,
            'display_use_default_option' => false,
            'templates'                  => $templates
        );
    }

    private function getDataFromListing(\Ess\M2ePro\Model\Listing $source, array $params = array())
    {
        $accountId = $source->getAccountId();
        $marketplaceId = $source->getMarketplaceId();
        $storeId = $source->getStoreId();
        $attributeSets = $this->getHelper('Magento\AttributeSet')
            ->getAll(\Ess\M2ePro\Helper\Magento\AbstractHelper::RETURN_TYPE_IDS);

        $templates = array();

        foreach ($this->templateManager->getAllTemplates() as $nick) {
            $manager = $this->templateManagerFactory->create()
                ->setTemplate($nick)
                ->setOwnerObject($source->getChildObject());

            $templateId = $manager->getIdColumnValue();
            $templateMode = $manager->getModeValue();

            $templates[$nick] = array(
                'id' => $templateId,
                'mode' => $templateMode,
                'force_parent' => false
            );
        }

        return array(
            'account_id'                 => $accountId,
            'marketplace_id'             => $marketplaceId,
            'store_id'                   => $storeId,
            'attribute_sets'             => $attributeSets,
            'display_use_default_option' => false,
            'templates'                  => $templates
        );
    }

    private function getDataFromListingProducts($source, array $params = array())
    {
        /** @var \Ess\M2ePro\Model\Listing\Product $listingProductFirst */
        $listingProductFirst = $source->getFirstItem();

        $productIds = array();
        foreach ($source as $listingProduct) {
            $productIds[] = $listingProduct->getData('product_id');
        }

        $accountId = $listingProductFirst->getListing()->getAccountId();
        $marketplaceId = $listingProductFirst->getListing()->getMarketplaceId();
        $storeId = $listingProductFirst->getListing()->getStoreId();
        $attributeSets = $this->getHelper('Magento\AttributeSet')
            ->getFromProducts($productIds, \Ess\M2ePro\Helper\Magento\AbstractHelper::RETURN_TYPE_IDS);

        $templates = array();

        foreach ($this->templateManager->getAllTemplates() as $nick) {
            $templateId = NULL;
            $templateMode = NULL;
            $forceParent = false;

            if ($source->count() <= 200) {
                foreach ($source as $listingProduct) {
                    $manager = $this->templateManagerFactory->create()
                        ->setTemplate($nick)
                        ->setOwnerObject($listingProduct->getChildObject());

                    $currentProductTemplateId = $manager->getIdColumnValue();
                    $currentProductTemplateMode = $manager->getModeValue();

                    if (is_null($templateId) && is_null($templateMode)) {
                        $templateId = $currentProductTemplateId;
                        $templateMode = $currentProductTemplateMode;
                        continue;
                    }

                    if ($templateId != $currentProductTemplateId || $templateMode != $currentProductTemplateMode) {
                        $templateId = NULL;
                        $templateMode = \Ess\M2ePro\Model\Ebay\Template\Manager::MODE_PARENT;
                        $forceParent = true;
                        break;
                    }
                }
            } else {
                $forceParent = true;
            }

            if (is_null($templateMode)) {
                $templateMode = \Ess\M2ePro\Model\Ebay\Template\Manager::MODE_PARENT;
            }

            $templates[$nick] = array(
                'id' => $templateId,
                'mode' => $templateMode,
                'force_parent' => $forceParent
            );
        }

        return array(
            'account_id'                 => $accountId,
            'marketplace_id'             => $marketplaceId,
            'store_id'                   => $storeId,
            'attribute_sets'             => $attributeSets,
            'display_use_default_option' => true,
            'templates'                  => $templates
        );
    }

    private function getDataFromTemplate($source, array $params = array())
    {
        $attributeSets = $this->getHelper('Magento\AttributeSet')
            ->getAll(\Ess\M2ePro\Helper\Magento\AbstractHelper::RETURN_TYPE_IDS);

        $marketplaceId = NULL;
        if (isset($params['marketplace_id'])) {
            $marketplaceId = (int)$params['marketplace_id'];
        }

        $nick = $this->getTemplateNick($source);

        return array(
            'account_id'                 => NULL,
            'marketplace_id'             => $marketplaceId,
            'store_id'                   => \Magento\Store\Model\Store::DEFAULT_STORE_ID,
            'attribute_sets'             => $attributeSets,
            'display_use_default_option' => true,
            'templates'                  => array(
                $nick => array(
                    'id' => $source->getId(),
                    'mode' => \Ess\M2ePro\Model\Ebay\Template\Manager::MODE_TEMPLATE,
                    'force_parent' => false
                )
            )
        );
    }

    private function getDataFromRequest(\Magento\Framework\App\RequestInterface $source, array $params = array())
    {
        $id   = $source->getParam('id');
        $nick = $source->getParam('nick');
        $mode = $source->getParam('mode', \Ess\M2ePro\Model\Ebay\Template\Manager::MODE_CUSTOM);

        $attributeSets = $source->getParam('attribute_sets', '');
        $attributeSets = array_filter(explode(',', $attributeSets));

        if (empty($attributeSets)) {
            $attributeSets = $this->getHelper('Magento\AttributeSet')
                ->getAll(\Ess\M2ePro\Helper\Magento\AbstractHelper::RETURN_TYPE_IDS);
        }

        return array(
            'account_id'                 => $source->getParam('account_id'),
            'marketplace_id'             => $source->getParam('marketplace_id'),
            'store_id'                   => \Magento\Store\Model\Store::DEFAULT_STORE_ID,
            'attribute_sets'             => $attributeSets,
            'display_use_default_option' => (bool)$source->getParam('display_use_default_option'),
            'templates'                  => array(
                $nick => array(
                    'id' => $id,
                    'mode' => $mode,
                    'force_parent' => false
                )
            )
        );
    }

    //########################################

    private function getTemplateNick($source)
    {
        if (!$this->isHorizontalTemplate($source)) {
            return $source->getNick();
        }

        $nick = null;

        if ($source instanceof \Ess\M2ePro\Model\Template\SellingFormat) {
            $nick = \Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_SELLING_FORMAT;
        } elseif ($source instanceof \Ess\M2ePro\Model\Template\Synchronization) {
            $nick = \Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_SYNCHRONIZATION;
        } elseif ($source instanceof \Ess\M2ePro\Model\Template\Description) {
            $nick = \Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_DESCRIPTION;
        }

        return $nick;
    }

    private function isTemplateInstance($source)
    {
        if ($source instanceof \Ess\M2ePro\Model\Ebay\Template\Payment
            || $source instanceof \Ess\M2ePro\Model\Ebay\Template\Shipping
            || $source instanceof \Ess\M2ePro\Model\Ebay\Template\ReturnPolicy
            || $source instanceof \Ess\M2ePro\Model\Template\SellingFormat
            || $source instanceof \Ess\M2ePro\Model\Template\Description
            || $source instanceof \Ess\M2ePro\Model\Template\Synchronization
        ) {
            return true;
        }

        return false;
    }

    private function isHorizontalTemplate($source)
    {
        if ($source instanceof \Ess\M2ePro\Model\Template\SellingFormat ||
            $source instanceof \Ess\M2ePro\Model\Template\Synchronization ||
            $source instanceof \Ess\M2ePro\Model\Template\Description) {

            return true;
        }

        return false;
    }

    //########################################
}