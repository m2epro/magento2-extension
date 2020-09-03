<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace  Ess\M2ePro\Block\Adminhtml\Walmart\Grid\Column\Renderer;

use Ess\M2ePro\Block\Adminhtml\Traits;
use Ess\M2ePro\Model\Listing\Log;

/**
 * Class  \Ess\M2ePro\Block\Adminhtml\Walmart\Grid\Column\Renderer\Status
 */
class Status extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\Options
{
    use Traits\BlockTrait;

    /** @var \Ess\M2ePro\Helper\Factory  */
    protected $helperFactory;

    /** @var \Ess\M2ePro\Model\ActiveRecord\Factory */
    protected $activeRecordFactory;

    /** @var \Ess\M2ePro\Model\Factory */
    protected $modelFactory;

    /** @var \Magento\Framework\App\ResourceConnection  */
    protected $resourceConnection;

    /** @var \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory  */
    protected $walmartFactory;

    protected $lockedDataCache = [];

    protected $parentAndChildReviseScheduledCache = [];

    //########################################

    public function __construct(
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Magento\Backend\Block\Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->helperFactory = $helperFactory;
        $this->walmartFactory = $walmartFactory;
        $this->activeRecordFactory = $activeRecordFactory;
        $this->modelFactory = $modelFactory;
        $this->resourceConnection = $resourceConnection;
    }

    //########################################

    public function render(\Magento\Framework\DataObject $row)
    {
        $listingProductId  = (int)$row->getData('id');
        $additionalData    = (array)$this->getHelper('Data')->jsonDecode($row->getData('additional_data'));
        $isVariationParent = (bool)(int)$row->getData('is_variation_parent');
        $isVariationGrid   = false;

        if ($this->getColumn() !== null && $this->getColumn()->getData('is_variation_grid') !== null) {
            $isVariationGrid = $this->getColumn()->getData('is_variation_grid');
        }

        $data['is_variation_parent'] = $isVariationParent;
        if ($isVariationGrid) {
            $data['jsHandler'] = 'ListingProductVariationManageVariationsGridObj';
        }

        /** @var \Ess\M2ePro\Block\Adminhtml\Walmart\Grid\Column\Renderer\ViewLogIcon\Listing $viewLogIcon */
        $viewLogIcon = $this->createBlock('Walmart_Grid_Column_Renderer_ViewLogIcon_Listing', '', ['data' => $data]);
        $html = $viewLogIcon->render($row);

        if (!empty($additionalData['synch_template_list_rules_note'])) {
            $synchNote = $this->getHelper('View')->getModifiedLogMessage(
                $additionalData['synch_template_list_rules_note']
            );

            if (empty($html)) {
                $html = <<<HTML
<span class="fix-magento-tooltip m2e-tooltip-grid-warning" style="float:right;">
    {$this->getTooltipHtml($synchNote, 'map_link_error_icon_'.$row->getId())}
</span>
HTML;
            } else {
                $html .= <<<HTML
<div id="synch_template_list_rules_note_{$listingProductId}" style="display: none">{$synchNote}</div>
HTML;
            }
        }

        $resetHtml = '';
        if ($row->getData('status') == \Ess\M2ePro\Model\Listing\Product::STATUS_BLOCKED &&
            !$row->getData('is_online_price_invalid')
        ) {
            $resetHtml = <<<HTML
<br/>
<span style="color: gray">[Can be fixed]</span>
HTML;
        }

        if (!$isVariationParent) {
            /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */
            $listingProduct = $this->walmartFactory
                ->getObjectLoaded('Listing\Product', $listingProductId);

            $statusChangeReasons = $listingProduct->getChildObject()->getStatusChangeReasons();

            return $html
                . $this->getProductStatus($row->getData('status'), $statusChangeReasons)
                . $resetHtml
                . $this->getScheduledTag($row)
                . $this->getLockedTag($row);
        } else {
            $statusNotListed = \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED;
            $statusListed    = \Ess\M2ePro\Model\Listing\Product::STATUS_LISTED;
            $statusStopped   = \Ess\M2ePro\Model\Listing\Product::STATUS_STOPPED;
            $statusBlocked   = \Ess\M2ePro\Model\Listing\Product::STATUS_BLOCKED;

            $variationChildStatuses = $row->getData('variation_child_statuses');
            if (empty($variationChildStatuses)) {
                return $html
                    . $this->getProductStatus($statusNotListed)
                    . $this->getScheduledTag($row)
                    . $this->getLockedTag($row);
            }

            $variationChildStatuses = $this->getHelper('Data')->jsonDecode($variationChildStatuses);

            $sortedStatuses = [];

            if (isset($variationChildStatuses[$statusNotListed])) {
                $sortedStatuses[$statusNotListed] = $variationChildStatuses[$statusNotListed];
            }

            if (isset($variationChildStatuses[$statusListed])) {
                $sortedStatuses[$statusListed] = $variationChildStatuses[$statusListed];
            }

            if (isset($variationChildStatuses[$statusStopped])) {
                $sortedStatuses[$statusStopped] = $variationChildStatuses[$statusStopped];
            }

            if (isset($variationChildStatuses[$statusBlocked])) {
                $sortedStatuses[$statusBlocked] = $variationChildStatuses[$statusBlocked];
            }

            $linkTitle = $this->getHelper('Module\Translation')->__('Show all Child Products with such Status');

            foreach ($sortedStatuses as $status => $productsCount) {
                if (empty($productsCount)) {
                    continue;
                }

                $filter = base64_encode('status=' . $status);

                $productTitle = $this->getHelper('Data')->escapeHtml($row->getData('name'));
                $vpmt = $this->getHelper('Module\Translation')->__(
                    'Manage Variations of &quot;%s%&quot; ',
                    $productTitle
                );
                $vpmt = addslashes($vpmt);

                $generalId = $row->getData('general_id');
                if (!empty($generalId)) {
                    $vpmt .= '(' . $generalId . ')';
                }

                $productsCount = <<<HTML
<a onclick="ListingGridObj.variationProductManageHandler.openPopUp({$listingProductId}, '{$vpmt}', '{$filter}')"
   class="hover-underline"
   title="{$linkTitle}"
   href="javascript:void(0)">[{$productsCount}]</a>
HTML;

                $html .= $this->getProductStatus($status) . '&nbsp;' . $productsCount . '<br/>';
            }

            $html .= $this->getScheduledTag($row) . $this->getLockedTag($row);
        }

        return $html;
    }

    protected function getProductStatus($status, $statusChangeReasons = [])
    {
        $translator = $this->getHelper('Module\Translation');
        $html = '';
        switch ($status) {
            case \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED:
                $html = '<span style="color: gray;">' . $translator->__('Not Listed') . '</span>';
                break;
            case \Ess\M2ePro\Model\Listing\Product::STATUS_LISTED:
                $html = '<span style="color: green;">' . $translator->__('Active') . '</span>';
                break;
            case \Ess\M2ePro\Model\Listing\Product::STATUS_STOPPED:
                $html ='<span style="color: red;">' . $translator->__('Inactive') . '</span>';
                break;
            case \Ess\M2ePro\Model\Listing\Product::STATUS_BLOCKED:
                $html ='<span style="color: orange; font-weight: bold;">' .
                    $translator->__('Inactive (Blocked)') . '</span>';
                break;
        }

        return $html .
            $this->getStatusChangeReasons($statusChangeReasons);
    }

    protected function getStatusChangeReasons($statusChangeReasons)
    {
        if (empty($statusChangeReasons)) {
            return '';
        }

        $html = '<li style="margin-bottom: 5px;">'
            . implode('</li><li style="margin-bottom: 5px;">', $statusChangeReasons)
            . '</li>';

        return <<<HTML
        <span class="fix-magento-tooltip">
            {$this->getTooltipHtml($html)}
        </span>
HTML;
    }

    protected function getScheduledTag($row)
    {
        $html = '';

        /**
         * @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\ScheduledAction\Collection $scheduledActionsCollection
         */
        $scheduledActionsCollection = $this->activeRecordFactory->getObject('Listing_Product_ScheduledAction')
            ->getCollection();
        $scheduledActionsCollection->addFieldToFilter('listing_product_id', $row->getData('id'));

        /** @var \Ess\M2ePro\Model\Listing\Product\ScheduledAction $scheduledAction */
        $scheduledAction = $scheduledActionsCollection->getFirstItem();
        if (!$scheduledAction->getId()) {
            return $html;
        }

        switch ($scheduledAction->getActionType()) {
            case \Ess\M2ePro\Model\Listing\Product::ACTION_LIST:
                $html .= '<br/><span style="color: #605fff">[List is Scheduled...]</span>';
                break;

            case \Ess\M2ePro\Model\Listing\Product::ACTION_RELIST:
                $html .= '<br/><span style="color: #605fff">[Relist is Scheduled...]</span>';
                break;

            case \Ess\M2ePro\Model\Listing\Product::ACTION_REVISE:
                $reviseParts = [];

                $additionalData = $scheduledAction->getAdditionalData();
                if (!empty($additionalData['configurator']) &&
                    !isset($this->parentAndChildReviseScheduledCache[$row->getData('id')])) {
                    /** @var \Ess\M2ePro\Model\Walmart\Listing\Product\Action\Configurator $configurator */
                    $configurator = $this->modelFactory->getObject('Walmart_Listing_Product_Action_Configurator');
                    $configurator->setUnserializedData($additionalData['configurator']);

                    if ($configurator->isIncludingMode()) {
                        if ($configurator->isQtyAllowed()) {
                            $reviseParts[] = 'QTY';
                        }

                        if ($configurator->isPriceAllowed()) {
                            $reviseParts[] = 'Price';
                        }

                        if ($configurator->isPromotionsAllowed()) {
                            $reviseParts[] = 'Promotions';
                        }

                        if ($configurator->isDetailsAllowed()) {
                            $params = $additionalData['params'];

                            if (isset($params['changed_sku'])) {
                                $reviseParts[] = 'SKU';
                            }

                            if (isset($params['changed_identifier'])) {
                                $reviseParts[] = strtoupper($params['changed_identifier']['type']);
                            }

                            $reviseParts[] = 'Details';
                        }
                    }
                }

                if (!empty($reviseParts)) {
                    $html .= '<br/><span style="color: #605fff">[Revise of '.
                        implode(', ', $reviseParts).' is Scheduled...]</span>';
                } else {
                    $html .= '<br/><span style="color: #605fff">[Revise is Scheduled...]</span>';
                }
                break;

            case \Ess\M2ePro\Model\Listing\Product::ACTION_STOP:
                $html .= '<br/><span style="color: #605fff">[Stop is Scheduled...]</span>';
                break;

            case \Ess\M2ePro\Model\Listing\Product::ACTION_DELETE:
                $html .= '<br/><span style="color: #605fff">[Retire is Scheduled...]</span>';
                break;

            default:
                break;
        }

        return $html;
    }

    protected function getLockedTag($row)
    {
        $html = '';

        $tempLocks = $this->getLockedData($row);
        $childCount = 0;

        foreach ($tempLocks['object_locks'] as $lock) {
            switch ($lock->getTag()) {
                case 'list_action':
                    $html .= '<br/><span style="color: #605fff">[List in Progress...]</span>';
                    break;

                case 'relist_action':
                    $html .= '<br/><span style="color: #605fff">[Relist in Progress...]</span>';
                    break;

                case 'revise_action':
                    $html .= '<br/><span style="color: #605fff">[Revise in Progress...]</span>';
                    break;

                case 'stop_action':
                    $html .= '<br/><span style="color: #605fff">[Stop in Progress...]</span>';
                    break;

                case 'stop_and_remove_action':
                    $html .= '<br/><span style="color: #605fff">[Stop And Remove in Progress...]</span>';
                    break;

                case 'delete_and_remove_action':
                    $html .= '<br/><span style="color: #605fff">[Remove in Progress...]</span>';
                    break;

                case 'child_products_in_action':
                    $childCount++;
                    break;

                default:
                    break;
            }
        }

        if ($childCount > 0) {
            $html .= '<br/><span style="color: #605fff">[Child(s) in Action...]</span>';
        }

        return $html;
    }

    protected function getLockedData($row)
    {
        $listingProductId = $row->getData('id');
        if (!isset($this->lockedDataCache[$listingProductId])) {
            $objectLocks = $this->activeRecordFactory->getObjectLoaded('Listing\Product', $listingProductId)
                ->getProcessingLocks();
            $tempArray = [
                'object_locks' => $objectLocks,
                'in_action' => !empty($objectLocks),
            ];
            $this->lockedDataCache[$listingProductId] = $tempArray;
        }

        return $this->lockedDataCache[$listingProductId];
    }

    //########################################

    public function getTooltipHtml($content, $id = '', $classes = [])
    {
        $classes = implode(' ', $classes);

        return <<<HTML
    <div id="{$id}" class="m2epro-field-tooltip admin__field-tooltip {$classes}">
        <a class="admin__field-tooltip-action" href="javascript://"></a>
        <div class="admin__field-tooltip-content" style="">
            {$content}
        </div>
    </div>
HTML;
    }

    //########################################

    public function setParentAndChildReviseScheduledCache(array $data)
    {
        $this->parentAndChildReviseScheduledCache = $data;
    }

    //########################################
}
