<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\Video;

class ProductProcessor
{
    private \Ess\M2ePro\Model\Ebay\VideoService $videoService;
    private \Ess\M2ePro\Model\ResourceModel\Listing\Product\Instruction $instructionResource;
    private \Ess\M2ePro\Model\Listing\Log\Factory $logFactory;
    private \Ess\M2ePro\Helper\Magento\Attribute $magentoAttributeHelper;

    public function __construct(
        \Ess\M2ePro\Model\Ebay\VideoService $videoService,
        \Ess\M2ePro\Model\ResourceModel\Listing\Product\Instruction $instructionResource,
        \Ess\M2ePro\Model\Listing\Log\Factory $logFactory,
        \Ess\M2ePro\Helper\Magento\Attribute $magentoAttributeHelper
    ) {
        $this->videoService = $videoService;
        $this->instructionResource = $instructionResource;
        $this->logFactory = $logFactory;
        $this->magentoAttributeHelper = $magentoAttributeHelper;
    }

    public function process(\Ess\M2ePro\Model\Listing\Product $listingProduct): void
    {
        $ebayListingProduct = $listingProduct->getChildObject();
        $videoUrl = $ebayListingProduct->findVideoUrlByPolicy();

        if ($videoUrl === null) {
            if (!$ebayListingProduct->hasVideoUrl()) {
                return;
            }

            $ebayListingProduct->setVideoUrl(null);
            $ebayListingProduct->setVideoId(null);
            $ebayListingProduct->save();

            $this->createInstruction($listingProduct);

            return;
        }

        if (!$this->isValidUrl($videoUrl)) {
            $this->logListingProductMessage($listingProduct, $this->getLabel($listingProduct));

            return;
        }

        if ($ebayListingProduct->getVideoUrl() !== $videoUrl) {
            $ebayListingProduct->setVideoUrl($videoUrl);
            $ebayListingProduct->save();
        }

        $accountId = $listingProduct->getListing()->getAccountId();
        $video = $this->videoService->find($accountId, $videoUrl);

        if ($video === null) {
            $this->videoService->create($accountId, $videoUrl);

            return;
        }

        if (!$video->isStatusSuccess()) {
            return;
        }

        $videoId = $video->getVideoId();

        if ($ebayListingProduct->getVideoId() === $videoId) {
            return;
        }

        $ebayListingProduct->setVideoId($videoId);
        $ebayListingProduct->save();

        $this->createInstruction($listingProduct);
    }

    private function isValidUrl(string $url): bool
    {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }

    private function getLabel($listingProduct): string
    {
        $magentoVideoAttribute = $listingProduct->getEbayDescriptionTemplate()->getVideoAttribute();

        return $this->magentoAttributeHelper->getAttributeLabel($magentoVideoAttribute);
    }

    private function logListingProductMessage(\Ess\M2ePro\Model\Listing\Product $listingProduct, string $label): void
    {
        /** @var \Ess\M2ePro\Model\Ebay\Listing\Log $log */
        $log = $this->logFactory->create();
        $log->setComponentMode($listingProduct->getComponentMode());

        $message = (string)__(
            'Product Video was not uploaded on eBay: invalid video URL value in attribute "%label"',
            ['label' => $label]
        );

        $log->addProductMessage(
            $listingProduct->getListingId(),
            $listingProduct->getProductId(),
            $listingProduct->getId(),
            \Ess\M2ePro\Helper\Data::INITIATOR_UNKNOWN,
            null,
            \Ess\M2ePro\Model\Listing\Log::ACTION_VIDEO,
            $message,
            \Ess\M2ePro\Model\Log\AbstractModel::TYPE_WARNING
        );
    }

    private function createInstruction(\Ess\M2ePro\Model\Listing\Product $listingProduct): void
    {
        $this->instructionResource->add(
            [
                [
                    'listing_product_id' => $listingProduct->getId(),
                    'component' => \Ess\M2ePro\Helper\Component\Ebay::NICK,
                    'type' => UploadingStatusProcessor::INSTRUCTION_TYPE_PRODUCT_VIDEO_URL_UPLOADED,
                    'initiator' => 'ebay_video_product_processor',
                    'priority' => 30,
                ],
            ]
        );
    }
}
