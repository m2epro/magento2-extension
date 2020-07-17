<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Listing\Product\Instruction\SynchronizationTemplate\Checker;

/**
 * Class \Ess\M2ePro\Model\Ebay\Listing\Product\Instruction\SynchronizationTemplate\Checker\NotListed
 */
class NotListed extends AbstractModel
{
    protected $activeRecordFactory;
    protected $parentFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Factory $parentFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        array $data = []
    ) {
        $this->activeRecordFactory = $activeRecordFactory;
        $this->parentFactory = $parentFactory;
        parent::__construct($activeRecordFactory, $helperFactory, $modelFactory, $data);
    }

    //########################################

    public function isAllowed()
    {
        $listingProduct = $this->input->getListingProduct();

        if (!$listingProduct->isListable() || !$listingProduct->isNotListed()) {
            return false;
        }

        return true;
    }

    //########################################

    public function process(array $params = [])
    {
        if (!$this->isMeetListRequirements()) {
            if ($this->input->getScheduledAction() && !$this->input->getScheduledAction()->isForce()) {
                $this->getScheduledActionManager()->deleteAction($this->input->getScheduledAction());
            }

            return;
        }

        if ($this->input->getScheduledAction() && $this->input->getScheduledAction()->isActionTypeList()) {
            return;
        }

        $scheduledAction = $this->input->getScheduledAction();
        if ($scheduledAction === null) {
            $scheduledAction = $this->activeRecordFactory->getObject('Listing_Product_ScheduledAction');
        }

        $scheduledAction->addData(
            [
                'listing_product_id' => $this->input->getListingProduct()->getId(),
                'component' => \Ess\M2ePro\Helper\Component\Ebay::NICK,
                'action_type' => \Ess\M2ePro\Model\Listing\Product::ACTION_LIST,
                'additional_data' => $this->getHelper('Data')->jsonEncode(['params' => $params]),
            ]
        );

        if ($scheduledAction->getId()) {
            $this->getScheduledActionManager()->updateAction($scheduledAction);
        } else {
            $this->getScheduledActionManager()->addAction($scheduledAction);
        }
    }

    //########################################

    public function isMeetListRequirements()
    {
        $listingProduct = $this->input->getListingProduct();

        /** @var \Ess\M2ePro\Model\Ebay\Listing\Product $ebayListingProduct */
        $ebayListingProduct = $listingProduct->getChildObject();

        $ebaySynchronizationTemplate = $ebayListingProduct->getEbaySynchronizationTemplate();

        if (!$ebaySynchronizationTemplate->isListMode()) {
            return false;
        }

        $additionalData = $listingProduct->getAdditionalData();

        if (!$ebayListingProduct->isSetCategoryTemplate()) {
            return false;
        }

        $variationResource = $this->parentFactory->getObject(
            \Ess\M2ePro\Helper\Component\Ebay::NICK,
            'Listing_Product_Variation'
        )->getResource();

        if ($ebaySynchronizationTemplate->isListStatusEnabled()) {
            if (!$listingProduct->getMagentoProduct()->isStatusEnabled()) {
                $note = $this->getHelper('Module\Log')->encodeDescription(
                    'Product was not Listed as it has Disabled Status in Magento. The Product Status condition
                     in the List Rules was not met.'
                );
                $additionalData['synch_template_list_rules_note'] = $note;

                $listingProduct->setSettings('additional_data', $additionalData)->save();

                return false;
            } else {
                if ($ebayListingProduct->isVariationsReady()) {
                    $temp = $variationResource->isAllStatusesDisabled(
                        $listingProduct->getId(),
                        $listingProduct->getListing()->getStoreId()
                    );

                    if ($temp !== null && $temp) {
                        $note = $this->getHelper('Module\Log')->encodeDescription(
                            'Product was not Listed as this Product Variation has Disabled Status in Magento.
                             The Product Status condition in the List Rules was not met.'
                        );
                        $additionalData['synch_template_list_rules_note'] = $note;

                        $listingProduct->setSettings('additional_data', $additionalData)->save();

                        return false;
                    }
                }
            }
        }

        if ($ebaySynchronizationTemplate->isListIsInStock()) {
            if (!$listingProduct->getMagentoProduct()->isStockAvailability()) {
                $note = $this->getHelper('Module\Log')->encodeDescription(
                    'Product was not Listed as it is Out of Stock in Magento. The Stock Availability condition in
                     the List Rules was not met.'
                );
                $additionalData['synch_template_list_rules_note'] = $note;

                $listingProduct->setSettings('additional_data', $additionalData)->save();

                return false;
            } else {
                if ($ebayListingProduct->isVariationsReady()) {
                    $temp = $variationResource->isAllDoNotHaveStockAvailabilities(
                        $listingProduct->getId(),
                        $listingProduct->getListing()->getStoreId()
                    );

                    if ($temp !== null && $temp) {
                        $note = $this->getHelper('Module\Log')->encodeDescription(
                            'Product was not Listed as this Product Variation is Out of Stock in Magento. The Stock
                             Availability condition in the List Rules was not met.'
                        );
                        $additionalData['synch_template_list_rules_note'] = $note;

                        $listingProduct->setSettings('additional_data', $additionalData)->save();

                        return false;
                    }
                }
            }
        }

        if ($ebaySynchronizationTemplate->isListWhenQtyCalculatedHasValue()) {
            $result = false;
            $productQty = (int)$ebayListingProduct->getQty();
            $minQty = (int)$ebaySynchronizationTemplate->getListWhenQtyCalculatedHasValue();

            $note = '';

            if ($productQty >= $minQty) {
                $result = true;
            } else {
                $note = $this->getHelper('Module\Log')->encodeDescription(
                    'Product was not Listed as its Quantity is %product_qty% in Magento. The Calculated
                     Quantity condition in the List Rules was not met.',
                    ['!product_qty' => $productQty]
                );
            }

            if (!$result) {
                if (!empty($note)) {
                    $additionalData['synch_template_list_rules_note'] = $note;
                    $listingProduct->setSettings('additional_data', $additionalData)->save();
                }

                return false;
            }
        }

        if ($ebaySynchronizationTemplate->isListAdvancedRulesEnabled()) {
            /** @var \Ess\M2ePro\Model\Magento\Product\Rule $ruleModel */
            $ruleModel = $this->activeRecordFactory->getObject('Magento_Product_Rule')->setData(
                [
                    'store_id' => $listingProduct->getListing()->getStoreId(),
                    'prefix' => \Ess\M2ePro\Model\Ebay\Template\Synchronization::LIST_ADVANCED_RULES_PREFIX
                ]
            );
            $ruleModel->loadFromSerialized($ebaySynchronizationTemplate->getListAdvancedRulesFilters());

            if (!$ruleModel->validate($listingProduct->getMagentoProduct()->getProduct())) {
                $note = $this->getHelper('Module\Log')->encodeDescription(
                    'Product was not Listed. Advanced Conditions in the List Rules were not met.'
                );

                $additionalData['synch_template_list_rules_note'] = $note;
                $listingProduct->setSettings('additional_data', $additionalData)->save();

                return false;
            }
        }

        return true;
    }

    //########################################
}
