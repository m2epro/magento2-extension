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
                "/{$component}/templates/synchronization/revise/total/",
                'start_date'
            );

            $reviseAllEndDate = $this->synchConfig->getGroupValue(
                "/{$component}/templates/synchronization/revise/total/",
                'end_date'
            );

            $reviseAllInProcessingState = $this->synchConfig->getGroupValue(
                "/{$component}/templates/synchronization/revise/total/",
                'last_listing_product_id'
            ) !== null;

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
            'start_date',
            $this->getHelper('Data')->getCurrentGmtDate()
        );

        $this->synchConfig->setGroupValue(
            "/{$component}/templates/synchronization/revise/total/",
            'end_date',
            null
        );

        $this->synchConfig->setGroupValue(
            "/{$component}/templates/synchronization/revise/total/",
            'last_listing_product_id',
            0
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
            "/{$component}/templates/synchronization/revise/total/",
            'last_listing_product_id',
            null
        );

        return $this->_redirect('*/*/*', ['action' => 'reviseTotal']);
    }

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
                $elp = $lp->getChildObject();

                $configurator = $this->modelFactory->getObject('Ebay_Listing_Product_Action_Configurator');

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

                    $request->setParams([
                        'out_of_stock_control_current_state' => $outOfStockControlCurrentState,
                        'out_of_stock_control_result'        => $outOfStockControlResult,
                    ]);
                }

                return '<pre>' . print_r($request->getRequestData(), true);
            }

            if ($componentMode == 'amazon') {
                $configurator = $this->modelFactory->getObject('Amazon_Listing_Product_Action_Configurator');

                $request = $this->modelFactory->getObject(
                    'Amazon\Listing\Product\Action\Type\\'.$requestType.'\Request'
                );
                $request->setParams([]);
                $request->setListingProduct($lp);
                $request->setConfigurator($configurator);

                if ($requestType == 'ListAction') {
                    $request->setValidatorsData([
                        'sku'        => 'placeholder',
                        'general_id' => 'placeholder',
                        'list_type'  => 'placeholder'
                    ]);
                }

                return '<pre>' . print_r($request->getRequestData(), true);
            }

            if ($componentMode == 'walmart') {
                $configurator = $this->modelFactory->getObject('Walmart_Listing_Product_Action_Configurator');

                $request = $this->modelFactory->getObject(
                    'Walmart\Listing\Product\Action\Type\\'.$requestType.'\Request'
                );
                $request->setParams([]);
                $request->setListingProduct($lp);
                $request->setConfigurator($configurator);

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
     * @new_line
     */
    public function getInspectorDataAction()
    {
        if ($this->getRequest()->getParam('print')) {
            $listingProductId = $this->getRequest()->getParam('listing_product_id');

            if ($this->getRequest()->getParam('component_mode') == 'ebay') {
                $lp = $this->parentFactory->getObjectLoaded(Ebay::NICK, 'Listing\Product', $listingProductId);
                $elp = $lp->getChildObject();

                $insp = $this->modelFactory->getObject('Ebay_Synchronization_Templates_Synchronization_Inspector');

                $html = '';

                $html .= '<pre>isMeetListRequirements: ' .$insp->isMeetListRequirements($lp). '<br>';
                $html .= '<pre>isMeetRelistRequirements: ' .$insp->isMeetRelistRequirements($lp). '<br>';
                $html .= '<pre>isMeetStopRequirements: ' .$insp->isMeetStopRequirements($lp). '<br>';
                $html .= '<pre>isMeetReviseGeneralRequirements: ' .$insp->isMeetReviseGeneralRequirements($lp). '<br>';
                $html .= '<pre>isMeetRevisePriceRequirements: ' .$insp->isMeetRevisePriceRequirements($lp). '<br>';
                $html .= '<pre>isMeetReviseQtyRequirements: ' .$insp->isMeetReviseQtyRequirements($lp). '<br>';
                $html .= '<pre>isMeetAdvancedListRequirements: ' .$insp->isMeetAdvancedListRequirements($lp). '<br>';
                $html .= '<pre>isMeetAdvancedRelistRequirements: '.$insp->isMeetAdvancedRelistRequirements($lp).'<br>';
                $html .= '<pre>isMeetAdvancedStopRequirements: ' .$insp->isMeetAdvancedStopRequirements($lp). '<br>';

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

                $insp = $this->modelFactory->getObject('Amazon_Synchronization_Templates_Synchronization_Inspector');

                $html = '';

                $html .= '<pre>isMeetList: ' .$insp->isMeetListRequirements($lp). '<br>';
                $html .= '<pre>isMeetRelist: ' .$insp->isMeetRelistRequirements($lp). '<br>';
                $html .= '<pre>isMeetStop: ' .$insp->isMeetStopRequirements($lp). '<br>';
                $html .= '<pre>isMeetReviseGeneral: ' .$insp->isMeetReviseGeneralRequirements($lp). '<br>';
                $html .= '<pre>isMeetReviseRegularPrice: '.$insp->isMeetReviseRegularPriceRequirements($lp).'<br>';
                $html .= '<pre>isMeetReviseBusinessPrice: '.$insp->isMeetReviseBusinessPriceRequirements($lp).'<br>';
                $html .= '<pre>isMeetReviseQty: ' .$insp->isMeetReviseQtyRequirements($lp). '<br>';
                $html .= '<pre>isMeetAdvancedListRequirements: ' .$insp->isMeetAdvancedListRequirements($lp). '<br>';
                $html .= '<pre>isMeetAdvancedRelistRequirements: '.$insp->isMeetAdvancedRelistRequirements($lp).'<br>';
                $html .= '<pre>isMeetAdvancedStopRequirements: ' .$insp->isMeetAdvancedStopRequirements($lp). '<br>';

                $html .= '<pre>isStatusEnabled: ' .($lp->getMagentoProduct()->isStatusEnabled()). '<br>';
                $html .= '<pre>isStockAvailability: ' .($lp->getMagentoProduct()->isStockAvailability()). '<br>';

                return $html;
            }

            if ($this->getRequest()->getParam('component_mode') == 'walmart') {
                $lp = $this->parentFactory->getObjectLoaded(Walmart::NICK, 'Listing\Product', $listingProductId);

                $insp = $this->modelFactory->getObject('Walmart_Synchronization_Templates_Synchronization_Inspector');

                $html = '';

                $html .= '<pre>isMeetList: ' .$insp->isMeetListRequirements($lp). '<br>';
                $html .= '<pre>isMeetRelist: ' .$insp->isMeetRelistRequirements($lp). '<br>';
                $html .= '<pre>isMeetStop: ' .$insp->isMeetStopRequirements($lp). '<br>';
                $html .= '<pre>isMeetReviseGeneral: ' .$insp->isMeetReviseGeneralRequirements($lp). '<br>';
                $html .= '<pre>isMeetRevisePrice: '.$insp->isMeetRevisePriceRequirements($lp).'<br>';
                $html .= '<pre>isMeetRevisePromotions: '.$insp->isMeetRevisePromotionsPriceRequirements($lp).'<br>';
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

    //########################################

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

            $html = '';

            $html .= '<pre><b>Grand Total:</b> ' .$quote->getGrandTotal(). '<br>';
            $html .= '<pre><b>Shipping Amount:</b> ' .$quote->getShippingAddress()->getShippingAmount(). '<br>';

            $html .= '<pre><b>Quote Data:</b> ' .print_r($quoteData, true). '<br>';
            $html .= '<pre><b>Shipping Address Data:</b> ' .print_r($shippingAddressData, true). '<br>';
            $html .= '<pre><b>Billing Address Data:</b> ' .print_r($billingAddressData, true). '<br>';

            $magentoQuoteManager->save($quote->setIsActive(false));

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
     * @title "Search Troubles With Parallel Execution"
     * @description "By operation history table"
     */
    public function searchTroublesWithParallelExecutionAction()
    {
        if (!$this->getRequest()->getParam('print')) {
            $formKey = $this->formKey->getFormKey();
            $actionUrl = $this->getUrl('*/*/*', ['action' => 'searchTroublesWithParallelExecution']);

            $collection = $this->activeRecordFactory->getObject('OperationHistory')->getCollection();
            $collection->getSelect()->reset(\Zend_Db_Select::COLUMNS);
            $collection->getSelect()->columns(['nick']);
            $collection->getSelect()->order('nick ASC');
            $collection->getSelect()->distinct();

            $optionsHtml = '';
            foreach ($collection->getItems() as $item) {
                /** @var \Ess\M2ePro\Model\OperationHistory $item */
                $optionsHtml .= <<<HTML
<option value="{$item->getData('nick')}">{$item->getData('nick')}</option>
HTML;
            }

            return <<<HTML
<form method="get" enctype="multipart/form-data" action="{$actionUrl}">

    <div style="margin: 5px 0; width: 400px;">
        <label style="width: 170px; display: inline-block;">Search by nick: </label>
        <select name="nick" style="width: 200px;" required>
            <option value="" style="display: none;"></option>
            {$optionsHtml}
        </select>
    </div>

    <input name="form_key" value="{$formKey}" type="hidden" />
    <input name="print" value="1" type="hidden" />

    <div style="margin: 10px 0; width: 365px; text-align: right;">
        <button type="submit">Search</button>
    </div>

</form>
HTML;
        }

        $searchByNick = (string)$this->getRequest()->getParam('nick');

        $collection = $this->activeRecordFactory->getObject('OperationHistory')->getCollection();
        $collection->addFieldToFilter('nick', $searchByNick);
        $collection->getSelect()->order('id ASC');

        $results = [];
        $prevItem = null;

        foreach ($collection->getItems() as $item) {
            /** @var \Ess\M2ePro\Model\OperationHistory $item */
            /** @var \Ess\M2ePro\Model\OperationHistory $prevItem */

            if ($item->getData('end_date') === null) {
                continue;
            }

            if ($prevItem === null) {
                $prevItem = $item;
                continue;
            }

            $prevEnd   = new \DateTime($prevItem->getData('end_date'), new \DateTimeZone('UTC'));
            $currStart = new \DateTime($item->getData('start_date'), new \DateTimeZone('UTC'));

            if ($currStart->getTimeStamp() < $prevEnd->getTimeStamp()) {
                $results[$item->getId().'##'.$prevItem->getId()] = [
                    'curr' => [
                        'id'    => $item->getId(),
                        'start' => $item->getData('start_date'),
                        'end'   => $item->getData('end_date')
                    ],
                    'prev' => [
                        'id'    => $prevItem->getId(),
                        'start' => $prevItem->getData('start_date'),
                        'end'   => $prevItem->getData('end_date')
                    ],
                ];
            }

            $prevItem = $item;
        }

        if (count($results) <= 0) {
            return $this->getEmptyResultsHtml('There are no troubles with a parallel work of crons.');
        }

        $tableContent = <<<HTML
<tr>
    <th>Num</th>
    <th>Type</th>
    <th>ID</th>
    <th>Started</th>
    <th>Finished</th>
    <th>Total</th>
    <th>Delay</th>
</tr>
HTML;
        $index = 1;
        $results = array_reverse($results, true);

        foreach ($results as $key => $row) {
            $currStart = new \DateTime($row['curr']['start'], new \DateTimeZone('UTC'));
            $currEnd   = new \DateTime($row['curr']['end'], new \DateTimeZone('UTC'));
            $currTime = $currEnd->diff($currStart);
            $currTime = $currTime->format('%H:%I:%S');

            $currUrlUp = $this->getUrl(
                '*/controlPanel_database/showOperationHistoryExecutionTreeUp',
                ['operation_history_id' => $row['curr']['id']]
            );
            $currUrlDown = $this->getUrl(
                '*/controlPanel_database/showOperationHistoryExecutionTreeDown',
                ['operation_history_id' => $row['curr']['id']]
            );

            $prevStart = new \DateTime($row['prev']['start'], new \DateTimeZone('UTC'));
            $prevEnd   = new \DateTime($row['prev']['end'], new \DateTimeZone('UTC'));
            $prevTime = $prevEnd->diff($prevStart);
            $prevTime = $prevTime->format('%H:%I:%S');

            $prevUrlUp = $this->getUrl(
                '*/controlPanel_database/showOperationHistoryExecutionTreeUp',
                ['operation_history_id' => $row['prev']['id']]
            );
            $prevUrlDown = $this->getUrl(
                '*/controlPanel_database/showOperationHistoryExecutionTreeDown',
                ['operation_history_id' => $row['prev']['id']]
            );

            $delayTime = $currStart->diff($prevStart);
            $delayTime = $delayTime->format('%H:%I:%S');

            $tableContent .= <<<HTML
<tr>
    <td rowspan="2">{$index}</td>
    <td>Previous</td>
    <td>
        {$row['prev']['id']}&nbsp;
        <a style="color: green;" href="{$prevUrlUp}" target="_blank"><span>&uarr;</span></a>&nbsp;
        <a style="color: green;" href="{$prevUrlDown}" target="_blank"><span>&darr;</span></a>
    </td>
    <td><span>{$row['prev']['start']}</span></td>
    <td><span>{$row['prev']['end']}</span></td>
    <td><span>{$prevTime}</span></td>
    <td rowspan="2"><span>{$delayTime}</span>
</tr>
<tr>
    <td>Current</td>
    <td>
        {$row['curr']['id']}&nbsp;
        <a style="color: green;" href="{$currUrlUp}" target="_blank"><span>&uarr;</span></a>&nbsp;&nbsp;
        <a style="color: green;" href="{$currUrlDown}" target="_blank"><span>&darr;</span></a>
        </td>
    <td><span>{$row['curr']['start']}</span></td>
    <td><span>{$row['curr']['end']}</span></td>
    <td><span>{$currTime}</span></td>
</tr>
HTML;
            $index++;
        }

        $html = $this->getStyleHtml() . <<<HTML
<html>
    <body>
        <h2 style="margin: 20px 0 0 10px">Parallel work of [{$searchByNick}]
            <span style="color: #808080; font-size: 15px;">(#count# entries)</span>
        </h2>
        <br/>
        <table class="grid" cellpadding="0" cellspacing="0">
            {$tableContent}
        </table>
    </body>
</html>
HTML;
        return str_replace('#count#', count($results), $html);
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
