<?php

namespace Ess\M2ePro\Controller\Adminhtml\ControlPanel\Module;

use Ess\M2ePro\Controller\Adminhtml\Context;
use Ess\M2ePro\Controller\Adminhtml\ControlPanel\Command;
use Ess\M2ePro\Helper\Component\Amazon;
use Ess\M2ePro\Helper\Component\Ebay;
use Magento\Backend\App\Action;

class Integration extends Command
{
    private $synchConfig;
    private $formKey;
    private $csvParser;
    private $phpEnvironmentRequest;
    private $productFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\Config\Manager\Synchronization $synchConfig,
        \Magento\Framework\Data\Form\FormKey $formKey,
        \Magento\Framework\File\Csv $csvParser,
        \Magento\Framework\HTTP\PhpEnvironment\Request $phpEnvironmentRequest,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        Context $context
    ) {
        $this->synchConfig = $synchConfig;
        $this->formKey = $formKey;
        $this->csvParser = $csvParser;
        $this->phpEnvironmentRequest = $phpEnvironmentRequest;
        $this->productFactory = $productFactory;
        parent::__construct($context);
    }

    //########################################

    /**
     * @title "Revise Total"
     * @description "Full Force Revise"
     */
    public function reviseTotalAction()
    {
        $html = '';
        foreach ($this->getHelper('Component')->getEnabledComponents() as $component) {

            $reviseAllStartDate = $this->synchConfig->getGroupValue(
                "/{$component}/templates/synchronization/revise/total/", 'start_date'
            );

            $reviseAllEndDate = $this->synchConfig->getGroupValue(
                "/{$component}/templates/synchronization/revise/total/", 'end_date'
            );

            $reviseAllInProcessingState = !is_null(
                $this->synchConfig->getGroupValue(
                    "/{$component}/templates/synchronization/revise/total/", 'last_listing_product_id'
                )
            );

            $runNowUrl = $this->getUrl('*/*/*', ['action' => 'processReviseTotal', 'component' => $component]);
            $resetUrl = $this->getUrl('*/*/*', ['action' => 'resetReviseTotal', 'component' => $component]);

            $html .= <<<HTML
<div>
    <span style="display:inline-block; width: 100px;">{$component}</span>
    <span style="display:inline-block; width: 150px;">
        <button onclick="window.location='{$runNowUrl}'">turn on</button>
        <button onclick="window.location='{$resetUrl}'">stop</button>
    </span>
    <span id="{$component}_start_date" style="color: indianred; display: none;">
        Started at - {$reviseAllStartDate}
    </span>
    <span id="{$component}_end_date" style="color: green; display: none;">
        Finished at - {$reviseAllEndDate}
    </span>
</div>

HTML;
            $html.= "<script type=\"text/javascript\">";
            if ($reviseAllInProcessingState) {
                $html .= "document.getElementById('{$component}_start_date').style.display = 'inline-block';";
            } else {

                if ($reviseAllEndDate) {
                    $html .= "document.getElementById('{$component}_end_date').style.display = 'inline-block';";
                }
            }
            $html.= "</script>";
        }

        return $html;
    }

    /**
     * @title "Process Revise Total for Component"
     * @hidden
     */
    public function processReviseTotalAction()
    {
        $component = $this->getRequest()->getParam('component', false);

        if (!$component) {
            $this->getMessageManager()->addError('Component is not presented.');
            $this->_redirect($this->getHelper('View\ControlPanel')->getPageModuleTabUrl());
        }

        $this->synchConfig->setGroupValue(
            "/{$component}/templates/synchronization/revise/total/",
            'start_date', $this->getHelper('Data')->getCurrentGmtDate()
        );

        $this->synchConfig->setGroupValue(
            "/{$component}/templates/synchronization/revise/total/", 'end_date', null
        );

        $this->synchConfig->setGroupValue(
            "/{$component}/templates/synchronization/revise/total/", 'last_listing_product_id', 0
        );

        $this->_redirect('*/*/*', ['action' => 'reviseTotal']);
    }

    /**
     * @title "Reset Revise Total for Component"
     * @hidden
     */
    public function resetReviseTotalAction()
    {
        $component = $this->getRequest()->getParam('component', false);

        if (!$component) {
            $this->getMessageManager()->addError('Component is not presented.');
            return $this->_redirect($this->getHelper('View\ControlPanel')->getPageModuleTabUrl());
        }

        $this->synchConfig->setGroupValue(
            "/{$component}/templates/synchronization/revise/total/", 'last_listing_product_id', null
        );

        return $this->_redirect('*/*/*', ['action' => 'reviseTotal']);
    }

    /**
     * @title "Print Request Data"
     * @description "Print [List/Relist/Revise] Request Data"
     * @new_line
     */
    public function getRequestDataAction()
    {
        if ($this->getRequest()->getParam('print')) {

            $listingProductId = (int)$this->getRequest()->getParam('listing_product_id');
            $lp               = $this->activeRecordFactory->getObjectLoaded('Listing\Product', $listingProductId);
            $componentMode    = $lp->getComponentMode();
            $requestType      = $this->getRequest()->getParam('request_type');

            if ($componentMode == 'ebay') {

                $elp = $lp->getChildObject();

                $configurator = $this->modelFactory->getObject('Ebay\Listing\Product\Action\Configurator');

                $request = $this->modelFactory->getObject('Ebay\Listing\Product\Action\Type\\'.$requestType.'\Request');
                $request->setListingProduct($lp);
                $request->setConfigurator($configurator);

                if ($requestType == 'Revise') {

                    $outOfStockControlCurrentState  = $elp->getOutOfStockControl();
                    $outOfStockControlTemplateState = $elp->getEbaySellingFormatTemplate()->getOutOfStockControl();

                    if (!$outOfStockControlCurrentState && $outOfStockControlTemplateState) {
                        $outOfStockControlCurrentState = true;
                    }

                    $outOfStockControlResult = $outOfStockControlCurrentState ||
                        $elp->getEbayAccount()->getOutOfStockControl();

                    $request->setParams(array(
                        'out_of_stock_control_current_state' => $outOfStockControlCurrentState,
                        'out_of_stock_control_result'        => $outOfStockControlResult,
                    ));
                }

                return '<pre>' . print_r($request->getRequestData(), true);
            }

            if ($componentMode == 'amazon') {

                $configurator = $this->modelFactory->getObject('Amazon\Listing\Product\Action\Configurator');

                $request = $this->modelFactory->getObject(
                    'Amazon\Listing\Product\Action\Type\\'.$requestType.'\Request'
                );
                $request->setParams(array());
                $request->setListingProduct($lp);
                $request->setConfigurator($configurator);

                if ($requestType == 'ListAction') {
                    $request->setValidatorsData(array(
                        'sku'        => 'placeholder',
                        'general_id' => 'placeholder',
                        'list_type'  => 'placeholder'
                    ));
                }

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

    //########################################

    /**
     * @title "Print Inspector Data"
     * @description "Print Inspector Data"
     * @new_line
     */
    public function getInspectorDataAction()
    {
        if ($this->getRequest()->getParam('print')) {

            $listingProductId = $this->getRequest()->getParam('listing_product_id');

            if ($this->getRequest()->getParam('component_mode') == 'ebay') {

                $lp = $this->parentFactory->getObjectLoaded(Ebay::NICK, 'Listing\Product', $listingProductId);
                $elp = $lp->getChildObject();

                $insp = $this->modelFactory->getObject('Ebay\Synchronization\Templates\Synchronization\Inspector');

                $html = '';

                $html .= '<pre>isMeetListRequirements: ' .$insp->isMeetListRequirements($lp). '<br>';
                $html .= '<pre>isMeetRelistRequirements: ' .$insp->isMeetRelistRequirements($lp). '<br>';
                $html .= '<pre>isMeetStopRequirements: ' .$insp->isMeetStopRequirements($lp). '<br>';
                $html .= '<pre>isMeetReviseGeneralRequirements: ' .$insp->isMeetReviseGeneralRequirements($lp). '<br>';
                $html .= '<pre>isMeetRevisePriceRequirements: ' .$insp->isMeetRevisePriceRequirements($lp). '<br>';
                $html .= '<pre>isMeetReviseQtyRequirements: ' .$insp->isMeetReviseQtyRequirements($lp). '<br>';

                $html .= '<br>';
                $html .= '<pre>isSetCategoryTemplate: ' .$elp->isSetCategoryTemplate(). '<br>';
                $html .= '<pre>isInAction: ' .$lp->isSetProcessingLock('in_action'). '<br>';

                $html .= '<pre>isStatusEnabled: ' .($lp->getMagentoProduct()->isStatusEnabled()). '<br>';
                $html .= '<pre>isStockAvailability: ' .($lp->getMagentoProduct()->isStockAvailability()). '<br>';

                $html .= '<pre>onlineQty: ' .($elp->getOnlineQty() - $elp->getOnlineQtySold()). '<br>';

                $totalQty = 0;

                if (!$elp->isVariationsReady()) {
                    $totalQty = $elp->getQty();
                } else {
                    foreach ($lp->getVariations(true) as $variation) {
                        $ebayVariation = $variation->getChildObject();
                        $totalQty += $ebayVariation->getQty();
                    }
                }

                $html .= '<pre>productQty: ' .$totalQty. '<br>';

                $html .= '<br>';
                $html .= '<pre>onlineCurrentPrice: '.($elp->getOnlineCurrentPrice()).'<br>';
                $html .= '<pre>currentPrice: '.($elp->getFixedPrice()).'<br>';

                $html .= '<br>';
                $html .= '<pre>onlineStartPrice: '.($elp->getOnlineStartPrice()).'<br>';
                $html .= '<pre>startPrice: '.($elp->getStartPrice()).'<br>';

                $html .= '<br>';
                $html .= '<pre>onlineReservePrice: '.($elp->getOnlineReservePrice()).'<br>';
                $html .= '<pre>reservePrice: '.($elp->getReservePrice()).'<br>';

                $html .= '<br>';
                $html .= '<pre>onlineBuyItNowPrice: '.($elp->getOnlineBuyItNowPrice()).'<br>';
                $html .= '<pre>buyItNowPrice: '.($elp->getBuyItNowPrice()).'<br>';

                return $html;
            }

            if ($this->getRequest()->getParam('component_mode') == 'amazon') {

                $lp = $this->parentFactory->getObjectLoaded(Amazon::NICK, 'Listing\Product', $listingProductId);

                $insp = $this->modelFactory->getObject('Amazon\Synchronization\Templates\Synchronization\Inspector');

                $html = '';

                $html .= '<pre>isMeetList: ' .$insp->isMeetListRequirements($lp). '<br>';
                $html .= '<pre>isMeetRelist: ' .$insp->isMeetRelistRequirements($lp). '<br>';
                $html .= '<pre>isMeetStop: ' .$insp->isMeetStopRequirements($lp). '<br>';
                $html .= '<pre>isMeetReviseGeneral: ' .$insp->isMeetReviseGeneralRequirements($lp). '<br>';
                $html .= '<pre>isMeetReviseRegularPrice: '.$insp->isMeetReviseRegularPriceRequirements($lp).'<br>';
                $html .= '<pre>isMeetReviseBusinessPrice: '.$insp->isMeetReviseBusinessPriceRequirements($lp).'<br>';
                $html .= '<pre>isMeetReviseQty: ' .$insp->isMeetReviseQtyRequirements($lp). '<br>';

                $html .= '<pre>isStatusEnabled: ' .($lp->getMagentoProduct()->isStatusEnabled()). '<br>';
                $html .= '<pre>isStockAvailability: ' .($lp->getMagentoProduct()->isStockAvailability()). '<br>';

                return $html;
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

    <div style="margin: 5px 0; width: 400px;">
        <label style="width: 170px; display: inline-block;">Component: </label>
        <select name="component_mode" style="width: 200px;" required>
            <option style="display: none;"></option>
            <option value="ebay">eBay</option>
            <option value="amazon">Amazon</option>
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
     * @title "Build Order Quote"
     * @description "Print Order Quote Data"
     * @new_line
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

            $proxy = $order->getProxy()->setStore($order->getStore());

            $magentoQuote = $this->modelFactory->getObject('Magento\Quote', ['proxyOrder' => $proxy]);
            $magentoQuote->buildQuote();
            $magentoQuote->getQuote()->setIsActive(false)->save();

            $shippingAddressData = $magentoQuote->getQuote()->getShippingAddress()->getData();
            unset(
                $shippingAddressData['cached_items_all'],
                $shippingAddressData['cached_items_nominal'],
                $shippingAddressData['cached_items_nonnominal']
            );
            $billingAddressData  = $magentoQuote->getQuote()->getBillingAddress()->getData();
            unset(
                $billingAddressData['cached_items_all'],
                $billingAddressData['cached_items_nominal'],
                $billingAddressData['cached_items_nonnominal']
            );

            $quote = $magentoQuote->getQuote();

            $html = '';

            $html .= '<pre><b>Grand Total:</b> ' .$quote->getGrandTotal(). '<br>';
            $html .= '<pre><b>Shipping Amount:</b> ' .$quote->getShippingAddress()->getShippingAmount(). '<br>';

            $html .= '<pre><b>Quote Data:</b> ' .print_r($quote->getData(), true). '<br>';
            $html .= '<pre><b>Shipping Address Data:</b> ' .print_r($shippingAddressData, true). '<br>';
            $html .= '<pre><b>Billing Address Data:</b> ' .print_r($billingAddressData, true). '<br>';

            return $html;
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

    /**
     * @title "Reset eBay 3rd Party"
     * @description "Clear all eBay 3rd party items for all Accounts"
     */
    public function resetOtherListingsAction()
    {
        $listingOther = $this->parentFactory->getObject(Ebay::NICK, 'Listing\Other');
        $ebayListingOther = $this->activeRecordFactory->getObject('Ebay\Listing\Other');

        $stmt = $listingOther->getResourceCollection()->getSelect()->query();

        foreach ($stmt as $row) {
            $listingOther->setData($row);
            $ebayListingOther->setData($row);

            $listingOther->setChildObject($ebayListingOther);
            $ebayListingOther->setParentObject($listingOther);

            $listingOther->delete();
        }

        foreach ($this->parentFactory->getObject(Ebay::NICK, 'Account')->getCollection() as $account) {
            $account->getChildObject()->setData('other_listings_last_synchronization', NULL)->save();
        }

        $this->getMessageManager()->addSuccess('Successfully removed.');
        $this->_redirect($this->getHelper('View\ControlPanel')->getPageModuleTabUrl());
    }

    /**
     * @title "Stop eBay 3rd Party"
     * @description "[in order to resolve the problem with duplicates]"
     * @new_line
     */
    public function stopEbay3rdPartyAction()
    {
        $collection = $this->parentFactory->getObject(Ebay::NICK, 'Listing\Other')->getCollection();
        $collection->addFieldToFilter('status', array('in' => array(
            \Ess\M2ePro\Model\Listing\Product::STATUS_LISTED,
            \Ess\M2ePro\Model\Listing\Product::STATUS_HIDDEN
        )));

        $total       = 0;
        $groupedData = array();

        foreach ($collection->getItems() as $item) {
            /** @var \Ess\M2ePro\Model\Ebay\Listing\Other $item */

            $key = $item->getAccount()->getId() .'##'. $item->getMarketplace()->getId();
            $groupedData[$key][$item->getId()] = $item->getItemId();
            $total++;
        }

        foreach ($groupedData as $groupKey => $items) {

            list($accountId, $marketplaceId) = explode('##', $groupKey);

            foreach (array_chunk($items, 10, true) as $itemsPart) {

                /** @var $dispatcherObject \Ess\M2ePro\Model\Ebay\Connector\Dispatcher */
                $dispatcherObject = $this->modelFactory->getObject('Ebay\Connector\Dispatcher');
                $connectorObj = $dispatcherObject->getVirtualConnector('item','update','ends',
                    array('items' => $itemsPart), null, $marketplaceId, $accountId
                );

                $dispatcherObject->process($connectorObj);
                $response = $connectorObj->getResponseData();

                foreach ($response['result'] as $itemId => $iResp) {

                    $item = $this->parentFactory->getObjectLoaded(
                        Ebay::NICK, 'Listing\Other', $itemId, null, false
                    );
                    if (!is_null($item) &&
                        ((isset($iResp['already_stop']) && $iResp['already_stop']) ||
                            isset($iResp['ebay_end_date_raw'])))
                    {
                        $item->setData('status', \Ess\M2ePro\Model\Listing\Product::STATUS_STOPPED)->save();
                    }
                }
            }
        }

        return "Processed {$total} products.";
    }

    /**
     * @title "Reset eBay Images Hashes"
     * @description "Clear eBay images hashes for listing products"
     * @prompt "Please enter Listing Product ID or `all` code for reset all products."
     * @prompt_var "listing_product_id"
     */
    public function resetEbayImagesHashesAction()
    {
        $listingProductId = $this->getRequest()->getParam('listing_product_id');

        $listingProducts = array();
        if (strtolower($listingProductId) == 'all') {

            $listingProducts = $this->parentFactory->getObject(Ebay::NICK, 'Listing\Product')->getCollection();
        } else {

            $listingProduct = $this->parentFactory->getObjectLoaded(Ebay::NICK, 'Listing\Product', $listingProductId);
            $listingProduct && $listingProducts[] = $listingProduct;
        }

        if (empty($listingProducts)) {
            $this->getMessageManager()->addError('Failed to load Listing Product.');
            return $this->_redirect($this->getHelper('View\ControlPanel')->getPageModuleTabUrl());
        }

        $affected = 0;
        foreach ($listingProducts as $listingProduct) {

            $additionalData = $listingProduct->getAdditionalData();

            if (!isset($additionalData['ebay_product_images_hash']) &&
                !isset($additionalData['ebay_product_variation_images_hash'])) {
                continue;
            }

            unset($additionalData['ebay_product_images_hash'],
                $additionalData['ebay_product_variation_images_hash']);

            $affected++;
            $listingProduct->setData('additional_data', $this->getHelper('Data')->jsonEncode($additionalData))
                ->save();
        }

        $this->getMessageManager()->addSuccess("Successfully removed for {$affected} affected Products.");
        return $this->_redirect($this->getHelper('View\ControlPanel')->getPageModuleTabUrl());
    }

    /**
     * @title "Set eBay EPS Images Mode"
     * @description "Set EPS Images Mode = true for listing products"
     * @prompt "Please enter Listing Product ID or `all` code for all products."
     * @prompt_var "listing_product_id"
     */
    public function setEpsImagesModeAction()
    {
        $listingProductId = $this->getRequest()->getParam('listing_product_id');

        $listingProducts = array();
        if (strtolower($listingProductId) == 'all') {

            $listingProducts = $this->parentFactory->getObject(Ebay::NICK, 'Listing\Product')->getCollection();
        } else {

            $listingProduct = $this->parentFactory->getObjectLoaded(Ebay::NICK, 'Listing\Product', $listingProductId);
            $listingProduct && $listingProducts[] = $listingProduct;
        }

        if (empty($listingProducts)) {
            $this->getMessageManager()->addError('Failed to load Listing Product.');
            return $this->_redirect($this->getHelper('View\ControlPanel')->getPageModuleTabUrl());
        }

        $affected = 0;
        foreach ($listingProducts as $listingProduct) {

            $additionalData = $listingProduct->getAdditionalData();

            if (!isset($additionalData['is_eps_ebay_images_mode']) ||
                $additionalData['is_eps_ebay_images_mode'] == true) {
                continue;
            }

            $additionalData['is_eps_ebay_images_mode'] = true;
            $affected++;

            $listingProduct->setData('additional_data', $this->getHelper('Data')->jsonEncode($additionalData))
                ->save();
        }

        $this->getMessageManager()->addSuccess("Successfully set for {$affected} affected Products.");
        return $this->_redirect($this->getHelper('View\ControlPanel')->getPageModuleTabUrl());
    }

    //########################################

    /**
     * @title "Show eBay Nonexistent Templates"
     * @description "Show Nonexistent Templates [eBay]"
     * @new_line
     */
    public function showNonexistentTemplatesAction()
    {
        if ($this->getRequest()->getParam('fix')) {

            $fixAction    = $this->getRequest()->getParam('fix_action');

            $template     = $this->getRequest()->getParam('template_nick');
            $currentMode  = $this->getRequest()->getParam('current_mode');
            $currentValue = $this->getRequest()->getParam('value');

            if ($fixAction == 'set_null') {

                $field = $currentMode == 'template' ? "template_{$template}_id"
                                                    : "template_{$template}_custom_id";

                $collection = $this->parentFactory->getObject(Ebay::NICK, 'Listing\Product')->getCollection();
                $collection->addFieldToFilter($field, $currentValue);

                foreach ($collection->getItems() as $listingProduct) {
                    $listingProduct->getChildObject()->setData($field, null)->save();
                }
            }

            if ($fixAction == 'set_parent') {

                $field = $currentMode == 'template' ? "template_{$template}_id"
                                                    : "template_{$template}_custom_id";

                $collection = $this->parentFactory->getObject(Ebay::NICK, 'Listing\Product')->getCollection();
                $collection->addFieldToFilter($field, $currentValue);

                $data = array(
                    "template_{$template}_mode" => \Ess\M2ePro\Model\Ebay\Template\Manager::MODE_PARENT,
                    $field                      => null
                );

                foreach ($collection->getItems() as $listingProduct) {
                    $listingProduct->getChildObject()->addData($data)->save();
                }
            }

            if ($fixAction == 'set_template' && $this->getRequest()->getParam('template_id')) {

                $field = $currentMode == 'template' ? "template_{$template}_id"
                                                    : "template_{$template}_custom_id";

                $collection = $this->parentFactory->getObject(Ebay::NICK, 'Listing\Product')->getCollection();
                $collection->addFieldToFilter($field, $currentValue);

                $data = array(
                    "template_{$template}_mode" => \Ess\M2ePro\Model\Ebay\Template\Manager::MODE_TEMPLATE,
                    $field                      => null,
                );
                $data["template_{$template}_id"] = (int)$this->getRequest()->getParam('template_id');

                foreach ($collection->getItems() as $listing) {
                    $listing->getChildObject()->addData($data)->save();
                }
            }

            $this->_redirect($this->getUrl('*/*/*', ['action' => 'showNonexistentTemplates']));
        }

        $nonexistentTemplates = array();

        $simpleTemplates = array('category', 'other_category');
        foreach ($simpleTemplates as $templateName) {

            $tempResult = $this->getNonexistentTemplatesBySimpleLogic($templateName);
            !empty($tempResult) && $nonexistentTemplates[$templateName] = $tempResult;
        }

        $difficultTemplates = array(
            \Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_SELLING_FORMAT,
            \Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_SYNCHRONIZATION,
            \Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_DESCRIPTION,
            \Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_SHIPPING,
            \Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_PAYMENT,
            \Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_RETURN_POLICY,
        );
        foreach ($difficultTemplates as $templateName) {

            $tempResult = $this->getNonexistentTemplatesByDifficultLogic($templateName);
            !empty($tempResult) && $nonexistentTemplates[$templateName] = $tempResult;
        }

        if (count($nonexistentTemplates) <= 0) {
            return $this->getEmptyResultsHtml('There are not any nonexistent templates.');
        }

        $tableContent = <<<HTML
<tr>
    <th>Listing ID</th>
    <th>Listing Product ID</th>
    <th>Policy ID</th>
    <th>My Mode</th>
    <th>Parent Mode</th>
    <th>Actions</th>
</tr>
HTML;

        $alreadyRendered = array();
        foreach ($nonexistentTemplates as $templateName => $items) {

            $tableContent .= <<<HTML
<tr>
    <td colspan="6" align="center"><b>{$templateName}</b></td>
</tr>
HTML;

            foreach ($items as $index => $itemInfo) {

                $myModeWord = '';
                $parentModeWord = '';
                $actionsHtml = '';

                if (!isset($itemInfo['my_mode']) && !isset($itemInfo['parent_mode'])) {

                    $url = $this->getUrl('*/*/*', array(
                        'action'        => 'showNonexistentTemplates',
                        'fix'           => '1',
                        'template_nick' => $templateName,
                        'current_mode'  => 'template',
                        'fix_action'    => 'set_null',
                        'value'         => $itemInfo['my_needed_id'],
                    ));

                    $actionsHtml .= <<<HTML
<a href="{$url}">set null</a><br>
HTML;
                }

                if (isset($itemInfo['my_mode']) && $itemInfo['my_mode'] == 0) {
                    $myModeWord = 'parent';
                }

                if (isset($itemInfo['my_mode']) && $itemInfo['my_mode'] == 1) {

                    $myModeWord = 'custom';
                    $url = $this->getUrl('*/*/*', array(
                        'action'        => 'showNonexistentTemplates',
                        'fix'           => '1',
                        'template_nick' => $templateName,
                        'current_mode'  => $myModeWord,
                        'fix_action'    => 'set_parent',
                        'value'         => $itemInfo['my_needed_id'],
                    ));

                    $actionsHtml .= <<<HTML
<a href="{$url}">set parent</a><br>
HTML;
                }

                if (isset($itemInfo['my_mode']) && $itemInfo['my_mode'] == 2) {

                    $myModeWord = 'template';
                    $url = $this->getUrl('*/*/*', array(
                        'action'        => 'showNonexistentTemplates',
                        'fix'           => '1',
                        'template_nick' => $templateName,
                        'current_mode'  => $myModeWord,
                        'fix_action'    => 'set_parent',
                        'value'         => $itemInfo['my_needed_id'],
                    ));

                    $actionsHtml .= <<<HTML
<a href="{$url}">set parent</a><br>
HTML;
                }

                if (isset($itemInfo['parent_mode']) && $itemInfo['parent_mode'] == 1) {

                    $parentModeWord = 'custom';
                    $url = $this->getUrl('*/*/*', array(
                        'action'        => 'showNonexistentTemplates',
                        'fix'           => '1',
                        'fix_action'    => 'set_template',
                        'template_nick' => $templateName,
                        'current_mode'  => $parentModeWord,
                        'value'         => $itemInfo['my_needed_id'],
                    ));
                    $onClick = <<<JS
var result = prompt('Enter Template ID');
if (result) {
    window.location.href = '{$url}' + '?template_id=' + result;
}
return false;
JS;
                    $actionsHtml .= <<<HTML
<a href="javascript:void();" onclick="{$onClick}">set template</a><br>
HTML;
                }

                if (isset($itemInfo['parent_mode']) && $itemInfo['parent_mode'] == 2) {

                    $parentModeWord = 'template';
                    $url = $this->getUrl('*/*/*', array(
                        'action'        => 'showNonexistentTemplates',
                        'fix'           => '1',
                        'fix_action'    => 'set_template',
                        'template_nick' => $templateName,
                        'current_mode'  => $parentModeWord,
                        'value'         => $itemInfo['my_needed_id'],
                    ));
                    $onClick = <<<JS
var result = prompt('Enter Template ID');
if (result) {
    window.location.href = '{$url}' + '?template_id=' + result;
}
return false;
JS;
                    $actionsHtml .= <<<HTML
<a href="javascript:void();" onclick="{$onClick}">set template</a><br>
HTML;
                }

                $key = $templateName .'##'. $myModeWord .'##'. $itemInfo['listing_id'];
                if ($myModeWord == 'parent' && in_array($key, $alreadyRendered)) {
                    continue;
                }

                $alreadyRendered[] = $key;
                $tableContent .= <<<HTML
<tr>
    <td>{$itemInfo['listing_id']}</td>
    <td>{$itemInfo['my_id']}</td>
    <td>{$itemInfo['my_needed_id']}</td>
    <td>{$myModeWord}</td>
    <td>{$parentModeWord}</td>
    <td>
        {$actionsHtml}
    </td>
</tr>
HTML;
            }
        }

        $html = $this->getStyleHtml() . <<<HTML
<html>
    <body>
        <h2 style="margin: 20px 0 0 10px">Nonexistent templates
            <span style="color: #808080; font-size: 15px;">(#count# entries)</span>
        </h2>
        <br/>
        <table class="grid" cellpadding="0" cellspacing="0">
            {$tableContent}
        </table>
    </body>
</html>
HTML;

        return str_replace('#count#', count($alreadyRendered), $html);
    }

    private function getNonexistentTemplatesByDifficultLogic($templateCode)
    {
        $subSelect = $this->resourceConnection->getConnection()->select()
            ->from(
                array('melp' => $this->resourceConnection->getTableName('m2epro_ebay_listing_product')),
                array(
                    'my_id'          => 'listing_product_id',
                    'my_mode'        => "template_{$templateCode}_mode",
                    'my_template_id' => "template_{$templateCode}_id",
                    'my_custom_id'   => "template_{$templateCode}_custom_id",

                    'my_needed_id'   => new \Zend_Db_Expr(
                        "CASE
                        WHEN melp.template_{$templateCode}_mode = 2 THEN melp.template_{$templateCode}_id
                        WHEN melp.template_{$templateCode}_mode = 1 THEN melp.template_{$templateCode}_custom_id
                        WHEN melp.template_{$templateCode}_mode = 0 THEN IF(mel.template_{$templateCode}_mode = 1,
                                                                            mel.template_{$templateCode}_custom_id,
                                                                            mel.template_{$templateCode}_id)
                    END"
                    ))
            )
            ->joinLeft(
                array('mlp' => $this->resourceConnection->getTableName('m2epro_listing_product')),
                'melp.listing_product_id = mlp.id',
                array('listing_id' => 'listing_id')
            )
            ->joinLeft(
                array('mel' => $this->resourceConnection->getTableName('m2epro_ebay_listing')),
                'mlp.listing_id = mel.listing_id',
                array(
                    'parent_mode'        => "template_{$templateCode}_mode",
                    'parent_template_id' => "template_{$templateCode}_id",
                    'parent_custom_id'   => "template_{$templateCode}_custom_id"
                )
            );

        $templateIdName = 'id';
        $horizontalTemplates = array(
            \Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_SELLING_FORMAT,
            \Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_SYNCHRONIZATION,
            \Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_DESCRIPTION,
        );
        in_array($templateCode, $horizontalTemplates) && $templateIdName = "template_{$templateCode}_id";

        $result = $this->resourceConnection->getConnection()->select()
            ->from(
                array('subselect' => new \Zend_Db_Expr('('.$subSelect->__toString().')')),
                array(
                    'subselect.my_id',
                    'subselect.listing_id',
                    'subselect.my_mode',
                    'subselect.parent_mode',
                    'subselect.my_needed_id',
                )
            )
            ->joinLeft(
                array('template' => $this->resourceConnection->getTableName("m2epro_ebay_template_{$templateCode}")),
                "subselect.my_needed_id = template.{$templateIdName}",
                array()
            )
            ->where("template.{$templateIdName} IS NULL")
            ->query()->fetchAll();

        return $result;
    }

    private function getNonexistentTemplatesBySimpleLogic($templateCode)
    {
        $select = $this->resourceConnection->getConnection()->select()
            ->from(
                array('melp' => $this->resourceConnection->getTableName('m2epro_ebay_listing_product')),
                array(
                    'my_id'        => 'listing_product_id',
                    'my_needed_id' => "template_{$templateCode}_id",
                )
            )
            ->joinLeft(
                array('mlp' => $this->resourceConnection->getTableName('m2epro_listing_product')),
                'melp.listing_product_id = mlp.id',
                array('listing_id' => 'listing_id')
            )
            ->joinLeft(
                array('template' => $this->resourceConnection->getTableName("m2epro_ebay_template_{$templateCode}")),
                "melp.template_{$templateCode}_id = template.id",
                array()
            )
            ->where("melp.template_{$templateCode}_id IS NOT NULL")
            ->where("template.id IS NULL");

        return $select->query()->fetchAll();
    }

    //########################################

    /**
     * @title "Show eBay Duplicates [parse logs]"
     * @description "Show eBay Duplicates According with Logs"
     */
    public function showEbayDuplicatesByLogsAction()
    {
        $queryObj = $this->resourceConnection->getConnection()
            ->select()
            ->from(array('mll' => $this->resourceConnection->getTableName('m2epro_listing_log')))
            ->joinLeft(
                array('ml' => $this->resourceConnection->getTableName('m2epro_listing')),
                'mll.listing_id = ml.id',
                array('marketplace_id')
            )
            ->joinLeft(
                array('mm' => $this->resourceConnection->getTableName('m2epro_marketplace')),
                'ml.marketplace_id = mm.id',
                array('marketplace_title' => 'title')
            )
            ->where("mll.description LIKE '%a duplicate of your item%' OR " . // ENG
                "mll.description LIKE '%ette annonce est identique%' OR " . // FR
                "mll.description LIKE '%ngebot ist identisch mit dem%' OR " .  // DE
                "mll.description LIKE '%un duplicato del tuo oggetto%' OR " . // IT
                "mll.description LIKE '%es un duplicado de tu art%'" // ESP
            )
            ->where("mll.component_mode = ?", 'ebay')
            ->order('mll.id DESC')
            ->group(array('mll.product_id', 'mll.listing_id'))
            ->query();

        $duplicatesInfo = array();
        while ($row = $queryObj->fetch()) {

            preg_match('/.*\((\d*)\)/', $row['description'], $matches);
            $ebayItemId = !empty($matches[1]) ? $matches[1] : '';

            $duplicatesInfo[] = array(
                'date'               => $row['create_date'],
                'listing_id'         => $row['listing_id'],
                'listing_title'      => $row['listing_title'],
                'product_id'         => $row['product_id'],
                'product_title'      => $row['product_title'],
                'listing_product_id' => $row['listing_product_id'],
                'description'        => $row['description'],
                'ebay_item_id'       => $ebayItemId,
                'marketplace_title'  => $row['marketplace_title']
            );
        }

        if (count($duplicatesInfo) <= 0) {
            return $this->getEmptyResultsHtml('According to you logs there are no duplicates.');
        }

        $tableContent = <<<HTML
<tr>
    <th>Listing ID</th>
    <th>Listing Title</th>
    <th>Product ID</th>
    <th>Product Title</th>
    <th>Listing Product ID</th>
    <th>eBay Item ID</th>
    <th>eBay Site</th>
    <th>Date</th>
</tr>
HTML;
        foreach ($duplicatesInfo as $row) {
            $tableContent .= <<<HTML
<tr>
    <td>{$row['listing_id']}</td>
    <td>{$row['listing_title']}</td>
    <td>{$row['product_id']}</td>
    <td>{$row['product_title']}</td>
    <td>{$row['listing_product_id']}</td>
    <td>{$row['ebay_item_id']}</td>
    <td>{$row['marketplace_title']}</td>
    <td>{$row['date']}</td>
</tr>
HTML;
        }

        $html = $this->getStyleHtml() . <<<HTML
<html>
    <body>
        <h2 style="margin: 20px 0 0 10px">eBay Duplicates
            <span style="color: #808080; font-size: 15px;">(#count# entries)</span>
        </h2>
        <br/>
        <table class="grid" cellpadding="0" cellspacing="0">
            {$tableContent}
        </table>
    </body>
</html>
HTML;
        return str_replace('#count#', count($duplicatesInfo), $html);
    }

    /**
     * @title "Show eBay Duplicates"
     * @description "[can be stopped and removed as option, by using remove=1 query param]"
     */
    public function showEbayDuplicatesAction()
    {
        $removeMode = (bool)$this->getRequest()->getParam('remove', false);

        $listingProduct = $this->resourceConnection->getTableName('m2epro_listing_product');
        $ebayListingProduct = $this->resourceConnection->getTableName('m2epro_ebay_listing_product');

        $subQuery = $this->resourceConnection->getConnection()
            ->select()
            ->from(array('melp' => $ebayListingProduct),
                   array())
            ->joinInner(array('mlp' => $listingProduct),
                'mlp.id = melp.listing_product_id',
                array('listing_id',
                    'product_id',
                    new \Zend_Db_Expr('COUNT(product_id) - 1 AS count_of_duplicates'),
                    new \Zend_Db_Expr('MIN(mlp.id) AS save_this_id'),
                ))
            ->group(array('mlp.product_id', 'mlp.listing_id'))
            ->having(new \Zend_Db_Expr('count_of_duplicates > 0'));

        $query = $this->resourceConnection->getConnection()
            ->select()
            ->from(array('melp' => $ebayListingProduct),
                   array('listing_product_id'))
            ->joinInner(array('mlp' => $listingProduct),
                        'mlp.id = melp.listing_product_id',
                        array('status'))
            ->joinInner(array('templ_table' => $subQuery),
                        'mlp.product_id = templ_table.product_id AND mlp.listing_id = templ_table.listing_id')
            ->where('melp.listing_product_id <> templ_table.save_this_id')
            ->query();

        $removed = 0;
        $stopped = 0;
        $duplicated = array();

        while ($row = $query->fetch()) {

            if ($removeMode) {

                if ($row['status'] == \Ess\M2ePro\Model\Listing\Product::STATUS_LISTED ||
                    $row['status'] == \Ess\M2ePro\Model\Listing\Product::STATUS_HIDDEN) {

                    $dispatcher = $this->modelFactory->getObject('Ebay\Connector\Item\Dispatcher');
                    $dispatcher->process(\Ess\M2ePro\Model\Listing\Product::ACTION_STOP,
                                         array($row['listing_product_id']));

                    $stopped++;
                }

                $this->resourceConnection->getConnection()->delete(
                    $listingProduct,
                    array('id = ?' => $row['listing_product_id'])
                );

                $this->resourceConnection->getConnection()->delete(
                    $ebayListingProduct,
                    array('listing_product_id = ?' => $row['listing_product_id'])
                );

                $removed++;
                continue;
            }

            $duplicated[$row['save_this_id']] = $row;
        }

        if (count($duplicated) <= 0) {

            $message = 'There are no duplicates.';
            $removed > 0 && $message .= ' Removed: ' . $removed;
            $stopped > 0 && $message .= ' Stopped: ' . $stopped;

            return $this->getEmptyResultsHtml($message);
        }

        $tableContent = <<<HTML
<tr>
    <th>Listing ID</th>
    <th>Magento Product ID</th>
    <th>Count Of Copies</th>
</tr>
HTML;
        foreach ($duplicated as $row) {
            $tableContent .= <<<HTML
<tr>
    <td>{$row['listing_id']}</td>
    <td>{$row['product_id']}</td>
    <td>{$row['count_of_duplicates']}</td>
</tr>
HTML;
        }

        $url = $this->getUrl('*/*/*', ['action' => 'showEbayDuplicates', 'remove' => '1']);
        $html = $this->getStyleHtml() . <<<HTML
<html>
    <body>
        <h2 style="margin: 20px 0 0 10px">eBay Duplicates
            <span style="color: #808080; font-size: 15px;">(#count# entries)</span>
        </h2>
        <br/>
        <table class="grid" cellpadding="0" cellspacing="0">
            {$tableContent}
        </table>
        <form action="{$url}" method="get" style="margin-top: 1em;">
            <button type="submit">Remove</button>
        </form>
    </body>
</html>
HTML;
        return str_replace('#count#', count($duplicated), $html);
    }

    /**
     * @title "Show Amazon Duplicates"
     * @description "[can be removed as option, by using remove=1 query param]"
     * @new_line
     */
    public function showAmazonDuplicatesAction()
    {
        $removeMode = (bool)$this->getRequest()->getParam('remove', false);

        $listingProduct = $this->resourceConnection->getTableName('m2epro_listing_product');
        $amazonListingProduct = $this->resourceConnection->getTableName('m2epro_amazon_listing_product');

        $subQuery = $this->resourceConnection->getConnection()
            ->select()
            ->from(array('malp' => $amazonListingProduct),
                array('general_id'))
            ->joinInner(array('mlp' => $listingProduct),
                'mlp.id = malp.listing_product_id',
                array('listing_id',
                    'product_id',
                    new \Zend_Db_Expr('COUNT(product_id) - 1 AS count_of_duplicates'),
                    new \Zend_Db_Expr('MIN(mlp.id) AS save_this_id'),
                ))
            ->group(array('mlp.product_id', 'malp.general_id', 'mlp.listing_id'))
            ->having(new \Zend_Db_Expr('count_of_duplicates > 0'));

        $query = $this->resourceConnection->getConnection()
            ->select()
            ->from(array('malp' => $amazonListingProduct),
                   array('listing_product_id'))
            ->joinInner(array('mlp' => $listingProduct),
                        'mlp.id = malp.listing_product_id',
                        array('status'))
            ->joinInner(array('templ_table' => $subQuery),
                        'mlp.product_id = templ_table.product_id AND
                         malp.general_id = templ_table.general_id AND
                         mlp.listing_id = templ_table.listing_id')
            ->where('malp.listing_product_id <> templ_table.save_this_id')
            ->query();

        $removed = 0;
        $duplicated = array();

        while ($row = $query->fetch()) {

            if ($removeMode) {

                $this->resourceConnection->getConnection()->delete(
                    $listingProduct,
                    array('id = ?' => $row['listing_product_id'])
                );

                $this->resourceConnection->getConnection()->delete(
                    $amazonListingProduct,
                    array('listing_product_id = ?' => $row['listing_product_id'])
                );

                $removed++;
                continue;
            }

            $duplicated[$row['save_this_id']] = $row;
        }

        if (count($duplicated) <= 0) {

            $message = 'There are no duplicates.';
            $removed > 0 && $message .= ' Removed: ' . $removed;

            return $this->getEmptyResultsHtml($message);
        }

        $tableContent = <<<HTML
<tr>
    <th>Listing ID</th>
    <th>Magento Product ID</th>
    <th>Count Of Copies</th>
</tr>
HTML;
        foreach ($duplicated as $row) {
            $tableContent .= <<<HTML
<tr>
    <td>{$row['listing_id']}</td>
    <td>{$row['product_id']}</td>
    <td>{$row['count_of_duplicates']}</td>
</tr>
HTML;
        }

        $url = $this->getUrl('*/*/*', ['action' => 'showAmazonDuplicates', 'remove' => '1']);
        $html = $this->getStyleHtml() . <<<HTML
<html>
    <body>
        <h2 style="margin: 20px 0 0 10px">Amazon Duplicates
            <span style="color: #808080; font-size: 15px;">(#count# entries)</span>
        </h2>
        <br/>
        <table class="grid" cellpadding="0" cellspacing="0">
            {$tableContent}
        </table>
        <form action="{$url}" method="get" style="margin-top: 1em;">
            <button type="submit">Remove</button>
        </form>
    </body>
</html>
HTML;
        return str_replace('#count#', count($duplicated), $html);
    }

    //########################################

    /**
     * @title "Fix many same categories templates [eBay]"
     * @description "[remove the same templates and set original templates to the settings of listings products]"
     * @new_line
     */
    public function fixManySameCategoriesTemplatesOnEbayAction()
    {
        $affectedListingProducts = $removedTemplates = 0;
        $statistics = array();
        $snapshots = array();

        foreach ($this->activeRecordFactory->getObject('Ebay\Template\Category')->getCollection() as $template) {
            $shot = $template->getDataSnapshot();
            unset($shot['id'], $shot['create_date'], $shot['update_date']);
            foreach ($shot['specifics'] as &$specific) {
                unset($specific['id'], $specific['template_category_id']);
            }
            $key = md5($this->getHelper('Data')->jsonEncode($shot));

            if (!array_key_exists($key, $snapshots)) {

                $snapshots[$key] = $template;
                continue;
            }

            foreach ($template->getAffectedListingsProducts(false) as $listingsProduct) {
                $originalTemplate = $snapshots[$key];
                $listingsProduct->getChildObject()->setData('template_category_id', $originalTemplate->getId())
                                                  ->save();

                $affectedListingProducts++;
            }

            $template->delete();
            $statistics['templates'][] = $template->getId();

            $removedTemplates++;
        }

        return <<<HTML
Templates were removed: {$removedTemplates}.<br>
Listings Product Affected: {$affectedListingProducts}.<br>
HTML;
    }

    //########################################

    private function getEmptyResultsHtml($messageText)
    {
        $backUrl = $this->getHelper('View\ControlPanel')->getPageModuleTabUrl();

        return <<<HTML
    <h2 style="margin: 20px 0 0 10px">
        {$messageText} <span style="color: grey; font-size: 10px;">
        <a href="{$backUrl}">[back]</a>
    </h2>
HTML;
    }

    //########################################
}