<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\Listing;

use Ess\M2ePro\Helper\Date as DateHelper;
use Ess\M2ePro\Helper\Factory as HelperFactory;
use Ess\M2ePro\Model\ActiveRecord\AbstractModel;
use Ess\M2ePro\Model\ActiveRecord\Factory as ActiveRecordFactory;
use Ess\M2ePro\Model\Listing;
use Ess\M2ePro\Model\Ebay\Listing\Wizard\Repository as WizardRepository;
use Ess\M2ePro\Model\ListingFactory;
use Ess\M2ePro\Model\Factory;
use Ess\M2ePro\Model\ResourceModel\Listing as ListingResource;
use Ess\M2ePro\Model\ResourceModel\Ebay\Listing\Wizard as ListingWizardResource;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;

class Wizard extends AbstractModel
{
    public const TYPE_GENERAL = 'general';
    public const TYPE_UNMANAGED = 'unmanaged';

    private Listing $listing;

    private ListingResource $listingResource;

    private ListingFactory $listingFactory;

    private WizardRepository $wizardRepository;

    /** @var \Ess\M2ePro\Model\Ebay\Listing\Wizard\Step[] */
    private array $steps;

    public function __construct(
        WizardRepository $wizardRepository,
        ListingResource $listingResource,
        ListingFactory $listingFactory,
        Context $context,
        Registry $registry,
        Factory $modelFactory,
        ActiveRecordFactory $activeRecordFactory,
        HelperFactory $helperFactory,
        ?AbstractResource $resource = null,
        ?AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct(
            $modelFactory,
            $activeRecordFactory,
            $helperFactory,
            $context,
            $registry,
            $resource,
            $resourceCollection,
            $data
        );
        $this->listingResource = $listingResource;
        $this->wizardRepository = $wizardRepository;
        $this->listingFactory = $listingFactory;
    }

    protected function _construct(): void
    {
        parent::_construct();
        $this->_init(ListingWizardResource::class);
    }

    /**
     * @param \Ess\M2ePro\Model\Ebay\Listing $listing
     * @param string $type
     * @param string $firstStepNick
     *
     * @return $this
     * @throws \Exception
     */
    public function init(Listing $listing, string $type, string $firstStepNick): self
    {
        $this
            ->setData(ListingWizardResource::COLUMN_LISTING_ID, $listing->getId())
            ->setData(ListingWizardResource::COLUMN_TYPE, $type)
            ->setData(ListingWizardResource::COLUMN_CURRENT_STEP_NICK, $firstStepNick)
            ->setData(
                ListingWizardResource::COLUMN_PROCESS_START_DATE,
                DateHelper::createCurrentGmt(),
            );

        return $this;
    }

    public function initListing(Listing $listing): void
    {
        $this->listing = $listing;
    }

    public function getListing(): ?Listing
    {
        if (isset($this->listing)) {
            return $this->listing;
        }

        $ebayListing = $this->listingFactory->create();

        //@todo Create Ebay Listing Repository
        $this->listingResource->load($ebayListing, $this->getListingId());

        return $ebayListing;
    }

    public function initSteps(array $steps): self
    {
        $this->steps = $steps;

        return $this;
    }

    public function getSteps(): array
    {
        if (isset($this->steps)) {
            return $this->steps;
        }

        return $this->steps = $this->wizardRepository->findSteps($this);
    }

    // ----------------------------------------

    public function getId(): int
    {
        return (int)parent::getId();
    }

    public function getListingId(): int
    {
        return (int)$this->getData(ListingWizardResource::COLUMN_LISTING_ID);
    }

    public function getType(): string
    {
        return $this->getData(ListingWizardResource::COLUMN_TYPE);
    }

    public function setProductCountTotal(int $count): self
    {
        $this->setData(ListingWizardResource::COLUMN_PRODUCT_COUNT_TOTAL, $count);

        return $this;
    }

    public function complete(int $productCount): self
    {
        $this->setData(ListingWizardResource::COLUMN_PRODUCT_COUNT_TOTAL, $productCount)
             ->setData(ListingWizardResource::COLUMN_IS_COMPLETED, 1)
             ->setData(
                 ListingWizardResource::COLUMN_PROCESS_END_DATE,
                 DateHelper::createCurrentGmt()->format('Y-m-d H:i:s'),
             );

        return $this;
    }

    public function isCompleted(): bool
    {
        return (bool)$this->getData(ListingWizardResource::COLUMN_IS_COMPLETED);
    }

    public function setCurrentStepNick(string $nick): self
    {
        $this->setData(ListingWizardResource::COLUMN_CURRENT_STEP_NICK, $nick);

        return $this;
    }

    public function getCurrentStepNick(): string
    {
        return $this->getData(ListingWizardResource::COLUMN_CURRENT_STEP_NICK);
    }

    public static function validateType(string $type): void
    {
        if (!in_array($type, [self::TYPE_GENERAL, self::TYPE_UNMANAGED])) {
            throw new \LogicException('Wrong listing wizard type.');
        }
    }
}
