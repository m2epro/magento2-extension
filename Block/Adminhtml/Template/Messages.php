<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Template;

use Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock;

class Messages extends AbstractBlock
{
    const TYPE_ATTRIBUTES_AVAILABILITY = 'attributes_availability';

    protected $templateNick = NULL;
    protected $componentMode = NULL;

    //########################################

    public function getResultBlock($templateNick, $componentMode)
    {
        $block = $this;

        switch ($templateNick) {

            case \Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_SHIPPING:

                $isPriceConvertEnabled = (int)$this->getHelper('Module')->getConfig()->getGroupValue(
                    '/magento/attribute/', 'price_type_converting'
                );

                if ($isPriceConvertEnabled && $componentMode == \Ess\M2ePro\Helper\Component\Ebay::NICK) {
                    $block = $this->createBlock('Ebay\Template\Shipping\Messages');
                }
                break;

            case \Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_SELLING_FORMAT:
                $block = $this->createBlock('Template\SellingFormat\Messages');
                break;
        }

        $block->setComponentMode($componentMode);
        $block->setTemplateNick($templateNick);

        return $block;
    }

    //########################################

    public function getMessages()
    {
        $messages = array();

        // ---------------------------------------
        if (!is_null($message = $this->getAttributesAvailabilityMessage())) {
            $messages[self::TYPE_ATTRIBUTES_AVAILABILITY] = $message;
        }
        // ---------------------------------------

        return $messages;
    }

    //########################################

    public function getMessagesHtml(array $messages = array())
    {
        if (empty($messages)) {
            $messages = $this->getMessages();
        }

        if (empty($messages)) {
            return '';
        }

        $messagesBlock = $this->getLayout()->createBlock('Magento\Framework\View\Element\Messages');

        $first = true;
        foreach ($messages as $messageType => $messageText) {
            $message = '';
            if ($first) {
                $first = false;
                $message .= <<<HTML
<div style="display: inline-block; float: right;">
    <a href="javascript: void(0);" class="refresh-messages">[{$this->__('Refresh')}]</a>
</div>
HTML;
            }
            $message .= $messageText;
            $messagesBlock->addWarning($message);
        }

        return $messagesBlock->toHtml();
    }

    //########################################

    public function getAttributesAvailabilityMessage()
    {
        if (!$this->canDisplayAttributesAvailabilityMessage()) {
            return NULL;
        }

        $productIds = $this->activeRecordFactory->getObject('Listing\Product')->getResource()
            ->getProductIds($this->getListingProductIds());
        $attributeSets = $this->getHelper('Magento\Attribute')
            ->getSetsFromProductsWhichLacksAttributes($this->getUsedAttributes(), $productIds);

        if (count($attributeSets) == 0) {
            return NULL;
        }

        $attributeSetsNames = $this->getHelper('Magento\AttributeSet')->getNames($attributeSets);

        // M2ePro_TRANSLATIONS
        // Some attributes which are used in this Policy were not found in Products Settings. Please, check if all of them are in [%set_name%] Attribute Set(s) as it can cause List, Revise or Relist issues.
        return
            $this->__(
                'Some Attributes which are used in this Policy were not found in Products Settings.'
                . ' Please, check if all of them are in [%set_name%] Attribute Set(s)'
                . ' as it can cause List, Revise or Relist issues.'
            ,
            implode('", "', $attributeSetsNames)
        );
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Marketplace|null
     */
    public function getMarketplace()
    {
        if (!isset($this->_data['marketplace_id'])) {
            return NULL;
        }

        return $this->parentFactory->getCachedObjectLoaded(
            $this->getComponentMode(),
            'Marketplace',
            (int)$this->_data['marketplace_id']
        );
    }

    //########################################

    /**
     * @return \Magento\Store\Model\Store|null
     */
    public function getStore()
    {
        if (!isset($this->_data['store_id'])) {
            return NULL;
        }

        return $this->_storeManager->getStore((int)$this->_data['store_id']);
    }

    //########################################

    public function setTemplateNick($templateNick)
    {
        $this->templateNick = $templateNick;
        return $this;
    }

    public function getTemplateNick()
    {
        if (is_null($this->templateNick)) {
            throw new \Ess\M2ePro\Model\Exception\Logic('Policy nick is not set.');
        }

        return $this->templateNick;
    }

    //########################################

    public function setComponentMode($componentMode)
    {
        $this->componentMode = $componentMode;
        return $this;
    }

    public function getComponentMode()
    {
        if (is_null($this->componentMode)) {
            throw new \Ess\M2ePro\Model\Exception\Logic('Component Mode is not set.');
        }

        return $this->componentMode;
    }

    //########################################

    protected function getTemplateData()
    {
        if (empty($this->_data['template_data']) || !is_array($this->_data['template_data'])) {
            throw new \Ess\M2ePro\Model\Exception\Logic('Policy data is not set.');
        }

        return $this->_data['template_data'];
    }

    //########################################

    protected function getUsedAttributes()
    {
        return isset($this->_data['used_attributes']) ? $this->_data['used_attributes'] : array();
    }

    //########################################

    protected function getListingProductIds()
    {
        $listingProductIds = $this->getRequest()->getParam('listing_product_ids', '');
        $listingProductIds = explode(',', $listingProductIds);

        return $listingProductIds ? $listingProductIds : array();
    }

    //########################################

    protected function canDisplayAttributesAvailabilityMessage()
    {
        if (!$this->getRequest()->getParam('check_attributes_availability')) {
            return false;
        }

        if (is_null($this->componentMode) || $this->componentMode != \Ess\M2ePro\Helper\Component\Ebay::NICK) {
            return false;
        }

        $listingProductIds = $this->getListingProductIds();

        if (empty($listingProductIds)) {
            return false;
        }

        return true;
    }

    //########################################
}