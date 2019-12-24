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

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\ControlPanel\Module\Integration\Ebay
 */
class Ebay extends Command
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
        $listingOther = $this->parentFactory->getObject(EbayHelper::NICK, 'Listing\Other');
        $ebayListingOther = $this->activeRecordFactory->getObject('Ebay_Listing_Other');

        $stmt = $listingOther->getResourceCollection()->getSelect()->query();

        $itemIds = [];
        foreach ($stmt as $row) {
            $listingOther->setData($row);
            $ebayListingOther->setData($row);

            $listingOther->setChildObject($ebayListingOther);
            $ebayListingOther->setParentObject($listingOther);
            $itemIds[] = $ebayListingOther->getItemId();

            $listingOther->delete();
        }

        $tableName = $this->getHelper('Module_Database_Structure')->getTableNameWithPrefix('m2epro_ebay_item');
        foreach (array_chunk($itemIds, 1000) as $chunkItemIds) {
            $this->resourceConnection->getConnection() ->delete($tableName, ['item_id IN (?)' => $chunkItemIds]);
        }

        foreach ($this->parentFactory->getObject(EbayHelper::NICK, 'Account')->getCollection() as $account) {
            $account->getChildObject()->setData('other_listings_last_synchronization', null)->save();
        }

        $this->getMessageManager()->addSuccess('Successfully removed.');
        $this->_redirect($this->getHelper('View\ControlPanel')->getPageModuleTabUrl());
    }

    /**
     * @title "Stop 3rd Party"
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
     * @title "Reset Images Hashes"
     * @description "Clear Images hashes for listing products"
     * @prompt "Please enter Listing Product ID or `all` code for reset all products."
     * @prompt_var "listing_product_id"
     */
    public function resetEbayImagesHashesAction()
    {
        $listingProductId = $this->getRequest()->getParam('listing_product_id');

        $listingProducts = [];
        if (strtolower($listingProductId) == 'all') {
            $listingProducts = $this->parentFactory->getObject(EbayHelper::NICK, 'Listing\Product')
                                                   ->getCollection();
        } else {
            $listingProduct = $this->parentFactory->getObjectLoaded(
                EbayHelper::NICK,
                'Listing\Product',
                $listingProductId
            );
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

            unset(
                $additionalData['ebay_product_images_hash'],
                $additionalData['ebay_product_variation_images_hash']
            );

            $affected++;
            $listingProduct->setData('additional_data', $this->getHelper('Data')->jsonEncode($additionalData))
                           ->save();
        }

        $this->getMessageManager()->addSuccess("Successfully removed for {$affected} affected Products.");
        return $this->_redirect($this->getHelper('View\ControlPanel')->getPageModuleTabUrl());
    }

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

        $this->getMessageManager()->addSuccess("Successfully set for {$affected} affected Products.");
        return $this->_redirect($this->getHelper('View\ControlPanel')->getPageModuleTabUrl());
    }

    /**
     * @title "Show Nonexistent Templates"
     * @description "Show Nonexistent Templates"
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

                $collection = $this->parentFactory->getObject(EbayHelper::NICK, 'Listing\Product')->getCollection();
                $collection->addFieldToFilter($field, $currentValue);

                foreach ($collection->getItems() as $listingProduct) {
                    $listingProduct->getChildObject()->setData($field, null)->save();
                }
            }

            if ($fixAction == 'set_parent') {
                $field = $currentMode == 'template' ? "template_{$template}_id"
                                                    : "template_{$template}_custom_id";

                $collection = $this->parentFactory->getObject(EbayHelper::NICK, 'Listing\Product')->getCollection();
                $collection->addFieldToFilter($field, $currentValue);

                $data = [
                    "template_{$template}_mode" => \Ess\M2ePro\Model\Ebay\Template\Manager::MODE_PARENT,
                    $field                      => null
                ];

                foreach ($collection->getItems() as $listingProduct) {
                    $listingProduct->getChildObject()->addData($data)->save();
                }
            }

            if ($fixAction == 'set_template' && $this->getRequest()->getParam('template_id')) {
                $field = $currentMode == 'template' ? "template_{$template}_id"
                                                    : "template_{$template}_custom_id";

                $collection = $this->parentFactory->getObject(EbayHelper::NICK, 'Listing\Product')->getCollection();
                $collection->addFieldToFilter($field, $currentValue);

                $data = [
                    "template_{$template}_mode" => \Ess\M2ePro\Model\Ebay\Template\Manager::MODE_TEMPLATE,
                    $field                      => null,
                ];
                $data["template_{$template}_id"] = (int)$this->getRequest()->getParam('template_id');

                foreach ($collection->getItems() as $listing) {
                    $listing->getChildObject()->addData($data)->save();
                }
            }

            $this->_redirect($this->getUrl('*/*/*', ['action' => 'showNonexistentTemplates']));
        }

        $nonexistentTemplates = [];

        $simpleTemplates = ['category', 'other_category'];
        foreach ($simpleTemplates as $templateName) {
            $tempResult = $this->getNonexistentTemplatesBySimpleLogic($templateName);
            !empty($tempResult) && $nonexistentTemplates[$templateName] = $tempResult;
        }

        $difficultTemplates = [
            \Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_SELLING_FORMAT,
            \Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_SYNCHRONIZATION,
            \Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_DESCRIPTION,
            \Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_SHIPPING,
            \Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_PAYMENT,
            \Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_RETURN_POLICY,
        ];
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

        $alreadyRendered = [];
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
                    $url = $this->getUrl('*/*/*', [
                        'action'        => 'showNonexistentTemplates',
                        'fix'           => '1',
                        'template_nick' => $templateName,
                        'current_mode'  => 'template',
                        'fix_action'    => 'set_null',
                        'value'         => $itemInfo['my_needed_id'],
                    ]);

                    $actionsHtml .= <<<HTML
<a href="{$url}">set null</a><br>
HTML;
                }

                if (isset($itemInfo['my_mode']) && $itemInfo['my_mode'] == 0) {
                    $myModeWord = 'parent';
                }

                if (isset($itemInfo['my_mode']) && $itemInfo['my_mode'] == 1) {
                    $myModeWord = 'custom';
                    $url = $this->getUrl('*/*/*', [
                        'action'        => 'showNonexistentTemplates',
                        'fix'           => '1',
                        'template_nick' => $templateName,
                        'current_mode'  => $myModeWord,
                        'fix_action'    => 'set_parent',
                        'value'         => $itemInfo['my_needed_id'],
                    ]);

                    $actionsHtml .= <<<HTML
<a href="{$url}">set parent</a><br>
HTML;
                }

                if (isset($itemInfo['my_mode']) && $itemInfo['my_mode'] == 2) {
                    $myModeWord = 'template';
                    $url = $this->getUrl('*/*/*', [
                        'action'        => 'showNonexistentTemplates',
                        'fix'           => '1',
                        'template_nick' => $templateName,
                        'current_mode'  => $myModeWord,
                        'fix_action'    => 'set_parent',
                        'value'         => $itemInfo['my_needed_id'],
                    ]);

                    $actionsHtml .= <<<HTML
<a href="{$url}">set parent</a><br>
HTML;
                }

                if (isset($itemInfo['parent_mode']) && $itemInfo['parent_mode'] == 1) {
                    $parentModeWord = 'custom';
                    $url = $this->getUrl('*/*/*', [
                        'action'        => 'showNonexistentTemplates',
                        'fix'           => '1',
                        'fix_action'    => 'set_template',
                        'template_nick' => $templateName,
                        'current_mode'  => $parentModeWord,
                        'value'         => $itemInfo['my_needed_id'],
                    ]);
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
                    $url = $this->getUrl('*/*/*', [
                        'action'        => 'showNonexistentTemplates',
                        'fix'           => '1',
                        'fix_action'    => 'set_template',
                        'template_nick' => $templateName,
                        'current_mode'  => $parentModeWord,
                        'value'         => $itemInfo['my_needed_id'],
                    ]);
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
                [
                    'melp' => $this->getHelper('Module_Database_Structure')
                        ->getTableNameWithPrefix('m2epro_ebay_listing_product')
                ],
                [
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
                    )]
            )
            ->joinLeft(
                [
                    'mlp' => $this->getHelper('Module_Database_Structure')
                        ->getTableNameWithPrefix('m2epro_listing_product')
                ],
                'melp.listing_product_id = mlp.id',
                ['listing_id' => 'listing_id']
            )
            ->joinLeft(
                [
                    'mel' => $this->getHelper('Module_Database_Structure')
                        ->getTableNameWithPrefix('m2epro_ebay_listing')
                ],
                'mlp.listing_id = mel.listing_id',
                [
                    'parent_mode'        => "template_{$templateCode}_mode",
                    'parent_template_id' => "template_{$templateCode}_id",
                    'parent_custom_id'   => "template_{$templateCode}_custom_id"
                ]
            );

        $templateIdName = 'id';
        $horizontalTemplates = [
            \Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_SELLING_FORMAT,
            \Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_SYNCHRONIZATION,
            \Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_DESCRIPTION,
        ];
        in_array($templateCode, $horizontalTemplates) && $templateIdName = "template_{$templateCode}_id";

        $result = $this->resourceConnection->getConnection()->select()
            ->from(
                ['subselect' => new \Zend_Db_Expr('('.$subSelect->__toString().')')],
                [
                    'subselect.my_id',
                    'subselect.listing_id',
                    'subselect.my_mode',
                    'subselect.parent_mode',
                    'subselect.my_needed_id',
                ]
            )
            ->joinLeft(
                [
                    'template' => $this->getHelper('Module_Database_Structure')
                        ->getTableNameWithPrefix("m2epro_ebay_template_{$templateCode}")
                ],
                "subselect.my_needed_id = template.{$templateIdName}",
                []
            )
            ->where("template.{$templateIdName} IS NULL")
            ->query()->fetchAll();

        return $result;
    }

    private function getNonexistentTemplatesBySimpleLogic($templateCode)
    {
        $select = $this->resourceConnection->getConnection()->select()
            ->from(
                [
                    'melp' => $this->getHelper('Module_Database_Structure')
                        ->getTableNameWithPrefix('m2epro_ebay_listing_product')
                ],
                [
                    'my_id'        => 'listing_product_id',
                    'my_needed_id' => "template_{$templateCode}_id",
                ]
            )
            ->joinLeft(
                [
                    'mlp' => $this->getHelper('Module_Database_Structure')
                        ->getTableNameWithPrefix('m2epro_listing_product')
                ],
                'melp.listing_product_id = mlp.id',
                ['listing_id' => 'listing_id']
            )
            ->joinLeft(
                [
                    'template' => $this->getHelper('Module_Database_Structure')
                        ->getTableNameWithPrefix("m2epro_ebay_template_{$templateCode}")
                ],
                "melp.template_{$templateCode}_id = template.id",
                []
            )
            ->where("melp.template_{$templateCode}_id IS NOT NULL")
            ->where("template.id IS NULL");

        return $select->query()->fetchAll();
    }

    //########################################

    /**
     * @title "Show Duplicates [parse logs]"
     * @description "Show Duplicates According with Logs"
     */
    public function showEbayDuplicatesByLogsAction()
    {
        $queryObj = $this->resourceConnection->getConnection()
            ->select()
            ->from(
                [
                    'mll' => $this->getHelper('Module_Database_Structure')
                        ->getTableNameWithPrefix('m2epro_listing_log')
                ]
            )
            ->joinLeft(
                ['ml' => $this->getHelper('Module_Database_Structure')->getTableNameWithPrefix('m2epro_listing')],
                'mll.listing_id = ml.id',
                ['marketplace_id']
            )
            ->joinLeft(
                [
                    'mm' => $this->getHelper('Module_Database_Structure')
                        ->getTableNameWithPrefix('m2epro_marketplace')
                ],
                'ml.marketplace_id = mm.id',
                ['marketplace_title' => 'title']
            )
            ->where("mll.description LIKE '%a duplicate of your item%' OR " . // ENG
                "mll.description LIKE '%ette annonce est identique%' OR " . // FR
                "mll.description LIKE '%ngebot ist identisch mit dem%' OR " .  // DE
                "mll.description LIKE '%un duplicato del tuo oggetto%' OR " . // IT
                "mll.description LIKE '%es un duplicado de tu art%'") // ESP

            ->where("mll.component_mode = ?", 'ebay')
            ->order('mll.id DESC')
            ->group(['mll.product_id', 'mll.listing_id'])
            ->query();

        $duplicatesInfo = [];
        while ($row = $queryObj->fetch()) {
            preg_match('/.*\((\d*)\)/', $row['description'], $matches);
            $ebayItemId = !empty($matches[1]) ? $matches[1] : '';

            $duplicatesInfo[] = [
                'date'               => $row['create_date'],
                'listing_id'         => $row['listing_id'],
                'listing_title'      => $row['listing_title'],
                'product_id'         => $row['product_id'],
                'product_title'      => $row['product_title'],
                'listing_product_id' => $row['listing_product_id'],
                'description'        => $row['description'],
                'ebay_item_id'       => $ebayItemId,
                'marketplace_title'  => $row['marketplace_title']
            ];
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
     * @title "Fix many same categories templates"
     * @description "[remove the same templates and set original templates to the settings of listings products]"
     * @new_line
     */
    public function fixManySameCategoriesTemplatesOnEbayAction()
    {
        $affectedListingProducts = $removedTemplates = 0;
        $statistics = [];
        $snapshots = [];

        foreach ($this->activeRecordFactory->getObject('Ebay_Template_Category')->getCollection() as $template) {
            $shot = $template->getDataSnapshot();
            unset($shot['id'], $shot['create_date'], $shot['update_date']);
            foreach ($shot['specifics'] as &$specific) {
                unset($specific['id'], $specific['template_category_id']);
            }
            $key = sha1($this->getHelper('Data')->jsonEncode($shot));

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

            $resolver = $this->modelFactory->getObject('Ebay_Listing_Product_Variation_Resolver', [
                'listingProduct' => $lp
            ]);
            $resolver->setIsAllowedToSave((bool)$this->getRequest()->getParam('allowed_to_save'));
            $resolver->process();

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
