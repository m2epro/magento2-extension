<?php

namespace Ess\M2ePro\Controller\Adminhtml\ControlPanel\Module;

use Ess\M2ePro\Controller\Adminhtml\Context;
use Ess\M2ePro\Controller\Adminhtml\ControlPanel\Command;
use Ess\M2ePro\Helper\Component\Amazon;
use Ess\M2ePro\Helper\Component\Ebay;
use Ess\M2ePro\Helper\Component\Walmart;

class Integration extends Command
{
    /** @var \Magento\Framework\Data\Form\FormKey */
    private $formKey;

    public function __construct(
        \Magento\Framework\Data\Form\FormKey $formKey,
        \Ess\M2ePro\Helper\View\ControlPanel $controlPanelHelper,
        Context $context
    ) {
        parent::__construct($controlPanelHelper, $context);
        $this->formKey = $formKey;
    }

    /**
     * @title "Print Request Data"
     * @description "Print [List/Relist/Revise] Request Data"
     */
    public function getRequestDataAction()
    {
        $listingProductId = $this->getRequest()->getParam('listing_product_id', '');
        $requestType = $this->getRequest()->getParam('request_type', '');

        $resultBlockHtml = '';
        if (!empty($listingProductId) && !empty($requestType)) {
            /** @var \Ess\M2ePro\Model\Listing\Product $lp */
            $lp = $this->activeRecordFactory->getObjectLoaded('Listing\Product', (int)$listingProductId);
            $componentMode = $lp->getComponentMode();

            $result = [];
            if ($componentMode == 'ebay') {
                /** @var \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Configurator $configurator */
                $configurator = $this->modelFactory->getObject('Ebay_Listing_Product_Action_Configurator');

                /** @var \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Type\Request $request */
                $request = $this->modelFactory->getObject(
                    'Ebay_Listing_Product_Action_Type_' . $requestType . '_Request'
                );
                $request->setListingProduct($lp);
                $request->setConfigurator($configurator);

                // @codingStandardsIgnoreLine
                $result = $request->getRequestData();
            }

            if ($componentMode == 'amazon') {
                /** @var \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Configurator $configurator */
                $configurator = $this->modelFactory->getObject('Amazon_Listing_Product_Action_Configurator');

                /** @var \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Type\Request $request */
                $request = $this->modelFactory->getObject(
                    'Amazon_Listing_Product_Action_Type_' . $requestType . '_Request'
                );
                $request->setParams([]);
                $request->setListingProduct($lp);
                $request->setConfigurator($configurator);

                if ($requestType == 'ListAction') {
                    $request->setCachedData([
                        'sku' => 'placeholder',
                        'general_id' => 'placeholder',
                        'list_type' => 'placeholder',
                    ]);
                }

                // @codingStandardsIgnoreLine
                $result = $request->getRequestData();
            }

            if ($componentMode == 'walmart') {
                /** @var \Ess\M2ePro\Model\Walmart\Listing\Product\Action\Configurator $configurator */
                $configurator = $this->modelFactory->getObject('Walmart_Listing_Product_Action_Configurator');

                /** @var \Ess\M2ePro\Model\Walmart\Listing\Product\Action\Type\ListAction\SkuResolver $skuResolver */
                $skuResolver = $this->modelFactory
                    ->getObject('Walmart_Listing_Product_Action_Type_ListAction_SkuResolver');
                $skuResolver->setListingProduct($lp);

                /** @var \Ess\M2ePro\Model\Walmart\Listing\Product\Action\Type\Request $request */
                $request = $this->modelFactory->getObject(
                    'Walmart_Listing_Product_Action_Type_' . $requestType . '_Request'
                );
                $request->setParams(['sku' => $skuResolver->resolve()]);
                $request->setListingProduct($lp);
                $request->setConfigurator($configurator);

                // @codingStandardsIgnoreLine
                $result = $request->getRequestData();
            }

            $resultBlockHtml = sprintf(
                '<pre class="card"><code>%s</code></pre>',
                htmlentities(
                    $this->jsonEncode($result),
                    ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401
                )
            );
        }

        $formKey = $this->formKey->getFormKey();
        $actionUrl = $this->getUrl('*/*/*', ['action' => 'getRequestData']);

        $typeOptions = [
            ['value' => 'ListAction', 'label' => 'List'],
            ['value' => 'Relist', 'label' => 'Relist'],
            ['value' => 'Revise', 'label' => 'Revise'],
        ];

        $requestTypeOptions = implode('', array_map(function (array $type) use ($requestType) {
            return sprintf(
                '<option value="%s" %s>%s</option>',
                $type['value'],
                $requestType === $type['value'] ? 'selected="selected"' : '',
                $type['label']
            );
        }, $typeOptions));

        $formHtml = <<<HTML
<form class="card gray" method="get" enctype="multipart/form-data" action="$actionUrl">
    <input name="form_key" value="$formKey" type="hidden" />
    <div class="row">
        <label for="listing_product_id" class="required">Listing Product ID:</label>
        <input id="listing_product_id" name="listing_product_id" class="form-control" type="text" value="$listingProductId" required>
    </div>
    <div class="row">
        <label for="request_type" class="required">Request Type:</label>
        <select class="form-control" id="request_type" name="request_type" required>
            <option hidden="hidden"></option>
            $requestTypeOptions
        </select>
    </div>
    <div class="row">
        <button type="submit" class="button">Show</button>
    </div>
</form>
HTML;

        return $this->getStyleHtml() . $formHtml . $resultBlockHtml;
    }

    /**
     * @title "Print Inspector Data"
     * @description "Print Inspector Data"
     */
    public function getInspectorDataAction()
    {
        $listingProductId = $this->getRequest()->getParam('listing_product_id');
        $resultHtml = '';
        if (!empty($listingProductId)) {
            /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\Instruction\Collection $instructionCollection */
            $instructionCollection = $this->activeRecordFactory->getObject(
                'Listing_Product_Instruction'
            )->getCollection();
            $instructionCollection->applySkipUntilFilter();
            $instructionCollection->addFieldToFilter('listing_product_id', $listingProductId);
            /** @var \Ess\M2ePro\Model\Listing\Product $lp */
            $lp = $this->activeRecordFactory
                ->getObject('Listing\Product')
                ->load($listingProductId);

            if ($lp->getComponentMode() == 'ebay') {
                $lp->setChildMode(Ebay::NICK);

                /** @var \Ess\M2ePro\Model\Listing\Product\Instruction\SynchronizationTemplate\Checker\Input $checkerInput */
                $checkerInput = $this->modelFactory
                    ->getObject('Listing_Product_Instruction_SynchronizationTemplate_Checker_Input');
                $checkerInput->setListingProduct($lp);

                $instructions = [];
                foreach ($instructionCollection->getItems() as $instruction) {
                    /**@var \Ess\M2ePro\Model\Listing\Product\Instruction $instruction */
                    $instruction->setListingProduct($lp);
                    $instructions[$instruction->getId()] = $instruction;
                }
                $checkerInput->setInstructions($instructions);

                $html = '<pre class="card">';

                //--
                /** @var \Ess\M2ePro\Model\Ebay\Listing\Product\Instruction\SynchronizationTemplate\Checker\NotListed $checker */
                $checker = $this->modelFactory->getObject(
                    'Ebay_Listing_Product_Instruction_SynchronizationTemplate_Checker_NotListed'
                );
                $checker->setInput($checkerInput);

                $html .= '<b>NotListed</b><br>';
                $html .= 'isAllowed: ' . json_encode($checker->isAllowed()) . '<br>';
                $html .= 'isMeetList: ' . json_encode($checker->isMeetListRequirements()) . '<br><br>';
                //--

                //--
                /** @var \Ess\M2ePro\Model\Ebay\Listing\Product\Instruction\SynchronizationTemplate\Checker\Inactive $checker */
                $checker = $this->modelFactory->getObject(
                    'Ebay_Listing_Product_Instruction_SynchronizationTemplate_Checker_Inactive'
                );
                $checker->setInput($checkerInput);

                $html .= '<b>Inactive</b><br>';
                $html .= 'isAllowed: ' . json_encode($checker->isAllowed()) . '<br>';
                $html .= 'isMeetRelist: ' . json_encode($checker->isMeetRelistRequirements()) . '<br><br>';
                //--

                //--
                /** @var \Ess\M2ePro\Model\Ebay\Listing\Product\Instruction\SynchronizationTemplate\Checker\Active $checker */
                $checker = $this->modelFactory->getObject(
                    'Ebay_Listing_Product_Instruction_SynchronizationTemplate_Checker_Active'
                );
                $checker->setInput($checkerInput);

                $html .= '<b>Active</b><br>';
                $html .= 'isAllowed: ' . json_encode($checker->isAllowed()) . '<br>';
                $html .= 'isMeetStop: ' . json_encode($checker->isMeetStopRequirements()) . '<br><br>';

                $html .= 'isMeetReviseQty: ' . json_encode($checker->isMeetReviseQtyRequirements()) . '<br>';
                $html .= 'isMeetRevisePrice: ' . json_encode($checker->isMeetRevisePriceRequirements()) . '<br>';
                $html .= 'isMeetReviseTitle: ' . json_encode($checker->isMeetReviseTitleRequirements()) . '<br>';
                $html .= 'isMeetReviseSubtitle: ' . json_encode($checker->isMeetReviseSubtitleRequirements()) . '<br>';
                $html .= 'isMeetReviseDescription: ' . json_encode(
                    $checker->isMeetReviseDescriptionRequirements()
                ) . '<br>';
                $html .= 'isMeetReviseCategories: ' . json_encode(
                    $checker->isMeetReviseCategoriesRequirements()
                ) . '<br>';
                $html .= 'isMeetReviseShipping: ' . json_encode($checker->isMeetReviseShippingRequirements()) . '<br>';
                $html .= 'isMeetReviseReturn: ' . json_encode($checker->isMeetReviseReturnRequirements()) . '<br>';
                $html .= 'isMeetReviseOther: ' . json_encode($checker->isMeetReviseOtherRequirements()) . '<br><br>';

                /** @var \Ess\M2ePro\Model\Ebay\Listing\Product $elp */
                $elp = $lp->getChildObject();
                $html .= 'isSetCategoryTemplate: ' . json_encode($elp->isSetCategoryTemplate()) . '<br>';
                $html .= 'isInAction: ' . json_encode($lp->isSetProcessingLock('in_action')) . '<br><br>';

                $magentoProduct = $lp->getMagentoProduct();
                $html .= 'isStatusEnabled: ' . json_encode($magentoProduct->isStatusEnabled()) . '<br>';
                $html .= 'isStockAvailability: ' . json_encode($magentoProduct->isStockAvailability()) . '<br>';

                //--

                $resultHtml = $html;
            }

            if ($lp->getComponentMode() == 'amazon') {
                $lp->setChildMode(Amazon::NICK);
                /** @var \Ess\M2ePro\Model\Listing\Product\Instruction\SynchronizationTemplate\Checker\Input $checkerInput */
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
                /** @var \Ess\M2ePro\Model\Amazon\Listing\Product\Instruction\SynchronizationTemplate\Checker\NotListed $checker */
                $checker = $this->modelFactory->getObject(
                    'Amazon_Listing_Product_Instruction_SynchronizationTemplate_Checker_NotListed'
                );
                $checker->setInput($checkerInput);

                $html .= '<b>NotListed</b><br>';
                $html .= 'isAllowed: ' . json_encode($checker->isAllowed()) . '<br>';
                $html .= 'isMeetList: ' . json_encode($checker->isMeetListRequirements()) . '<br><br>';
                //--

                //--
                /** @var \Ess\M2ePro\Model\Amazon\Listing\Product\Instruction\SynchronizationTemplate\Checker\Inactive $checker */
                $checker = $this->modelFactory->getObject(
                    'Amazon_Listing_Product_Instruction_SynchronizationTemplate_Checker_Inactive'
                );
                $checker->setInput($checkerInput);

                $html .= '<b>Inactive</b><br>';
                $html .= 'isAllowed: ' . json_encode($checker->isAllowed()) . '<br>';
                $html .= 'isMeetRelist: ' . json_encode($checker->isMeetRelistRequirements()) . '<br><br>';
                //--

                //--
                /** @var \Ess\M2ePro\Model\Amazon\Listing\Product\Instruction\SynchronizationTemplate\Checker\Active $checker */
                $checker = $this->modelFactory->getObject(
                    'Amazon_Listing_Product_Instruction_SynchronizationTemplate_Checker_Active'
                );
                $checker->setInput($checkerInput);

                $html .= '<b>Active</b><br>';
                $html .= 'isAllowed: ' . json_encode($checker->isAllowed()) . '<br>';
                $html .= 'isMeetStop: ' . json_encode($checker->isMeetStopRequirements()) . '<br><br>';

                $html .= 'isMeetReviseQty: ' . json_encode($checker->isMeetReviseQtyRequirements()) . '<br>';
                $html .= 'isMeetRevisePriceReg: ' . json_encode($checker->isMeetRevisePriceRegularRequirements()) . '<br>';
                $html .= 'isMeetRevisePriceBus: ' . json_encode($checker->isMeetRevisePriceBusinessRequirements()) . '<br>';
                $html .= 'isMeetReviseDetails: ' . json_encode($checker->isMeetReviseDetailsRequirements()) . '<br>';
                //--

                //--
                $magentoProduct = $lp->getMagentoProduct();
                $html .= 'isStatusEnabled: ' . json_encode($magentoProduct->isStatusEnabled()) . '<br>';
                $html .= 'isStockAvailability: ' . json_encode($magentoProduct->isStockAvailability()) . '<br>';

                //--

                $resultHtml = $html;
            }

            if ($lp->getComponentMode() == 'walmart') {
                $lp->setChildMode(Walmart::NICK);

                /** @var \Ess\M2ePro\Model\Listing\Product\Instruction\SynchronizationTemplate\Checker\Input $checkerInput */
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
                /** @var \Ess\M2ePro\Model\Walmart\Listing\Product\Instruction\SynchronizationTemplate\Checker\NotListed $checker */
                $checker = $this->modelFactory->getObject(
                    'Walmart_Listing_Product_Instruction_SynchronizationTemplate_Checker_NotListed'
                );
                $checker->setInput($checkerInput);

                $html .= '<b>NotListed</b><br>';
                $html .= 'isAllowed: ' . json_encode($checker->isAllowed()) . '<br>';
                $html .= 'isMeetList: ' . json_encode($checker->isMeetListRequirements()) . '<br><br>';
                //--

                //--
                /** @var \Ess\M2ePro\Model\Walmart\Listing\Product\Instruction\SynchronizationTemplate\Checker\Inactive $checker */
                $checker = $this->modelFactory->getObject(
                    'Walmart_Listing_Product_Instruction_SynchronizationTemplate_Checker_Inactive'
                );
                $checker->setInput($checkerInput);

                $html .= '<b>Inactive</b><br>';
                $html .= 'isAllowed: ' . json_encode($checker->isAllowed()) . '<br>';
                $html .= 'isMeetRelist: ' . json_encode($checker->isMeetRelistRequirements()) . '<br><br>';
                //--

                //--
                /** @var \Ess\M2ePro\Model\Walmart\Listing\Product\Instruction\SynchronizationTemplate\Checker\Active $checker */
                $checker = $this->modelFactory->getObject(
                    'Walmart_Listing_Product_Instruction_SynchronizationTemplate_Checker_Active'
                );
                $checker->setInput($checkerInput);

                $html .= '<b>Active</b><br>';
                $html .= 'isAllowed: ' . json_encode($checker->isAllowed()) . '<br>';
                $html .= 'isMeetStop: ' . json_encode($checker->isMeetStopRequirements()) . '<br><br>';

                $html .= 'isMeetReviseQty: ' . json_encode($checker->isMeetReviseQtyRequirements()) . '<br>';
                $html .= 'isMeetRevisePrice: ' . json_encode($checker->isMeetRevisePriceRequirements()) . '<br>';
                $html .= 'isMeetRevisePromotions: ' . json_encode($checker->isMeetRevisePromotionsRequirements()) . '<br>';
                $html .= 'isMeetReviseDetails: ' . json_encode($checker->isMeetReviseDetailsRequirements()) . '<br>';
                //--

                //--
                $magentoProduct = $lp->getMagentoProduct();
                $html .= 'isStatusEnabled: ' . json_encode($magentoProduct->isStatusEnabled()) . '<br>';
                $html .= 'isStockAvailability: ' . json_encode($magentoProduct->isStockAvailability()) . '<br>';

                //--

                $resultHtml = $html;
            }
        }

        $formKey = $this->formKey->getFormKey();
        $actionUrl = $this->getUrl('*/*/*', ['action' => 'getInspectorData']);

        $formHtml = <<<HTML
<form class="card gray" method="get" enctype="multipart/form-data" action="$actionUrl">
    <input name="form_key" value="{$formKey}" type="hidden" />
    <div class="row">
        <label for="listing_product_id" class="required">Listing Product ID: </label>
        <input id="listing_product_id" name="listing_product_id" type="text" value="$listingProductId" required>
    </div>

    <div class="row">
        <button type="submit" class="button">Show</button>
    </div>
</form>
HTML;

        return $this->getStyleHtml() . $formHtml . $resultHtml;
    }

    //########################################

    /**
     * @title "Build Order Quote"
     * @description "Print Order Quote Data"
     */
    public function getPrintOrderQuoteDataAction()
    {
        $orderId = $this->getRequest()->getParam('order_id');

        $resultHtml = '';
        if (!empty($orderId)) {
            try {
                /** @var \Ess\M2ePro\Model\Order $order */
                $order = $this->activeRecordFactory->getObjectLoaded('Order', $orderId);
            } catch (\Ess\M2ePro\Model\Exception\Logic $exception) {
                $order = false;
                $resultHtml = sprintf('<div class="card red">%s</div>', $exception->getMessage());
            }

            if ($order) {
                // Store must be initialized before products
                // ---------------------------------------
                $order->associateWithStore();
                $order->associateItemsWithProducts();
                // ---------------------------------------

                $proxy = $order->getProxy()->setStore($order->getStore());

                /** @var \Ess\M2ePro\Model\Magento\Quote\Builder $magentoQuoteBuilder */
                $magentoQuoteBuilder = $this->modelFactory
                    ->getObject('Magento_Quote_Builder', ['proxyOrder' => $proxy]);
                /** @var  \Ess\M2ePro\Model\Magento\Quote\Manager $magentoQuoteManager */
                $magentoQuoteManager = $this->modelFactory
                    ->getObject('Magento_Quote_Manager');

                $quote = $magentoQuoteBuilder->build();

                $shippingAddressData = $quote->getShippingAddress()->getData();
                unset(
                    $shippingAddressData['cached_items_all'],
                    $shippingAddressData['cached_items_nominal'],
                    $shippingAddressData['cached_items_nonnominal']
                );
                $billingAddressData = $quote->getBillingAddress()->getData();
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

                $resultHtml = '<pre class="card">';
                $resultHtml .= sprintf('<h3>Grand Total: %s</h3>', $quote->getGrandTotal());
                $resultHtml .= sprintf(
                    '<h3>Shipping Amount: %s</h3>',
                    $quote->getShippingAddress()->getShippingAmount()
                );
                $resultHtml .= sprintf('<h3>Quote Data:</h3><code>%s</code>', $this->jsonEncode($quoteData));
                $resultHtml .= sprintf(
                    '<h3>Shipping Address Data:</h3><code>%s</code>',
                    $this->jsonEncode($shippingAddressData)
                );
                $resultHtml .= sprintf(
                    '<h3>Billing Address Data:</h3><code>%s</code>',
                    $this->jsonEncode($billingAddressData)
                );
                $resultHtml .= sprintf('<h3>Items:</h3><code>%s</code>', $this->jsonEncode($items));
                $resultHtml .= '</pre>';
            }
        }

        $formKey = $this->formKey->getFormKey();
        $actionUrl = $this->getUrl('*/*/*', ['action' => 'getPrintOrderQuoteData']);

        $formHtml = <<<HTML
<form class="card gray" method="get" enctype="multipart/form-data" action="$actionUrl">
    <input name="form_key" value="$formKey" type="hidden" />
    <div class="row">
        <label for="order_id" class="required">Order ID:</label>
        <input id="order_id" name="order_id" type="text" value="$orderId" required>
    </div>
    <div class="row">
        <button type="submit" class="button">Build</button>
    </div>
</form>
HTML;

        return $this->getStyleHtml() . $formHtml . $resultHtml;
    }

    //########################################
}
