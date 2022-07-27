<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing;

class View extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing
{
    /** @var \Ess\M2ePro\Helper\Data\GlobalData */
    private $globalData;

    /** @var \Ess\M2ePro\Helper\Data\Session */
    private $sessionHelper;

    public function __construct(
        \Ess\M2ePro\Helper\Data\GlobalData $globalData,
        \Ess\M2ePro\Helper\Data\Session $sessionHelper,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($ebayFactory, $context);

        $this->globalData = $globalData;
        $this->sessionHelper = $sessionHelper;
    }

    public function execute()
    {
        if ($this->getRequest()->getQuery('ajax')) {
            $id = $this->getRequest()->getParam('id');
            $listing = $this->ebayFactory->getCachedObjectLoaded('Listing', $id);

            $this->globalData->setValue('view_listing', $listing);

            // Set rule model
            // ---------------------------------------
            $this->setRuleData('ebay_rule_view_listing');
            // ---------------------------------------

            $this->setAjaxContent(
                $this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\Ebay\Listing\View::class)->getGridHtml()
            );
            return $this->getResult();
        }

        if ((bool)$this->getRequest()->getParam('do_list', false)) {
            $this->sessionHelper->setValue(
                'products_ids_for_list',
                implode(',', $this->sessionHelper->getValue('added_products_ids'))
            );

            return $this->_redirect('*/*/*', [
                '_current'  => true,
                'do_list'   => null,
                'view_mode' => \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\View\Switcher::VIEW_MODE_EBAY
            ]);
        }

        $id = $this->getRequest()->getParam('id');

        try {
            $listing = $this->ebayFactory->getCachedObjectLoaded('Listing', $id);
        } catch (\LogicException $e) {
            $this->getMessageManager()->addError($this->__('Listing does not exist.'));
            return $this->_redirect('*/ebay_listing/index');
        }

        $productAddIds = $listing->getChildObject()->getData('product_add_ids');
        $productAddIds = array_filter((array)$this->getHelper('Data')->jsonDecode($productAddIds));

        if (!empty($productAddIds)) {
            $this->getMessageManager()->addNotice($this->__(
                'Please make sure you finish adding new Products before moving to the next step.'
            ));

            return $this->_redirect('*/ebay_listing_product_category_settings', ['id' => $id, 'step' => 1]);
        }

        $this->globalData->setValue('view_listing', $listing);

        // Set rule model
        // ---------------------------------------
        $this->setRuleData('ebay_rule_view_listing');
        // ---------------------------------------

        $this->setPageHelpLink('x/Fv8UB');

        $this->getResultPage()->getConfig()->getTitle()->prepend(
            $this->__('M2E Pro Listing "%listing_title%"', $listing->getTitle())
        );

        $this->addContent($this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\Ebay\Listing\View::class));

        return $this->getResult();
    }

    protected function setRuleData($prefix)
    {
        $listingData = $this->globalData->getValue('view_listing');

        $storeId = isset($listingData['store_id']) ? (int)$listingData['store_id'] : 0;
        $prefix .= isset($listingData['id']) ? '_'.$listingData['id'] : '';
        $this->globalData->setValue('rule_prefix', $prefix);

        $ruleModel = $this->activeRecordFactory->getObject('Ebay_Magento_Product_Rule')->setData(
            [
                'prefix' => $prefix,
                'store_id' => $storeId,
            ]
        );

        $ruleParam = $this->getRequest()->getPost('rule');
        if (!empty($ruleParam)) {
            $this->sessionHelper->setValue(
                $prefix,
                $ruleModel->getSerializedFromPost($this->getRequest()->getPostValue())
            );
        } elseif ($ruleParam !== null) {
            $this->sessionHelper->setValue($prefix, []);
        }

        $sessionRuleData = $this->sessionHelper->getValue($prefix);
        if (!empty($sessionRuleData)) {
            $ruleModel->loadFromSerialized($sessionRuleData);
        }

        $this->globalData->setValue('rule_model', $ruleModel);
    }
}
