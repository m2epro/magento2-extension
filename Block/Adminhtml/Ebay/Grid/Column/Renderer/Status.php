<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Grid\Column\Renderer;

use \Ess\M2ePro\Block\Adminhtml\Traits;
use Ess\M2ePro\Model\Listing\Log;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Ebay\Grid\Column\Renderer\Status
 */
class Status extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\Options
{
    use Traits\BlockTrait;

    /** @var \Ess\M2ePro\Model\Factory */
    protected $modelFactory;

    /** @var \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory  */
    protected $ebayFactory;

    /** @var \Ess\M2ePro\Model\ActiveRecord\Factory */
    protected $activeRecordFactory;

    /** @var \Magento\Framework\App\ResourceConnection  */
    protected $resourceConnection;

    /** @var \Ess\M2ePro\Helper\Factory  */
    protected $helperFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Magento\Backend\Block\Context $context,
        \Ess\M2ePro\Model\Factory $modelFactory,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->helperFactory = $helperFactory;
        $this->modelFactory = $modelFactory;
        $this->ebayFactory = $ebayFactory;
        $this->activeRecordFactory = $activeRecordFactory;
        $this->resourceConnection = $resourceConnection;
    }

    //########################################

    public function render(\Magento\Framework\DataObject $row)
    {
        $html = '';
        $listingProductId = (int)$row->getData('listing_product_id');

        if ($this->getColumn()->getData('showLogIcon')) {
            /** @var \Ess\M2ePro\Block\Adminhtml\Grid\Column\Renderer\ViewLogIcon\Listing $viewLogIcon */
            $viewLogIcon = $this->createBlock('Grid_Column_Renderer_ViewLogIcon_Listing', '', [
                'data' => ['jsHandler' => 'EbayListingViewEbayGridObj']
            ]);
            $html = $viewLogIcon->render($row);

            $additionalData = (array)$this->getHelper('Data')->jsonDecode($row->getData('additional_data'));
            $synchNote = (isset($additionalData['synch_template_list_rules_note']))
                                ? $additionalData['synch_template_list_rules_note']
                                : [];
            if (!empty($synchNote)) {
                $synchNote = $this->getHelper('View')->getModifiedLogMessage($synchNote);

                if (empty($html)) {
                    $html = <<<HTML
<span class="fix-magento-tooltip m2e-tooltip-grid-warning" style="float:right;">
    {$this->getTooltipHtml($synchNote, 'map_link_error_icon_' . $row->getId())}
</span>
HTML;
                } else {
                    $html .= <<<HTML
<div id="synch_template_list_rules_note_{$listingProductId}" style="display: none">{$synchNote}</div>
HTML;
                }
            }
        }
        $translator = $this->getHelper('Module\Translation');
        $html .= $this->getCurrentStatus($row);

        if ($row->getData('is_duplicate') && isset($additionalData['item_duplicate_action_required'])) {
            if ($this->getColumn()->getData('showLogIcon')) {
                $duplicateContent = <<<HTML
<a href="javascript:" onclick="EbayListingViewEbayGridObj.openItemDuplicatePopUp({$listingProductId});">
    {$translator->__('Duplicate')}
</a>
HTML;
            } else {
                $duplicateContent = "<span style='color: #ea7601;'>{$translator->__('duplicate')}</span>";
            }

            $html .= <<<HTML
<div class="icon-warning left">
   {$duplicateContent}
</div>
<br>
HTML;
        }

        $html .= $this->getScheduledTag($row) . $this->getLockedTag($row);

        return $html;
    }

    //########################################

    protected function getCurrentStatus($row)
    {
        $html = '';
        $translator = $this->getHelper('Module\Translation');

        switch ($row->getData('status')) {
            case \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED:
                $html .= '<span style="color: gray;">' . $translator->__('Not Listed') . '</span>';
                break;

            case \Ess\M2ePro\Model\Listing\Product::STATUS_LISTED:
                $html .= '<span style="color: green;">' . $translator->__('Listed') . '</span>';
                break;

            case \Ess\M2ePro\Model\Listing\Product::STATUS_HIDDEN:
                $html .= '<span style="color: red;">' . $translator->__('Listed (Hidden)') . '</span>';
                break;

            case \Ess\M2ePro\Model\Listing\Product::STATUS_SOLD:
                $html .= '<span style="color: brown;">' . $translator->__('Inactive (Sold)') . '</span>';

                break;
            case \Ess\M2ePro\Model\Listing\Product::STATUS_STOPPED:
                $html .= '<span style="color: red;">' . $translator->__('Inactive (Stopped)') . '</span>';
                break;

            case \Ess\M2ePro\Model\Listing\Product::STATUS_FINISHED:
                $html .= '<span style="color: blue;">' . $translator->__('Inactive (Finished)') . '</span>';
                break;

            case \Ess\M2ePro\Model\Listing\Product::STATUS_BLOCKED:
                $html .= '<span style="color: orange;">' . $translator->__('Inactive (Blocked)') . '</span>';
                break;

            default:
                break;
        }

        return $html;
    }

    private function getLockedTag($row)
    {
        if ($row instanceof \Ess\M2ePro\Model\Listing\Other) {
            $processingLocks = $row->getProcessingLocks();
        } else {
            $object = $this->ebayFactory->getObjectLoaded('Listing\Product', $row->getData('listing_product_id'));
            $processingLocks = $object->getProcessingLocks();
        }

        $html = '';

        foreach ($processingLocks as $processingLock) {
            switch ($processingLock->getTag()) {
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

                default:
                    break;
            }
        }

        return $html;
    }

    private function getScheduledTag($row)
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
                if (!empty($additionalData['configurator'])) {
                    /** @var \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Configurator $configurator */
                    $configurator = $this->modelFactory->getObject('Ebay_Listing_Product_Action_Configurator');
                    $configurator->setUnserializedData($additionalData['configurator']);

                    if ($configurator->isIncludingMode()) {
                        if ($configurator->isQtyAllowed()) {
                            $reviseParts[] = 'QTY';
                        }

                        if ($configurator->isPriceAllowed()) {
                            $reviseParts[] = 'Price';
                        }

                        if ($configurator->isTitleAllowed()) {
                            $reviseParts[] = 'Title';
                        }

                        if ($configurator->isSubtitleAllowed()) {
                            $reviseParts[] = 'Subtitle';
                        }

                        if ($configurator->isDescriptionAllowed()) {
                            $reviseParts[] = 'Description';
                        }

                        if ($configurator->isImagesAllowed()) {
                            $reviseParts[] = 'Images';
                        }

                        if ($configurator->isCategoriesAllowed()) {
                            $reviseParts[] = 'Categories / Specifics';
                        }

                        if ($configurator->isShippingAllowed()) {
                            $reviseParts[] = 'Shipping';
                        }

                        if ($configurator->isPaymentAllowed()) {
                            $reviseParts[] = 'Payment';
                        }

                        if ($configurator->isReturnAllowed()) {
                            $reviseParts[] = 'Return';
                        }
                    }
                }

                if (!empty($reviseParts)) {
                    $html .= '<br/><span style="color: #605fff">[Revise of ' .
                        implode(', ', $reviseParts) . ' is Scheduled...]</span>';
                } else {
                    $html .= '<br/><span style="color: #605fff">[Revise is Scheduled...]</span>';
                }

                break;

            case \Ess\M2ePro\Model\Listing\Product::ACTION_STOP:
                $html .= '<br/><span style="color: #605fff">[Stop is Scheduled...]</span>';
                break;

            case \Ess\M2ePro\Model\Listing\Product::ACTION_DELETE:
                $html .= '<br/><span style="color: #605fff">[Delete is Scheduled...]</span>';
                break;

            default:
                break;
        }

        return $html;
    }

    //########################################
}
