<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\ControlPanel\Module\Integration;

use Ess\M2ePro\Controller\Adminhtml\Context;
use Ess\M2ePro\Controller\Adminhtml\ControlPanel\Command;

class Walmart extends Command
{
    private $formKey;
    private $csvParser;
    private $phpEnvironmentRequest;
    private $productFactory;
    /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\CollectionFactory */
    private $listingProductCollectionFactory;
    /** @var \Ess\M2ePro\Model\ResourceModel\Listing */
    private $listingResource;
    /** @var \Ess\M2ePro\Model\ResourceModel\Walmart\Item */
    private $walmartItemResource;
    /** @var \Ess\M2ePro\Model\Walmart\Listing\Product\Action\Type\ListAction\LinkingFactory */
    private $walmartLinkingFactory;
    /** @var \Ess\M2ePro\Helper\Module\Configuration */
    private $moduleConfiguration;

    public function __construct(
        \Magento\Framework\Data\Form\FormKey $formKey,
        \Magento\Framework\File\Csv $csvParser,
        \Magento\Framework\HTTP\PhpEnvironment\Request $phpEnvironmentRequest,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Ess\M2ePro\Helper\View\ControlPanel $controlPanelHelper,
        \Ess\M2ePro\Helper\Module\Configuration $moduleConfiguration,
        \Ess\M2ePro\Model\ResourceModel\Listing\Product\CollectionFactory $listingProductCollectionFactory,
        \Ess\M2ePro\Model\ResourceModel\Listing $listingResource,
        \Ess\M2ePro\Model\ResourceModel\Walmart\Item $walmartItemResource,
        \Ess\M2ePro\Model\Walmart\Listing\Product\Action\Type\ListAction\LinkingFactory $walmartLinkingFactory,
        Context $context
    ) {
        parent::__construct($controlPanelHelper, $context);
        $this->formKey = $formKey;
        $this->csvParser = $csvParser;
        $this->phpEnvironmentRequest = $phpEnvironmentRequest;
        $this->productFactory = $productFactory;
        $this->listingProductCollectionFactory = $listingProductCollectionFactory;
        $this->listingResource = $listingResource;
        $this->walmartItemResource = $walmartItemResource;
        $this->walmartLinkingFactory = $walmartLinkingFactory;
        $this->moduleConfiguration = $moduleConfiguration;
    }

    /**
     * @title "Show Duplicates [listing_id/sku]"
     * @description "Show Duplicates [listing_id/sku]"
     */
    public function showWalmartDuplicatesAction()
    {
        $structureHelper = $this->getHelper('Module_Database_Structure');

        $lp = $structureHelper->getTableNameWithPrefix('m2epro_listing_product');
        $wlp = $structureHelper->getTableNameWithPrefix('m2epro_walmart_listing_product');

        $subQuery = $this->resourceConnection->getConnection()
            ->select()
            ->from(
                ['malp' => $wlp],
                ['wpid', 'sku']
            )
            ->joinInner(
                ['mlp' => $lp],
                'mlp.id = malp.listing_product_id',
                [
                    'listing_id',
                    'product_id',
                    new \Zend_Db_Expr('COUNT(product_id) - 1 AS count_of_duplicates'),
                    new \Zend_Db_Expr('MIN(mlp.id) AS save_this_id'),
                ]
            )
            ->group(['mlp.product_id', 'malp.sku'])
            ->having(new \Zend_Db_Expr('count_of_duplicates > 0'));

        $query = $this->resourceConnection->getConnection()
            ->select()
            ->from(
                ['malp' => $wlp],
                ['listing_product_id']
            )
            ->joinInner(
                ['mlp' => $lp],
                'mlp.id = malp.listing_product_id',
                ['status']
            )
            ->joinInner(
                ['templ_table' => $subQuery],
                'malp.sku = templ_table.sku AND mlp.listing_id = templ_table.listing_id'
            )
            ->where('malp.listing_product_id <> templ_table.save_this_id')
            ->query();

        $removed = 0;
        $duplicated = [];

        while ($row = $query->fetch()) {
            if ((bool)$this->getRequest()->getParam('remove', false)) {
                $this->resourceConnection->getConnection()->delete(
                    $lp,
                    ['id = ?' => $row['listing_product_id']]
                );

                $this->resourceConnection->getConnection()->delete(
                    $wlp,
                    ['listing_product_id = ?' => $row['listing_product_id']]
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
    <th>SKU</th>
    <th>Count Of Copies</th>
</tr>
HTML;
        foreach ($duplicated as $row) {
            $tableContent .= <<<HTML
<tr>
    <td>{$row['listing_id']}</td>
    <td>{$row['product_id']}</td>
    <td>{$row['sku']}</td>
    <td>{$row['count_of_duplicates']}</td>
</tr>
HTML;
        }

        $url = $this->getUrl('*/*/*', ['action' => 'showWalmartDuplicates', 'remove' => '1']);
        $html = $this->getStyleHtml() . <<<HTML
<html>
    <body>
        <h2 style="margin: 20px 0 0 10px">Walmart Duplicates [group by SKU and listing_id]
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
     * @title "Fix Walmart Items"
     * @description "Insert records in walmart_items table"
     */
    public function fixWalmartItemsAction(): string
    {
        $listingProductCollection = $this->listingProductCollectionFactory->create([
            'childMode' => \Ess\M2ePro\Helper\Component\Walmart::NICK,
        ]);
        $listingProductCollection->addFieldToFilter(
            'status',
            ['neq' => \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED]
        );
        $listingProductCollection->getSelect()->joinLeft(
            ['l' => $this->listingResource->getMainTable()],
            'main_table.listing_id = l.id',
            []
        );
        $listingProductCollection->getSelect()->joinLeft(
            ['wi' => $this->walmartItemResource->getMainTable()],
            <<<CONDITION
second_table.sku = wi.sku
AND l.account_id = wi.account_id
AND l.marketplace_id = wi.marketplace_id
CONDITION
            ,
            []
        );
        $listingProductCollection->addFieldToFilter('wi.sku', ['null' => true]);

        $startFix = (bool)$this->getRequest()->getParam('start_fix', false);
        if ($startFix) {
            $start = microtime(true);
            $linkingObject = $this->walmartLinkingFactory->create();
            /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */
            foreach ($listingProductCollection->getItems() as $listingProduct) {
                if (
                    $this->moduleConfiguration->isGroupedProductModeSet()
                    && $listingProduct->getMagentoProduct()->isGroupedType()
                ) {
                    $listingProduct->setSetting('additional_data', 'grouped_product_mode', 1);
                    $listingProduct->save();
                }

                $linkingObject->setListingProduct($listingProduct);
                $linkingObject->createWalmartItem();
            }

            $message = sprintf(
                'Listing product fixed. Executed time %01.4f sec',
                microtime(true) - $start
            );
            $this->messageManager->addSuccessMessage($message, 'm2epro_walmart_item_fixer');
            return $this->_redirect($this->redirect->getRefererUrl());
        }

        $messagesCollection = $this->messageManager->getMessages(true, 'm2epro_walmart_item_fixer');
        $successMessage = '';
        if ($messagesCollection->getCount() > 0) {
            $successMessages = array_map(static function (\Magento\Framework\Message\MessageInterface $message) {
                return $message->getText();
            }, $messagesCollection->getItems());

            $successMessage = '<p class="success">';
            $successMessage .= implode('<br>', $successMessages);
            $successMessage .= '</p>';
        }

        $backUrl = $this->controlPanelHelper->getPageModuleTabUrl();
        $count = $listingProductCollection->getSize();

        return <<<HTML
<html>
    <head>
        <title>M2E Pro | Fix Walmart Items</title>
        <style>
            button {
                border-radius: 3px;
                padding: 7px;
                border: 1px solid grey;
                cursor: pointer;
            }
            button:hover {
                background-color: lightgrey;
            }
            p.success {
                color: darkgreen
            }
        </style>
    </head>
    <body>
        <a href="$backUrl">⇦ Back to Control Panel</a>
        <h2>Fix Walmart Items</h2>
        <p>Listing products without record in <code>walmart_item</code> table: <strong>$count</strong></p>
        $successMessage
        <form method="get">
            <input type="hidden" name="start_fix" value="1">
            <button type="submit">Start Fix</button>
        </form>
    </body>
</html>
HTML;
    }

    //########################################

    private function getEmptyResultsHtml($messageText)
    {
        $backUrl = $this->controlPanelHelper->getPageModuleTabUrl();

        return <<<HTML
    <h2 style="margin: 20px 0 0 10px">
        {$messageText} <span style="color: grey; font-size: 10px;">
        <a href="{$backUrl}">[back]</a>
    </h2>
HTML;
    }
}
