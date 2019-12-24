<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\ControlPanel\Module\Integration;

use Ess\M2ePro\Controller\Adminhtml\Context;
use Ess\M2ePro\Controller\Adminhtml\ControlPanel\Command;
use Ess\M2ePro\Helper\Component\Amazon as AmazonHelper;
use Ess\M2ePro\Model\Amazon\Account;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\ControlPanel\Module\Integration\Amazon
 */
class Amazon extends Command
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
     * @title "Reset 3rd Party"
     * @description "Clear all 3rd party items for all Accounts"
     */
    public function resetOtherListingsAction()
    {
        $listingOther = $this->parentFactory->getObject(AmazonHelper::NICK, 'Listing\Other');
        $amazonListingOther = $this->activeRecordFactory->getObject('Amazon_Listing_Other');

        $stmt = $listingOther->getResourceCollection()->getSelect()->query();

        $SKUs = [];
        foreach ($stmt as $row) {
            $listingOther->setData($row);
            $amazonListingOther->setData($row);

            $listingOther->setChildObject($amazonListingOther);
            $amazonListingOther->setParentObject($listingOther);
            $SKUs[] = $amazonListingOther->getSku();

            $listingOther->delete();
        }

        $tableName = $this->getHelper('Module_Database_Structure')->getTableNameWithPrefix('m2epro_amazon_item');
        foreach (array_chunk($SKUs, 1000) as $chunkSKUs) {
            $this->resourceConnection->getConnection()->delete($tableName, ['sku IN (?)' => $chunkSKUs]);
        }

        $accountsCollection = $this->parentFactory->getObject(AmazonHelper::NICK, 'Account')->getCollection();
        $accountsCollection->addFieldToFilter(
            'other_listings_synchronization',
            Account::OTHER_LISTINGS_SYNCHRONIZATION_YES
        );

        foreach ($accountsCollection->getItems() as $account) {
            $additionalData = (array)$this->getHelper('Data')
                ->jsonDecode($account->getAdditionalData());

            unset($additionalData['is_amazon_other_listings_full_items_data_already_received'],
                $additionalData['last_other_listing_products_synchronization']
            );

            $account->setSettings('additional_data', $additionalData)->save();
        }

        $this->getMessageManager()->addSuccess('Successfully removed.');
        $this->_redirect($this->getHelper('View\ControlPanel')->getPageModuleTabUrl());
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
                ['listing_id',
                    'product_id',
                    new \Zend_Db_Expr('COUNT(product_id) - 1 AS count_of_duplicates'),
                    new \Zend_Db_Expr('MAX(mlp.id) AS save_this_id'),
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
