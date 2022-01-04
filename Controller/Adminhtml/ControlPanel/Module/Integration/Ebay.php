<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\ControlPanel\Module\Integration;

use Ess\M2ePro\Controller\Adminhtml\Context;
use Ess\M2ePro\Controller\Adminhtml\ControlPanel\Command;
use Ess\M2ePro\Helper\Component\Ebay as EbayHelper;
use Ess\M2ePro\Model\ControlPanel\Inspection\Manager;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\ControlPanel\Module\Integration\Ebay
 */
class Ebay extends Command
{
    /** @var \Ess\M2ePro\Model\ControlPanel\Inspection\Manager Manager  */
    private $inspectionManager;

    //########################################

    public function __construct(
        Manager $inspectionManager,
        Context $context
    ) {
        $this->inspectionManager = $inspectionManager;
        parent::__construct($context);
    }

    //########################################

    /**
     * @title "Stop Unmanaged"
     * @description "[in order to resolve the problem with duplicates]"
     * @new_line
     */
    public function stopEbay3rdPartyAction()
    {
        $collection = $this->parentFactory->getObject(EbayHelper::NICK, 'Listing\Other')->getCollection();
        $collection->addFieldToFilter('status', ['in' => [
            \Ess\M2ePro\Model\Listing\Product::STATUS_LISTED,
            \Ess\M2ePro\Model\Listing\Product::STATUS_HIDDEN
        ]]);

        $total       = 0;
        $groupedData = [];

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
                $dispatcherObject = $this->modelFactory->getObject('Ebay_Connector_Dispatcher');
                $connectorObj = $dispatcherObject->getVirtualConnector(
                    'item',
                    'update',
                    'ends',
                    ['items' => $itemsPart],
                    null,
                    $marketplaceId,
                    $accountId
                );

                $dispatcherObject->process($connectorObj);
                $response = $connectorObj->getResponseData();

                foreach ($response['result'] as $itemId => $iResp) {
                    $item = $this->parentFactory->getObjectLoaded(
                        EbayHelper::NICK,
                        'Listing\Other',
                        $itemId,
                        null,
                        false
                    );
                    if ($item !== null &&
                        ((isset($iResp['already_stop']) && $iResp['already_stop']) ||
                            isset($iResp['ebay_end_date_raw']))) {
                        $item->setData('status', \Ess\M2ePro\Model\Listing\Product::STATUS_STOPPED)->save();
                    }
                }
            }
        }

        return "Processed {$total} products.";
    }

    //########################################

    /**
     * @title "Set EPS Images Mode"
     * @description "Set EPS Images Mode = true for listing products"
     * @prompt "Please enter Listing Product ID or `all` code for all products."
     * @prompt_var "listing_product_id"
     */
    public function setEpsImagesModeAction()
    {
        $listingProductId = $this->getRequest()->getParam('listing_product_id');

        $listingProducts = [];
        if (strtolower($listingProductId) == 'all') {
            $listingProducts = $this->parentFactory->getObject(EbayHelper::NICK, 'Listing\Product')->getCollection();
        } else {
            $listingProduct = $this->parentFactory
                ->getObjectLoaded(EbayHelper::NICK, 'Listing\Product', $listingProductId);
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

        $this->getMessageManager()->addSuccess("Set for {$affected} affected Products.");
        return $this->_redirect($this->getHelper('View\ControlPanel')->getPageModuleTabUrl());
    }

    /**
     * @hidden
     */
    public function showNonexistentTemplatesAction()
    {
        $fixData = [
            'field'       => $this->getRequest()->getParam('field'),
            'template'    => $this->getRequest()->getParam('template'),
            'field_value' => $this->getRequest()->getParam('field_value'),
            'action'      => $this->getRequest()->getParam('action'),
            'template_id' => $this->getRequest()->getParam('template_id', false)
        ];

        $inspector = $this->inspectionManager
            ->getInspection('NonexistentTemplates');
        $inspector->fix($fixData);

        $this->_redirect($this->_redirect->getRefererUrl());
    }

    /**
     * @title "Show Duplicates [product_id/listing_id]"
     * @description "[MIN(id) will be saved]"
     * @new_line
     */
    public function showEbayDuplicatesAction()
    {
        $structureHelper = $this->getHelper('Module_Database_Structure');

        $listingProduct = $structureHelper->getTableNameWithPrefix('m2epro_listing_product');
        $ebayListingProduct = $structureHelper->getTableNameWithPrefix('m2epro_ebay_listing_product');
        $ebayItem = $structureHelper->getTableNameWithPrefix('m2epro_ebay_item');

        $subQuery = $this->resourceConnection->getConnection()
            ->select()
            ->from(
                ['melp' => $ebayListingProduct],
                []
            )
            ->joinInner(
                ['mlp' => $listingProduct],
                'mlp.id = melp.listing_product_id',
                ['listing_id',
                    'product_id',
                    new \Zend_Db_Expr('COUNT(product_id) - 1 AS count_of_duplicates'),
                    new \Zend_Db_Expr('MIN(mlp.id) AS save_this_id'),
                ]
            )
            ->group(['mlp.product_id', 'mlp.listing_id'])
            ->having(new \Zend_Db_Expr('count_of_duplicates > 0'));

        $query = $this->resourceConnection->getConnection()
            ->select()
            ->from(
                ['melp' => $ebayListingProduct],
                ['listing_product_id', 'ebay_item_id']
            )
            ->joinInner(
                ['mlp' => $listingProduct],
                'mlp.id = melp.listing_product_id',
                ['status']
            )
            ->joinInner(
                ['templ_table' => $subQuery],
                'mlp.product_id = templ_table.product_id AND
                         mlp.listing_id = templ_table.listing_id'
            )
            ->where('melp.listing_product_id <> templ_table.save_this_id')
            ->query();

        $removed = 0;
        $stopped = 0;
        $duplicated = [];

        while ($row = $query->fetch()) {
            if ((bool)$this->getRequest()->getParam('remove', false)) {
                if ($row['status'] == \Ess\M2ePro\Model\Listing\Product::STATUS_LISTED ||
                    $row['status'] == \Ess\M2ePro\Model\Listing\Product::STATUS_HIDDEN) {
                    $dispatcher = $this->modelFactory->getObject('Ebay_Connector_Item_Dispatcher');
                    $dispatcher->process(
                        \Ess\M2ePro\Model\Listing\Product::ACTION_STOP,
                        [$row['listing_product_id']]
                    );

                    $stopped++;
                }

                $this->resourceConnection->getConnection()->delete(
                    $listingProduct,
                    ['id = ?' => $row['listing_product_id']]
                );

                $this->resourceConnection->getConnection()->delete(
                    $ebayListingProduct,
                    ['listing_product_id = ?' => $row['listing_product_id']]
                );

                $this->resourceConnection->getConnection()->delete(
                    $ebayItem,
                    ['id = ?' => $row['ebay_item_id']]
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

    //########################################

    /**
     * @title "Try to fix variation product"
     * @description "[]"
     */
    public function tryToFixVariationProductAction()
    {
        if ((bool)$this->getRequest()->getParam('fix', false)) {

            /** @var \Ess\M2ePro\Model\Listing\Product $lp */
            $lpId = $this->getRequest()->getParam('listing_product_id');
            $lp = $this->parentFactory->getObjectLoaded(EbayHelper::NICK, 'Listing\Product', $lpId);

            /** @var \Ess\M2ePro\Model\Ebay\Listing\Product\Variation\Resolver $resolver */
            $resolver = $this->modelFactory->getObject('Ebay_Listing_Product_Variation_Resolver');
            $resolver->setListingProduct($lp);
            $resolver->setIsAllowedToSave((bool)$this->getRequest()->getParam('allowed_to_save'));
            $resolver->setIsAllowedToProcessVariationsWhichAreNotExistInTheModule(true);
            $resolver->setIsAllowedToProcessExistedVariations(true);
            $resolver->setIsAllowedToProcessVariationMpnErrors(true);

            $resolver->resolve();

            $errors = $warnings = $notices = [];
            foreach ($resolver->getMessagesSet()->getEntities() as $message) {
                $message->isError() && $errors[] = $message->getText();
                $message->isWarning() && $warnings[] = $message->getText();
                $message->isNotice() && $notices[] = $message->getText();
            }

            return '<pre>' .
                        sprintf('Listing Product ID: %s<br/><br/>', $lpId) .
                        sprintf('Errors: %s<br/><br/>', print_r($errors, true)) .
                        sprintf('Warnings: %s<br/><br/>', print_r($warnings, true)) .
                        sprintf('Notices: %s<br/><br/>', print_r($notices, true)) .
                   '</pre>';
        }

        $url = $this->getUrl('*/*/*', ['action' => 'tryToFixVariationProduct']);

        return <<<HTML
<form method="get" enctype="multipart/form-data" action="{$url}">

    <div style="margin: 5px 0; width: 400px;">
        <label style="width: 170px; display: inline-block;">Listing Product ID: </label>
        <input name="listing_product_id" style="width: 200px;" required>
    </div>

    <div style="margin: 5px 0; width: 400px;">
        <label style="width: 170px; display: inline-block;">Allowed to save Item: </label>
        <select name="allowed_to_save" style="width: 200px;" required>
            <option style="display: none;"></option>
            <option value="1">YES</option>
            <option value="0">NO</option>
        </select>
    </div>

    <input name="fix" value="1" type="hidden" />

    <div style="margin: 10px 0; width: 365px; text-align: right;">
        <button type="submit">Show</button>
    </div>

</form>
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
