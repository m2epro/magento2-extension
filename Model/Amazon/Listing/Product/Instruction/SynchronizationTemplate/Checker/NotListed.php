<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Listing\Product\Instruction\SynchronizationTemplate\Checker;

/**
 * Class \Ess\M2ePro\Model\Amazon\Listing\Product\Instruction\SynchronizationTemplate\Checker\NotListed
 */
class NotListed extends AbstractModel
{
    //########################################

    public function isAllowed()
    {
        $listingProduct = $this->input->getListingProduct();

        if (!$listingProduct->isListable() || !$listingProduct->isNotListed()) {
            return false;
        }

        /** @var \Ess\M2ePro\Model\Amazon\Listing\Product $amazonListingProduct */
        $amazonListingProduct = $listingProduct->getChildObject();
        $variationManager = $amazonListingProduct->getVariationManager();

        $searchGeneralId   = $amazonListingProduct->getListingSource()->getSearchGeneralId();
        $searchWorldwideId = $amazonListingProduct->getListingSource()->getSearchWorldwideId();

        if ($variationManager->isVariationProduct()) {
            if ($variationManager->isPhysicalUnit() &&
                !$variationManager->getTypeModel()->isVariationProductMatched()
            ) {
                return false;
            }

            if ($variationManager->isRelationParentType() && $amazonListingProduct->getGeneralId()) {
                return false;
            }

            if ($variationManager->isRelationChildType()) {
                if (!$amazonListingProduct->getGeneralId() && !$amazonListingProduct->isGeneralIdOwner()) {
                    return false;
                }
            }

            if ($variationManager->isIndividualType()) {
                if (!$amazonListingProduct->getGeneralId() &&
                    ($listingProduct->getMagentoProduct()->isSimpleTypeWithCustomOptions() ||
                        $listingProduct->getMagentoProduct()->isBundleType() ||
                        $listingProduct->getMagentoProduct()->isDownloadableTypeWithSeparatedLinks())
                ) {
                    return false;
                }
            }

            if ($variationManager->isRelationParentType() &&
                empty($searchGeneralId) &&
                !$amazonListingProduct->isGeneralIdOwner()
            ) {
                return false;
            }
        }

        if (!$amazonListingProduct->getGeneralId() && !$amazonListingProduct->isGeneralIdOwner() &&
            empty($searchGeneralId) && empty($searchWorldwideId)
        ) {
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
                'component'          => \Ess\M2ePro\Helper\Component\Amazon::NICK,
                'action_type'        => \Ess\M2ePro\Model\Listing\Product::ACTION_LIST,
                'additional_data'    => $this->getHelper('Data')->jsonEncode(['params' => $params]),
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

        /** @var \Ess\M2ePro\Model\Amazon\Listing\Product $amazonListingProduct */
        $amazonListingProduct = $listingProduct->getChildObject();
        $variationManager = $amazonListingProduct->getVariationManager();

        $amazonSynchronizationTemplate = $amazonListingProduct->getAmazonSynchronizationTemplate();

        if (!$amazonSynchronizationTemplate->isListMode()) {
            return false;
        }

        /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\Variation $variationResource */
        $variationResource = $this->activeRecordFactory->getObject('Listing_Product_Variation')->getResource();

        $additionalData = $listingProduct->getAdditionalData();

        if ($amazonSynchronizationTemplate->isListStatusEnabled()) {
            if (!$listingProduct->getMagentoProduct()->isStatusEnabled()) {
                $note = $this->getHelper('Module\Log')->encodeDescription(
                    'Product was not Listed as it has Disabled Status in Magento. The Product Status condition
                     in the List Rules was not met.'
                );
                $additionalData['synch_template_list_rules_note'] = $note;

                $listingProduct->setSettings('additional_data', $additionalData)->save();

                return false;
            } elseif ($variationManager->isPhysicalUnit() &&
                $variationManager->getTypeModel()->isVariationProductMatched()
            ) {
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

        if ($amazonSynchronizationTemplate->isListIsInStock()) {
            if (!$listingProduct->getMagentoProduct()->isStockAvailability()) {
                $note = $this->getHelper('Module\Log')->encodeDescription(
                    'Product was not Listed as it is Out of Stock in Magento. The Stock Availability condition in
                     the List Rules was not met.'
                );
                $additionalData['synch_template_list_rules_note'] = $note;

                $listingProduct->setSettings('additional_data', $additionalData)->save();

                return false;
            } elseif ($variationManager->isPhysicalUnit() &&
                $variationManager->getTypeModel()->isVariationProductMatched()
            ) {
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

        if ($amazonSynchronizationTemplate->isListWhenQtyCalculatedHasValue() &&
            !$variationManager->isRelationParentType()
        ) {
            $result = false;
            $productQty = (int)$amazonListingProduct->getQty(false);
            $minQty  = (int)$amazonSynchronizationTemplate->getListWhenQtyCalculatedHasValue();

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

        if ($amazonSynchronizationTemplate->isListAdvancedRulesEnabled()) {
            /** @var \Ess\M2ePro\Model\Magento\Product\Rule $ruleModel */
            $ruleModel = $this->activeRecordFactory->getObject('Magento_Product_Rule')->setData(
                [
                    'store_id' => $listingProduct->getListing()->getStoreId(),
                    'prefix'   => \Ess\M2ePro\Model\Amazon\Template\Synchronization::LIST_ADVANCED_RULES_PREFIX
                ]
            );
            $ruleModel->loadFromSerialized($amazonSynchronizationTemplate->getListAdvancedRulesFilters());

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
