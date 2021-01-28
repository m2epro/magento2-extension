<?php

namespace Ess\M2ePro\Model\ControlPanel\Inspection\Inspector;

use Ess\M2ePro\Helper\Component\Ebay as EbayHelper;
use Ess\M2ePro\Model\ControlPanel\Inspection\AbstractInspection;
use Ess\M2ePro\Model\ControlPanel\Inspection\FixerInterface;
use Ess\M2ePro\Model\ControlPanel\Inspection\InspectorInterface;
use Ess\M2ePro\Model\ControlPanel\Inspection\Manager;

class NonexistentTemplates extends AbstractInspection implements InspectorInterface, FixerInterface
{
    const FIX_ACTION_SET_NULL     = 'set_null';
    const FIX_ACTION_SET_PARENT   = 'set_parent';
    const FIX_ACTION_SET_TEMPLATE = 'set_template';

    /**@var array */
    protected $_simpleTemplates = [
        'template_category_id' => 'category',
        'template_category_secondary_id' => 'category',
        'template_store_category_id' => 'store_category',
        'template_store_category_secondary_id' => 'store_category'
    ];

    /** @var array */
    protected $_difficultTemplates = [
        \Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_SELLING_FORMAT,
        \Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_SYNCHRONIZATION,
        \Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_DESCRIPTION,
        \Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_SHIPPING,
        \Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_PAYMENT,
        \Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_RETURN_POLICY,
    ];

    //########################################

    public function getTitle()
    {
        return 'Nonexistent template';
    }

    public function getGroup()
    {
        return Manager::GROUP_PRODUCTS;
    }

    public function getExecutionSpeed()
    {
        return Manager::EXECUTION_SPEED_FAST;
    }

    //########################################

    public function process()
    {
        $nonexistentTemplates = [];
        $issues = [];

        foreach ($this->_simpleTemplates as $templateIdField => $templateName) {
            $tempResult = $this->getNonexistentTemplatesBySimpleLogic($templateName, $templateIdField);
            !empty($tempResult) && $nonexistentTemplates[$templateName] = $tempResult;
        }

        foreach ($this->_difficultTemplates as $templateName) {
            $tempResult = $this->getNonexistentTemplatesByDifficultLogic($templateName);
            !empty($tempResult) && $nonexistentTemplates[$templateName] = $tempResult;
        }

        if (!empty($nonexistentTemplates)) {
            $issues[] = $this->resultFactory->createError(
                $this,
                'Has nonexistent templates',
                $this->renderMetadata($nonexistentTemplates)
            );
        }

        return $issues;
    }

    protected function renderMetadata($data)
    {
        $tableContent = <<<HTML
<tr>
    <th>Listing ID</th>
    <th>Listing Product ID</th>
    <th>Policy ID</th>
    <th>Policy ID Field</th>
    <th>My Mode</th>
    <th>Parent Mode</th>
    <th>Actions</th>
</tr>
HTML;

        $alreadyRendered = [];
        foreach ($data as $templateName => $items) {
            $tableContent .= <<<HTML
<tr>
    <td colspan="15" align="center">{$templateName}</td>
</tr>
HTML;

            foreach ($items as $index => $itemInfo) {
                $myModeWord = '--';
                $parentModeWord = '--';
                $actionsHtml = '';
                $params = [
                    'template'    => $templateName,
                    'field_value' => $itemInfo['my_needed_id'],
                    'field'       => $itemInfo['my_needed_id_field'],
                    'action'      => 'repairNonexistentTemplates'
                ];

                if (!isset($itemInfo['my_mode']) && !isset($itemInfo['parent_mode'])) {
                    $params['action'] = self::FIX_ACTION_SET_NULL;
                    $url = $this->urlBuilder->getUrl(
                        'm2epro/controlPanel_module_integration/ebay',
                        $params
                    );

                    $actionsHtml .= <<<HTML
<a href="{$url}">set null</a><br>
HTML;
                }

                if (isset($itemInfo['my_mode']) && $itemInfo['my_mode'] == 0) {
                    $myModeWord = 'parent';
                }

                if (isset($itemInfo['my_mode']) && $itemInfo['my_mode'] == 1) {
                    $myModeWord = 'custom';
                    $params['action'] = self::FIX_ACTION_SET_PARENT;
                    $url = $this->urlBuilder->getUrl(
                        'm2epro/controlPanel_module_integration_ebay/repairNonexistentTemplates',
                        $params
                    );

                    $actionsHtml .= <<<HTML
<a href="{$url}">set parent</a><br>
HTML;
                }

                if (isset($itemInfo['my_mode']) && $itemInfo['my_mode'] == 2) {
                    $myModeWord = 'template';
                    $params['action'] = self::FIX_ACTION_SET_PARENT;
                    $url = $this->urlBuilder->getUrl(
                        'm2epro/controlPanel_module_integration_ebay/repairNonexistentTemplates',
                        $params
                    );

                    $actionsHtml .= <<<HTML
<a href="{$url}">set parent</a><br>
HTML;
                }

                if (isset($itemInfo['parent_mode']) && $itemInfo['parent_mode'] == 1) {
                    $parentModeWord = 'custom';
                    $params['action'] = self::FIX_ACTION_SET_TEMPLATE;
                    $url = $this->urlBuilder->getUrl(
                        'm2epro/controlPanel_module_integration_ebay/repairNonexistentTemplates',
                        $params
                    );
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
                    $params['action'] = self::FIX_ACTION_SET_TEMPLATE;
                    $url = $this->urlBuilder->getUrl(
                        'm2epro/controlPanel_module_integration_ebay/repairNonexistentTemplates',
                        $params
                    );
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

                $key = $templateName . '##' . $myModeWord . '##' . $itemInfo['listing_id'];
                if ($myModeWord === 'parent' && in_array($key, $alreadyRendered)) {
                    continue;
                }

                $alreadyRendered[] = $key;
                $tableContent .= <<<HTML
<tr>
    <td>{$itemInfo['listing_id']}</td>
    <td>{$itemInfo['my_id']}</td>
    <td>{$itemInfo['my_needed_id']}</td>
    <td>{$itemInfo['my_needed_id_field']}</td>
    <td>{$myModeWord}</td>
    <td>{$parentModeWord}</td>
    <td>
        {$actionsHtml}
    </td>
</tr>
HTML;
            }
        }

        $html = <<<HTML
        <table width="100%">
            {$tableContent}
        </table>
HTML;
        return $html;
    }

    //########################################

    private function getNonexistentTemplatesByDifficultLogic($templateCode)
    {
        $databaseHelper = $this->helperFactory->getObject('Module_Database_Structure');

        $subSelect = $this->resourceConnection->getConnection()->select()
            ->from(
                [
                    'melp' => $databaseHelper->getTableNameWithPrefix('m2epro_ebay_listing_product')
                ],
                [
                    'my_id' => 'listing_product_id',
                    'my_mode' => "template_{$templateCode}_mode",
                    'my_template_id' => "template_{$templateCode}_id",

                    'my_needed_id' => new \Zend_Db_Expr(
                        "CASE
                        WHEN melp.template_{$templateCode}_mode = 2 THEN melp.template_{$templateCode}_id
                        WHEN melp.template_{$templateCode}_mode = 1 THEN melp.template_{$templateCode}_id
                        WHEN melp.template_{$templateCode}_mode = 0 THEN mel.template_{$templateCode}_id
                        END"
                    ),
                    'my_needed_id_field' => "template_{$templateCode}_id"
                ]
            )
            ->joinLeft(
                [
                    'mlp' => $databaseHelper->getTableNameWithPrefix('m2epro_listing_product')
                ],
                'melp.listing_product_id = mlp.id',
                ['listing_id' => 'listing_id']
            )
            ->joinLeft(
                [
                    'mel' => $databaseHelper->getTableNameWithPrefix('m2epro_ebay_listing')
                ],
                'mlp.listing_id = mel.listing_id',
                [
                    'parent_template_id' => "template_{$templateCode}_id"
                ]
            );

        $templateIdName = 'id';

        /** @var \Ess\M2ePro\Model\Ebay\Template\Manager $templateManager */
        $templateManager = $this->modelFactory->getObject('Ebay_Template_Manager');
        if (in_array($templateCode, $templateManager->getHorizontalTemplates())) {
            $templateIdName = "template_{$templateCode}_id";
        }

        $result = $this->resourceConnection->getConnection()->select()
            ->from(
                [
                    'subselect' => new \Zend_Db_Expr(
                        '(' . $subSelect->__toString() . ')'
                    )
                ],
                [
                    'subselect.my_id',
                    'subselect.listing_id',
                    'subselect.my_mode',
                    'subselect.my_needed_id',
                    'subselect.my_needed_id_field'
                ]
            )
            ->joinLeft(
                [
                    'template' => $databaseHelper->getTableNameWithPrefix("m2epro_ebay_template_{$templateCode}")
                ],
                "subselect.my_needed_id = template.{$templateIdName}",
                []
            )
            ->where("template.{$templateIdName} IS NULL")
            ->query()
            ->fetchAll();

        return $result;
    }

    private function getNonexistentTemplatesBySimpleLogic($templateCode, $templateIdField)
    {
        $databaseHelper = $this->helperFactory->getObject('Module_Database_Structure');

        $select = $this->resourceConnection->getConnection()->select()
            ->from(
                [
                    'melp' => $databaseHelper->getTableNameWithPrefix('m2epro_ebay_listing_product')
                ],
                [
                    'my_id' => 'listing_product_id',
                    'my_needed_id' => $templateIdField,
                    'my_needed_id_field' => new \Zend_Db_Expr("'{$templateIdField}'")
                ]
            )
            ->joinLeft(
                [
                    'mlp' => $databaseHelper->getTableNameWithPrefix('m2epro_listing_product')
                ],
                'melp.listing_product_id = mlp.id',
                ['listing_id' => 'listing_id']
            )
            ->joinLeft(
                [
                    'template' => $databaseHelper->getTableNameWithPrefix("m2epro_ebay_template_{$templateCode}")
                ],
                "melp.{$templateIdField} = template.id",
                []
            )
            ->where("melp.{$templateIdField} IS NOT NULL")
            ->where("template.id IS NULL");

        return $select->query()->fetchAll();
    }

    //########################################

    public function fix($data)
    {
        if ($data['action'] === self::FIX_ACTION_SET_NULL) {
            $collection = $this->parentFactory->getObject(EbayHelper::NICK, 'Listing\Product')->getCollection();
            $collection->addFieldToFilter($data['field'], $data['field_value']);

            foreach ($collection->getItems() as $listingProduct) {
                $listingProduct->getChildObject()->setData($data['field'], null);
                $listingProduct->getChildObject()->save();
            }
        }

        if ($data['action'] === self::FIX_ACTION_SET_PARENT) {
            $collection = $this->parentFactory->getObject(EbayHelper::NICK, 'Listing\Product')->getCollection();
            $collection->addFieldToFilter($data['field'], $data['field_value']);

            foreach ($collection->getItems() as $listingProduct) {
                $listingProduct->getChildObject()->setData(
                    "template_{$data['template']}_mode",
                    \Ess\M2ePro\Model\Ebay\Template\Manager::MODE_PARENT
                );

                $listingProduct->getChildObject()->setData($data['field'], null);
                $listingProduct->getChildObject()->save();
            }
        }

        if ($data['action'] === self::FIX_ACTION_SET_TEMPLATE &&
            $data['template_id']) {

            $collection = $this->parentFactory->getObject(EbayHelper::NICK, 'Listing\Product')->getCollection();
            $collection->addFieldToFilter($data['field'], $data['field_value']);

            foreach ($collection->getItems() as $listing) {
                $listing->getChildObject()->setData(
                    "template_{$data['template']}_mode",
                    \Ess\M2ePro\Model\Ebay\Template\Manager::MODE_TEMPLATE
                );
                $listingProduct->getChildObject()->setData($data['field'], null);
                $listingProduct->getChildObject()->setData(
                    "template_{$data['template']}_id",
                    (int)$data['template_id']
                );
            }
        }
    }

    //########################################
}
