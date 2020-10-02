<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\ControlPanel\Module;

use Ess\M2ePro\Controller\Adminhtml\Context;
use Ess\M2ePro\Controller\Adminhtml\ControlPanel\Command;
use Ess\M2ePro\Helper\Component\Walmart;
use Ess\M2ePro\Helper\Component\Amazon;
use Ess\M2ePro\Helper\Component\Ebay;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\ControlPanel\Module\Integration
 */
class Integration extends Command
{
    /** @var \Magento\Framework\Data\Form\FormKey */
    private $formKey;

    //########################################

    public function __construct(
        \Magento\Framework\Data\Form\FormKey $formKey,
        Context $context
    ) {
        $this->formKey = $formKey;
        parent::__construct($context);
    }

    //########################################

    /**
     * @title "Print Request Data"
     * @description "Print [List/Relist/Revise] Request Data"
     */
    public function getRequestDataAction()
    {
        if ($this->getRequest()->getParam('print')) {
            $listingProductId = (int)$this->getRequest()->getParam('listing_product_id');
            $lp               = $this->activeRecordFactory->getObjectLoaded('Listing\Product', $listingProductId);
            $componentMode    = $lp->getComponentMode();
            $requestType      = $this->getRequest()->getParam('request_type');

            if ($componentMode == 'ebay') {
                $configurator = $this->modelFactory->getObject('Ebay_Listing_Product_Action_Configurator');

                /** @var \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Type\Request $request */
                $request = $this->modelFactory->getObject(
                    'Ebay_Listing_Product_Action_Type_'.$requestType.'_Request'
                );
                $request->setListingProduct($lp);
                $request->setConfigurator($configurator);

                // @codingStandardsIgnoreLine
                return '<pre>' . print_r($request->getRequestData(), true);
            }

            if ($componentMode == 'amazon') {
                $configurator = $this->modelFactory->getObject('Amazon_Listing_Product_Action_Configurator');

                /** @var \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Type\Request $request */
                $request = $this->modelFactory->getObject(
                    'Amazon_Listing_Product_Action_Type_'.$requestType.'_Request'
                );
                $request->setParams([]);
                $request->setListingProduct($lp);
                $request->setConfigurator($configurator);

                if ($requestType == 'ListAction') {
                    $request->setCachedData([
                        'sku'        => 'placeholder',
                        'general_id' => 'placeholder',
                        'list_type'  => 'placeholder'
                    ]);
                }

                // @codingStandardsIgnoreLine
                return '<pre>' . print_r($request->getRequestData(), true);
            }

            if ($componentMode == 'walmart') {
                $configurator = $this->modelFactory->getObject('Walmart_Listing_Product_Action_Configurator');

                $skuResolver = $this->modelFactory
                    ->getObject('Walmart_Listing_Product_Action_Type_ListAction_SkuResolver');
                $skuResolver->setListingProduct($lp);

                /** @var \Ess\M2ePro\Model\Walmart\Listing\Product\Action\Type\Request $request */
                $request = $this->modelFactory->getObject(
                    'Walmart_Listing_Product_Action_Type_'.$requestType.'_Request'
                );
                $request->setParams(['sku' => $skuResolver->resolve()]);
                $request->setListingProduct($lp);
                $request->setConfigurator($configurator);

                // @codingStandardsIgnoreLine
                return '<pre>' . print_r($request->getRequestData(), true);
            }

            return '';
        }

        $formKey = $this->formKey->getFormKey();
        $actionUrl = $this->getUrl('*/*/*', ['action' => 'getRequestData']);

        return <<<HTML
<form method="get" enctype="multipart/form-data" action="{$actionUrl}">

    <div style="margin: 5px 0; width: 400px;">
        <label style="width: 170px; display: inline-block;">Listing Product ID: </label>
        <input name="listing_product_id" style="width: 200px;" required>
    </div>

    <div style="margin: 5px 0; width: 400px;">
        <label style="width: 170px; display: inline-block;">Request Type: </label>
        <select name="request_type" style="width: 200px;" required>
            <option style="display: none;"></option>
            <option value="ListAction">List</option>
            <option value="Relist">Relist</option>
            <option value="Revise">Revise</option>
        </select>
    </div>

    <input name="form_key" value="{$formKey}" type="hidden" />
    <input name="print" value="1" type="hidden" />

    <div style="margin: 10px 0; width: 365px; text-align: right;">
        <button type="submit">Show</button>
    </div>

</form>
HTML;
    }

    /**
     * @title "Print Inspector Data"
     * @description "Print Inspector Data"
     */
    public function getInspectorDataAction()
    {
        if ($this->getRequest()->getParam('print')) {
            $listingProductId = $this->getRequest()->getParam('listing_product_id');

            $instructionCollection = $this->activeRecordFactory->getObject(
                'Listing_Product_Instruction'
            )->getCollection();
            $instructionCollection->applySkipUntilFilter();
            $instructionCollection->addFieldToFilter('listing_product_id', $listingProductId);
            $lp = $this->activeRecordFactory->getObject('Listing\Product')->load($listingProductId);

            if ($lp->getComponentMode() == 'ebay') {
                $lp->setChildMode(Ebay::NICK);

                /**@var \Ess\M2ePro\Model\Listing\Product $lp */
               // $lp = $this->parentFactory->getObjectLoaded(Ebay::NICK, 'Listing\Product', $listingProductId);

                $checkerInput = $this->modelFactory->getObject(
                    'Listing_Product_Instruction_SynchronizationTemplate_Checker_Input'
                );
                $checkerInput->setListingProduct($lp);

                $instructions = [];
                foreach ($instructionCollection->getItems() as $instruction) {
                    /**@var \Ess\M2ePro\Model\Listing\Product\Instruction $instruction */
                    $instruction->setListingProduct($lp);
                    $instructions[$instruction->getId()] = $instruction;
                }
                $checkerInput->setInstructions($instructions);

                $html = '<pre>';

                //--
                $checker = $this->modelFactory->getObject(
                    'Ebay_Listing_Product_Instruction_SynchronizationTemplate_Checker_NotListed'
                );
                $checker->setInput($checkerInput);

                $html .= '<b>NotListed</b><br>';
                $html .= 'isAllowed: '.json_encode($checker->isAllowed()).'<br>';
                $html .= 'isMeetList: '.json_encode($checker->isMeetListRequirements()).'<br><br>';
                //--

                //--
                $checker = $this->modelFactory->getObject(
                    'Ebay_Listing_Product_Instruction_SynchronizationTemplate_Checker_Inactive'
                );
                $checker->setInput($checkerInput);

                $html .= '<b>Inactive</b><br>';
                $html .= 'isAllowed: '.json_encode($checker->isAllowed()).'<br>';
                $html .= 'isMeetRelist: '.json_encode($checker->isMeetRelistRequirements()).'<br><br>';
                //--

                //--
                $checker = $this->modelFactory->getObject(
                    'Ebay_Listing_Product_Instruction_SynchronizationTemplate_Checker_Active'
                );
                $checker->setInput($checkerInput);

                $html .= '<b>Active</b><br>';
                $html .= 'isAllowed: '.json_encode($checker->isAllowed()).'<br>';
                $html .= 'isMeetStop: '.json_encode($checker->isMeetStopRequirements()).'<br><br>';

                $html .= 'isMeetReviseQty: '.json_encode($checker->isMeetReviseQtyRequirements()).'<br>';
                $html .= 'isMeetRevisePrice: '.json_encode($checker->isMeetRevisePriceRequirements()).'<br>';
                $html .= 'isMeetReviseTitle: '.json_encode($checker->isMeetReviseTitleRequirements()).'<br>';
                $html .= 'isMeetReviseSubtitle: '.json_encode($checker->isMeetReviseSubtitleRequirements()).'<br>';
                $html .='isMeetReviseDescription: '.json_encode($checker->isMeetReviseDescriptionRequirements()).'<br>';
                $html .= 'isMeetReviseImages: '.json_encode($checker->isMeetReviseImagesRequirements()).'<br>';
                $html .= 'isMeetReviseCategories: '.json_encode($checker->isMeetReviseCategoriesRequirements()).'<br>';
                $html .= 'isMeetRevisePayment: '.json_encode($checker->isMeetRevisePaymentRequirements()).'<br>';
                $html .= 'isMeetReviseShipping: '.json_encode($checker->isMeetReviseShippingRequirements()).'<br>';
                $html .= 'isMeetReviseReturn: '.json_encode($checker->isMeetReviseReturnRequirements()).'<br>';
                $html .= 'isMeetReviseOther: '.json_encode($checker->isMeetReviseOtherRequirements()).'<br><br>';

                /** @var \Ess\M2ePro\Model\Ebay\Listing\Product $elp */
                $elp = $lp->getChildObject();
                $html .= 'isSetCategoryTemplate: ' .json_encode($elp->isSetCategoryTemplate()).'<br>';
                $html .= 'isInAction: ' .json_encode($lp->isSetProcessingLock('in_action')). '<br><br>';

                $magentoProduct = $lp->getMagentoProduct();
                $html .= 'isStatusEnabled: ' .json_encode($magentoProduct->isStatusEnabled()).'<br>';
                $html .= 'isStockAvailability: ' .json_encode($magentoProduct->isStockAvailability()).'<br>';
                //--

                return $this->getResponse()->setBody($html);
            }

            if ($lp->getComponentMode() == 'amazon') {
                $lp->setChildMode(Amazon::NICK);

                $checkerInput = $this->modelFactory->getObject(
                    'Listing_Product_Instruction_SynchronizationTemplate_Checker_Input'
                );
                $checkerInput->setListingProduct($lp);

                $instructions = [];
                foreach ($instructionCollection->getItems() as $instruction) {
                    /**@var \Ess\M2ePro\Model\Listing\Product\Instruction $instruction */
                    $instruction->setListingProduct($lp);
                    $instructions[$instruction->getId()] = $instruction;
                }
                $checkerInput->setInstructions($instructions);

                $html = '<pre>';

                //--
                $checker = $this->modelFactory->getObject(
                    'Amazon_Listing_Product_Instruction_SynchronizationTemplate_Checker_NotListed'
                );
                $checker->setInput($checkerInput);

                $html .= '<b>NotListed</b><br>';
                $html .= 'isAllowed: '.json_encode($checker->isAllowed()).'<br>';
                $html .= 'isMeetList: '.json_encode($checker->isMeetListRequirements()).'<br><br>';
                //--

                //--
                $checker = $this->modelFactory->getObject(
                    'Amazon_Listing_Product_Instruction_SynchronizationTemplate_Checker_Inactive'
                );
                $checker->setInput($checkerInput);

                $html .= '<b>Inactive</b><br>';
                $html .= 'isAllowed: '.json_encode($checker->isAllowed()).'<br>';
                $html .= 'isMeetRelist: '.json_encode($checker->isMeetRelistRequirements()).'<br><br>';
                //--

                //--
                $checker = $this->modelFactory->getObject(
                    'Amazon_Listing_Product_Instruction_SynchronizationTemplate_Checker_Active'
                );
                $checker->setInput($checkerInput);

                $html .= '<b>Active</b><br>';
                $html .= 'isAllowed: '.json_encode($checker->isAllowed()).'<br>';
                $html .= 'isMeetStop: '.json_encode($checker->isMeetStopRequirements()).'<br><br>';

                $html .= 'isMeetReviseQty: '.json_encode($checker->isMeetReviseQtyRequirements()).'<br>';
                $html .= 'isMeetRevisePriceReg: '.json_encode($checker->isMeetRevisePriceRegularRequirements()).'<br>';
                $html .= 'isMeetRevisePriceBus: '.json_encode($checker->isMeetRevisePriceBusinessRequirements()).'<br>';
                $html .= 'isMeetReviseDetails: '.json_encode($checker->isMeetReviseDetailsRequirements()).'<br>';
                $html .= 'isMeetReviseImages: '.json_encode($checker->isMeetReviseImagesRequirements()).'<br><br>';
                //--

                //--
                $magentoProduct = $lp->getMagentoProduct();
                $html .= 'isStatusEnabled: '.json_encode($magentoProduct->isStatusEnabled()).'<br>';
                $html .= 'isStockAvailability: '.json_encode($magentoProduct->isStockAvailability()).'<br>';
                //--

                return $this->getResponse()->setBody($html);
            }

            if ($lp->getComponentMode() == 'walmart') {
                $lp->setChildMode(Walmart::NICK);

                $checkerInput = $this->modelFactory->getObject(
                    'Listing_Product_Instruction_SynchronizationTemplate_Checker_Input'
                );
                $checkerInput->setListingProduct($lp);

                $instructions = [];
                foreach ($instructionCollection->getItems() as $instruction) {
                    /**@var \Ess\M2ePro\Model\Listing\Product\Instruction $instruction */
                    $instruction->setListingProduct($lp);
                    $instructions[$instruction->getId()] = $instruction;
                }
                $checkerInput->setInstructions($instructions);

                $html = '<pre>';

                //--
                $checker = $this->modelFactory->getObject(
                    'Walmart_Listing_Product_Instruction_SynchronizationTemplate_Checker_NotListed'
                );
                $checker->setInput($checkerInput);

                $html .= '<b>NotListed</b><br>';
                $html .= 'isAllowed: '.json_encode($checker->isAllowed()).'<br>';
                $html .= 'isMeetList: '.json_encode($checker->isMeetListRequirements()).'<br><br>';
                //--

                //--
                $checker = $this->modelFactory->getObject(
                    'Walmart_Listing_Product_Instruction_SynchronizationTemplate_Checker_Inactive'
                );
                $checker->setInput($checkerInput);

                $html .= '<b>Inactive</b><br>';
                $html .= 'isAllowed: '.json_encode($checker->isAllowed()).'<br>';
                $html .= 'isMeetRelist: '.json_encode($checker->isMeetRelistRequirements()).'<br><br>';
                //--

                //--
                $checker = $this->modelFactory->getObject(
                    'Walmart_Listing_Product_Instruction_SynchronizationTemplate_Checker_Active'
                );
                $checker->setInput($checkerInput);

                $html .= '<b>Active</b><br>';
                $html .= 'isAllowed: '.json_encode($checker->isAllowed()).'<br>';
                $html .= 'isMeetStop: '.json_encode($checker->isMeetStopRequirements()).'<br><br>';

                $html .= 'isMeetReviseQty: '.json_encode($checker->isMeetReviseQtyRequirements()).'<br>';
                $html .= 'isMeetRevisePrice: '.json_encode($checker->isMeetRevisePriceRequirements()).'<br>';
                $html .= 'isMeetRevisePromotions: '.json_encode($checker->isMeetRevisePromotionsRequirements()).'<br>';
                $html .= 'isMeetReviseDetails: '.json_encode($checker->isMeetReviseDetailsRequirements()).'<br>';
                //--

                //--
                $magentoProduct = $lp->getMagentoProduct();
                $html .= 'isStatusEnabled: '.json_encode($magentoProduct->isStatusEnabled()).'<br>';
                $html .= 'isStockAvailability: '.json_encode($magentoProduct->isStockAvailability()).'<br>';
                //--

                return $this->getResponse()->setBody($html);
            }

            return '';
        }

        $formKey = $this->formKey->getFormKey();
        $actionUrl = $this->getUrl('*/*/*', ['action' => 'getInspectorData']);

        return <<<HTML
<form method="get" enctype="multipart/form-data" action="{$actionUrl}">

    <div style="margin: 5px 0; width: 400px;">
        <label style="width: 170px; display: inline-block;">Listing Product ID: </label>
        <input name="listing_product_id" style="width: 200px;" required>
    </div>

    <input name="form_key" value="{$formKey}" type="hidden" />
    <input name="print" value="1" type="hidden" />

    <div style="margin: 10px 0; width: 365px; text-align: right;">
        <button type="submit">Show</button>
    </div>

</form>
HTML;
    }

    //########################################

    /**
     * @title "Build Order Quote"
     * @description "Print Order Quote Data"
     */
    public function getPrintOrderQuoteDataAction()
    {
        if ($this->getRequest()->getParam('print')) {

            /** @var \Ess\M2ePro\Model\Order $order */
            $orderId = $this->getRequest()->getParam('order_id');
            $order = $this->activeRecordFactory->getObjectLoaded('Order', $orderId);

            if (!$order->getId()) {
                $this->getMessageManager()->addError('Unable to load order instance.');
                $this->_redirect($this->getHelper('View\ControlPanel')->getPageModuleTabUrl());
                return;
            }

            // Store must be initialized before products
            // ---------------------------------------
            $order->associateWithStore();
            $order->associateItemsWithProducts();
            // ---------------------------------------

            $proxy = $order->getProxy()->setStore($order->getStore());

            /** @var \Ess\M2ePro\Model\Magento\Quote\Builder $magentoQuoteBuilder */
            $magentoQuoteBuilder = $this->modelFactory->getObject('Magento_Quote_Builder', ['proxyOrder' => $proxy]);
            /** @var  \Ess\M2ePro\Model\Magento\Quote\Manager $magentoQuoteManager */
            $magentoQuoteManager = $this->modelFactory->getObject('Magento_Quote_Manager');

            $quote = $magentoQuoteBuilder->build();

            $shippingAddressData = $quote->getShippingAddress()->getData();
            unset(
                $shippingAddressData['cached_items_all'],
                $shippingAddressData['cached_items_nominal'],
                $shippingAddressData['cached_items_nonnominal']
            );
            $billingAddressData  = $quote->getBillingAddress()->getData();
            unset(
                $billingAddressData['cached_items_all'],
                $billingAddressData['cached_items_nominal'],
                $billingAddressData['cached_items_nonnominal']
            );
            $quoteData = $quote->getData();
            unset(
                $quoteData['items'],
                $quoteData['extension_attributes']
            );

            $items = [];
            foreach ($quote->getAllItems() as $item) {
                $items[] = $item->getData();
            }

            $magentoQuoteManager->save($quote->setIsActive(false));

            return print_r(json_decode(json_encode([
                'Grand Total'           => $quote->getGrandTotal(),
                'Shipping Amount'       => $quote->getShippingAddress()->getShippingAmount(),
                'Quote Data'            => $quoteData,
                'Shipping Address Data' => $shippingAddressData,
                'Billing Address Data'  => $billingAddressData,
                'Items'                 => $items
            ]), true), true);
        }

        $formKey = $this->formKey->getFormKey();
        $actionUrl = $this->getUrl('*/*/*', ['action' => 'getPrintOrderQuoteData']);

        return <<<HTML
<form method="get" enctype="multipart/form-data" action="{$actionUrl}">

    <div style="margin: 5px 0; width: 400px;">
        <label style="width: 170px; display: inline-block;">Order ID: </label>
        <input name="order_id" style="width: 200px;" required>
    </div>

    <input name="form_key" value="{$formKey}" type="hidden" />
    <input name="print" value="1" type="hidden" />

    <div style="margin: 10px 0; width: 365px; text-align: right;">
        <button type="submit">Build</button>
    </div>

</form>
HTML;
    }

    //########################################
}
