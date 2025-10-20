<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\ControlPanel\Module\Integration;

use Ess\M2ePro\Controller\Adminhtml\Context;
use Ess\M2ePro\Controller\Adminhtml\ControlPanel\Command;

class Amazon extends Command
{
    /** @var \Magento\Framework\Data\Form\FormKey */
    private $formKey;
    /** @var \Magento\Framework\File\Csv */
    private $csvParser;
    /** @var \Magento\Framework\HTTP\PhpEnvironment\Request */
    private $phpEnvironmentRequest;
    /** @var \Magento\Catalog\Model\ProductFactory */
    private $productFactory;

    public function __construct(
        \Magento\Framework\Data\Form\FormKey $formKey,
        \Magento\Framework\File\Csv $csvParser,
        \Magento\Framework\HTTP\PhpEnvironment\Request $phpEnvironmentRequest,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Ess\M2ePro\Helper\View\ControlPanel $controlPanelHelper,
        Context $context
    ) {
        parent::__construct($controlPanelHelper, $context);
        $this->formKey = $formKey;
        $this->csvParser = $csvParser;
        $this->phpEnvironmentRequest = $phpEnvironmentRequest;
        $this->productFactory = $productFactory;
    }

    /**
     * @title "Show Duplicates"
     * @description "[listing_id/sku]"
     */
    public function showAmazonDuplicatesAction()
    {
        $structureHelper = $this->getHelper('Module_Database_Structure');

        $lp = $structureHelper->getTableNameWithPrefix('m2epro_listing_product');
        $alp = $structureHelper->getTableNameWithPrefix('m2epro_amazon_listing_product');
        $alpr = $structureHelper->getTableNameWithPrefix('m2epro_amazon_listing_product_repricing');

        $subQuery = $this->resourceConnection->getConnection()
                                             ->select()
                                             ->from(
                                                 ['malp' => $alp],
                                                 ['general_id', 'sku']
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
                                              ['malp' => $alp],
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
                    $alp,
                    ['listing_product_id = ?' => $row['listing_product_id']]
                );

                $this->resourceConnection->getConnection()->delete(
                    $alpr,
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

    /**
     * @title "Repricer Print Request"
     */
    public function repricerPrintRequestAction()
    {
        $listingProductId = $this->_request->getParam('listing_product_id', '');
        $html = $this->getRepricerPrintRequestForm($listingProductId);
        if (!empty($listingProductId)) {
            $html .= $this->getRepricerHtml($listingProductId);
        }

        return $this->getResponse()->setBody($html);
    }

    private function getRepricerHtml($listingProductId): string
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Amazon\Listing\Product\Collection $amazonListingProductCollection */
        $amazonListingProductCollection = $this->_objectManager
            ->create(\Ess\M2ePro\Model\Amazon\Listing\Product::class)
            ->getCollection();
        $amazonListingProductCollection->addFieldToFilter('listing_product_id', $listingProductId);

        /** @var \Ess\M2ePro\Model\Amazon\Listing\Product $amazonListingProduct */
        $amazonListingProduct = $amazonListingProductCollection->getFirstItem();
        if (!$amazonListingProduct->getId()) {
            return $this->printErrorMessage(
                sprintf('Listing product with ID "%s" not found.', $listingProductId)
            );
        }

        /** @var \Ess\M2ePro\Model\ResourceModel\Amazon\Listing\Product\Repricing\Collection $repricingListingProductCollection */
        $repricingListingProductCollection = $this->_objectManager
            ->create(\Ess\M2ePro\Model\Amazon\Listing\Product\Repricing::class)
            ->getCollection();
        $repricingListingProductCollection->addFieldToFilter('listing_product_id', $listingProductId);

        /** @var \Ess\M2ePro\Model\Amazon\Listing\Product\Repricing $repricingListingProduct */
        $repricingListingProduct = $repricingListingProductCollection->getFirstItem();
        if (!$repricingListingProduct->getId()) {
            return $this->printErrorMessage(
                sprintf('No repricer is used for listing product with ID "%s"', $listingProductId)
            );
        }

        $repricingListingProduct->setListingProduct($amazonListingProduct->getParentObject());

        /** @var \Ess\M2ePro\Model\Amazon\Repricing\Updating $repricingUpdating */
        $repricingUpdating = $this->_objectManager
            ->create(\Ess\M2ePro\Model\Amazon\Repricing\Updating::class);
        $repricingUpdating->setAccount($amazonListingProduct->getParentObject()->getAccount());

        try {
            $result = $repricingUpdating->getChangeData($repricingListingProduct);
        } catch (\Ess\M2ePro\Model\Exception\Logic $exception) {
            $message = sprintf(
                '<h3>The product will not be sent to the repricer.</h3><p><strong>Product log text</strong>: %s</p><h3>Context:</h3>%s',
                $exception->getMessage(),
                $this->printJsonBlock([
                    'min_price' => $repricingListingProduct->getMinPrice(),
                    'regular_price' => $repricingListingProduct->getRegularPrice(),
                    'max_price' => $repricingListingProduct->getMaxPrice(),
                ])
            );

            return $this->printErrorMessage($message);
        } catch (\Exception $exception) {
            $message = sprintf(
                '<h3>Something went wrong.</h3><p><strong>Exception message</strong>: %s</p><h4>Exception Trace:</h4><pre>%s</pre>',
                $exception->getMessage(),
                $exception->getTraceAsString()
            );

            return $this->printErrorMessage($message);
        }

        if (empty($result)) {
            $context = $this->printJsonBlock([
                'repricing_account_data' => $repricingListingProduct->getAccountRepricing()->getData(),
            ]);

            return $this->printErrorMessage(
                '<h3>No data will be sent to the repricer.</h3><h3>Context</h3>' . $context
            );
        }

        return $this->printJsonBlock($result);
    }

    private function getRepricerPrintRequestForm($listingProductId): string
    {
        return <<<HTML
<style>
pre {
    white-space: pre-wrap;       /* Since CSS 2.1 */
    white-space: -moz-pre-wrap;  /* Mozilla, since 1999 */
    white-space: -o-pre-wrap;    /* Opera 7 */
    word-wrap: break-word;       /* Internet Explorer 5.5+ */
}
.form-wrap {
    color: #383d41;
    background-color: #e2e3e5;
    border: 1px solid #d6d8db;
    border-radius: .25rem;
    padding: .75rem 1.25rem;
    margin-bottom: 3px;
}
.form-wrap form { margin: 0}
.form-row:not(:last-child) {margin-bottom: 10px}
.btn {padding: .375rem .75rem; cursor: pointer}
.btn.primary {color: #fff;  background-color: #007bff; border: 1px solid #007bff}
.btn.primary:hover {background-color: #0069d9; border-color: #0062cc}
.form-wrap input {border: 1px solid #ced4da; color: #495057; border-radius: .25rem; padding: .375rem .75rem}
</style>

<div class="form-wrap">
<form>
<div class="form-row">
    <label for="listing_product_id">Listing Product ID:</label>
    <input id="listing_product_id" name="listing_product_id" value="$listingProductId" required>
</div>
<div class="form-row">
    <input type="submit" class="btn primary" value="Print Repricer Request">
</div>
</form>
</div>
HTML;
    }

    private function printErrorMessage($message): string
    {
        return <<<HTML
<style>
.error-message {
    color: #721c24;
    padding: .75rem 1.25rem;
    background-color: #f8d7da;
    border: 1px solid #f5c6cb;
    border-radius: .25rem
}
</style>
<div class="error-message">
<p>$message</p>
</div>
HTML;
    }

    private function printJsonBlock(array $data): string
    {
        $dataHtml = json_encode($data, JSON_PRETTY_PRINT);

        return <<<HTML
<style>
.json-code {
    color: #383d41;
    background-color: #e2e3e5;
    border: 1px solid #d6d8db;
    border-radius: .25rem;
    padding: .75rem 1.25rem;
    margin-bottom: 3px;
}
</style>
<div class="json-code">
    <pre>$dataHtml</pre>
</div>
HTML;
    }
}
