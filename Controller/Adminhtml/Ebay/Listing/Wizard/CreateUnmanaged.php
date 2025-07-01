<?php

declare(strict_types=1);

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Wizard;

use Ess\M2ePro\Controller\Adminhtml\Context;
use Ess\M2ePro\Controller\Adminhtml\Ebay\Listing as EbayListingController;
use Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory;
use Ess\M2ePro\Model\Ebay\Listing\Wizard\Create as CreateModel;
use Ess\M2ePro\Model\Ebay\Listing\Wizard\TemplateCategoryLinkProcessor;
use Ess\M2ePro\Model\ListingFactory;
use Ess\M2ePro\Model\Ebay\Listing\Wizard;
use Ess\M2ePro\Model\ResourceModel\Listing as ListingResource;
use Ess\M2ePro\Model\Ebay\Listing\Wizard\ManagerFactory;
use Ess\M2ePro\Helper\Data\Session;
use Ess\M2ePro\Helper\Data as DataHelper;

class CreateUnmanaged extends EbayListingController
{
    use WizardTrait;

    private ListingResource $listingResource;

    private ListingFactory $listingFactory;

    private CreateModel $createModel;

    private ManagerFactory $managerFactory;

    private Session $sessionDataHelper;

    private DataHelper $dataHelper;

    private TemplateCategoryLinkProcessor $templateCategoryLinkProcessor;

    public function __construct(
        ListingResource $listingResource,
        ListingFactory $listingFactory,
        CreateModel $createModel,
        ManagerFactory $managerFactory,
        Session $sessionDataHelper,
        TemplateCategoryLinkProcessor $templateCategoryLinkProcessor,
        DataHelper $dataHelper,
        Factory $ebayFactory,
        Context $context
    ) {
        parent::__construct($ebayFactory, $context);

        $this->listingResource = $listingResource;
        $this->listingFactory = $listingFactory;
        $this->createModel = $createModel;
        $this->managerFactory = $managerFactory;
        $this->sessionDataHelper = $sessionDataHelper;
        $this->templateCategoryLinkProcessor = $templateCategoryLinkProcessor;
        $this->dataHelper = $dataHelper;
    }

    public function execute()
    {
        $listingId = (int)$this->getRequest()->getParam('listingId');

        if (empty($listingId)) {
            $this->getMessageManager()->addError(__('Cannot start Wizard, Listing must be created first.'));

            return $this->_redirect('*/ebay_listing/index');
        }

        $listing = $this->listingFactory->create();

        //@todo Create Ebay Listing Repository
        $this->listingResource->load($listing, $listingId);
        $wizard = $this->createModel->process($listing, Wizard::TYPE_UNMANAGED);
        $manager = $this->managerFactory->create($wizard);

        $sessionKey = \Ess\M2ePro\Helper\Component\Ebay::NICK . '_'
            . \Ess\M2ePro\Helper\View::MOVING_LISTING_OTHER_SELECTED_SESSION_KEY;

        $selectedProducts = $this->sessionDataHelper->getValue($sessionKey);

        $errorsCount = 0;

        foreach ($selectedProducts as $otherListingProduct) {
            /** @var \Ess\M2ePro\Model\Listing\Other $listingOther */
            $listingOther = $this->ebayFactory->getObjectLoaded('Listing\Other', $otherListingProduct);
            $ebayListingOther = $listingOther->getChildObject();

            if ($listing->hasProduct($listingOther->getProductId())) {
                $errorsCount++;

                continue;
            }

            $wizardProduct = $manager->addUnmanagedProduct(
                $listingOther,
                $this->getCategoryData($ebayListingOther->getOnlineMainCategory(), $listing)
            );

            if ($wizardProduct === null) {
                $errorsCount++;
            }
        }

        $this->sessionDataHelper->removeValue($sessionKey);

        if ($errorsCount) {
            if (count($selectedProducts) == $errorsCount) {
                $manager->cancel();

                $this->setJsonContent(
                    [
                        'result' => false,
                        'message' => __(
                            'Products were not moved because they already exist in the selected Listing or do not
                            belong to the channel account or marketplace of the listing.'
                        ),
                    ]
                );

                return $this->getResult();
            }

            $this->setJsonContent(
                [
                    'result' => true,
                    'isFailed' => true,
                    'wizardId' => $wizard->getId(),
                    'message' => __(
                        'Some products were not moved because they already exist in the selected Listing or do not
                        belong to the channel account or marketplace of the listing.'
                    ),
                ]
            );
        } else {
            $this->setJsonContent(['result' => true, 'wizardId' => $wizard->getId()]);
        }

        return $this->getResult();
    }

    private function getCategoryData($onlineMainCategory, \Ess\M2ePro\Model\Listing $listing): ?int
    {
        $templateCategoryId = null;

        if (empty($onlineMainCategory)) {
            return $templateCategoryId;
        }

        [$path, $value] = explode(" (", $onlineMainCategory);
        $value = trim($value, ')');

        /** @var \Ess\M2ePro\Model\Ebay\Template\Category $templateCategory */
        $templateCategory = $this->activeRecordFactory->getObject('Ebay_Template_Category')->getCollection()
                                                      ->addFieldToFilter('marketplace_id', $listing->getMarketplaceId())
                                                      ->addFieldToFilter('category_id', $value)
                                                      ->addFieldToFilter('is_custom_template', 0)
                                                      ->getFirstItem();

        if ($templateCategory->getId()) {
            $templateCategoryId = (int)$templateCategory->getId();
        }

        return $templateCategoryId;
    }
}
