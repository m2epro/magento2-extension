<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Grid\Column\Renderer;

class Status extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\Options
{
    use \Ess\M2ePro\Block\Adminhtml\Traits\BlockTrait;

    /** @var \Ess\M2ePro\Model\Factory */
    protected $modelFactory;

    /** @var \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory */
    protected $ebayFactory;

    /** @var \Ess\M2ePro\Model\ActiveRecord\Factory */
    protected $activeRecordFactory;

    /** @var \Ess\M2ePro\Helper\View */
    protected $viewHelper;
    /** @var \Ess\M2ePro\Model\ResourceModel\Tag\ListingProduct\Relation\CollectionFactory */
    private $tagRelationCollectionFactory;
    /** @var \Ess\M2ePro\Model\ResourceModel\Tag */
    private $tagResource;

    public function __construct(
        \Ess\M2ePro\Model\ResourceModel\Tag\ListingProduct\Relation\CollectionFactory $tagRelationCollectionFactory,
        \Ess\M2ePro\Model\ResourceModel\Tag $tagResource,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\View $viewHelper,
        \Magento\Backend\Block\Context $context,
        \Ess\M2ePro\Model\Factory $modelFactory,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->modelFactory = $modelFactory;
        $this->ebayFactory = $ebayFactory;
        $this->activeRecordFactory = $activeRecordFactory;
        $this->viewHelper = $viewHelper;
        $this->tagRelationCollectionFactory = $tagRelationCollectionFactory;
        $this->tagResource = $tagResource;
    }

    public function render(\Magento\Framework\DataObject $row): string
    {
        $html = '';
        $listingProductId = (int)$row->getData('listing_product_id');

        if ($this->getColumn()->getData('showLogIcon')) {
            /** @var \Ess\M2ePro\Block\Adminhtml\Grid\Column\Renderer\ViewLogIcon\Listing $viewLogIcon */
            $viewLogIcon = $this->getLayout()->createBlock(
                \Ess\M2ePro\Block\Adminhtml\Grid\Column\Renderer\ViewLogIcon\Listing::class,
                '',
                [
                    'data' => ['jsHandler' => 'EbayListingViewEbayGridObj'],
                ]
            );
            $html = $viewLogIcon->render($row);

            $additionalData = (array)\Ess\M2ePro\Helper\Json::decode($row->getData('additional_data'));
            $synchNote = (isset($additionalData['synch_template_list_rules_note']))
                ? $additionalData['synch_template_list_rules_note']
                : [];
            if (!empty($synchNote)) {
                $synchNote = $this->viewHelper->getModifiedLogMessage($synchNote);

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
        $html .= $this->getCurrentStatus($row);

        if ($row->getData('is_duplicate') && isset($additionalData['item_duplicate_action_required'])) {
            if ($this->getColumn()->getData('showLogIcon')) {
                $duplicateText = __('Duplicate');
                $duplicateContent = <<<HTML
<a href="javascript:" onclick="EbayListingViewEbayGridObj.openItemDuplicatePopUp({$listingProductId});">
    {$duplicateText}
</a>
HTML;
            } else {
                $duplicateText = __('duplicate');
                $duplicateContent = "<span style='color: #ea7601;'>{$duplicateText}</span>";
            }

            $html .= <<<HTML
<div class="icon-warning left">
   {$duplicateContent}
</div>
<br>
HTML;
        }

        $html .= $this->getItemSpecificValidationWarning($row)
            . $this->getScheduledTag($row)
            . $this->getLockedTag($row);

        return $html;
    }

    // ----------------------------------------

    protected function getCurrentStatus($row): string
    {
        $html = '';

        switch ($row->getData('status')) {
            case \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED:
                $html .= '<span style="color: gray;">' . __('Not Listed') . '</span>';
                break;

            case \Ess\M2ePro\Model\Listing\Product::STATUS_LISTED:
                $html .= '<span style="color: green;">' . __('Listed') . '</span>';
                break;

            case \Ess\M2ePro\Model\Listing\Product::STATUS_HIDDEN:
                $html .= '<span style="color: red;">' . __('Listed (Hidden)') . '</span>';
                break;

            case \Ess\M2ePro\Model\Listing\Product::STATUS_SOLD:
                $html .= '<span style="color: brown;">' . __('Inactive (Sold)') . '</span>';

                break;
            case \Ess\M2ePro\Model\Listing\Product::STATUS_STOPPED:
                $html .= '<span style="color: red;">' . __('Inactive (Stopped)') . '</span>';
                break;

            case \Ess\M2ePro\Model\Listing\Product::STATUS_FINISHED:
                $html .= '<span style="color: blue;">' . __('Inactive (Finished)') . '</span>';
                break;

            case \Ess\M2ePro\Model\Listing\Product::STATUS_BLOCKED:
                $html .= '<span style="color: orange;">' . __('Inactive (Blocked)') . '</span>';
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

    public function renderExport(\Magento\Framework\DataObject $row): string
    {
        return strip_tags($this->getCurrentStatus($row));
    }

    private function getItemSpecificValidationWarning(\Magento\Framework\DataObject $row): string
    {
        $collection = $this->tagRelationCollectionFactory->create();
        $collection->join(
            ['tag' => $this->tagResource->getMainTable()],
            'main_table.tag_id = tag.id',
            ['error_code' => 'error_code']
        );
        $collection->addFieldToFilter(
            'error_code',
            ['eq' => \Ess\M2ePro\Model\Ebay\Category\SpecificValidator::ERROR_TAG_CODE]
        );
        $collection->addFieldToFilter(
            'listing_product_id',
            ['eq' => (int)$row->getData('listing_product_id')]
        );

        if ($collection->getSize() === 0) {
            return '';
        }

        $warningMessage = __('Unable to List Product Due to missing Item Specific(s)');

        return sprintf(
            '<span class="fix-magento-tooltip m2e-tooltip-grid-warning" style="float:right;">%s</span>',
            $this->getTooltipHtml($warningMessage)
        );
    }
}
