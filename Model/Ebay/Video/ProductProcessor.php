<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\Video;

use Ess\M2ePro\Model\Ebay\Video\ProductProcessor\Result;

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

    public function process(\Ess\M2ePro\Model\Listing\Product $listingProduct): Result
    {
        /** @var \Ess\M2ePro\Model\Ebay\Listing\Product $ebayListingProduct */
        $ebayListingProduct = $listingProduct->getChildObject();

        if (!$ebayListingProduct->isVideoModeEnabled()) {
            if ($ebayListingProduct->hasVideoUrl()) {
                $this->removeVideoDataFromProduct($ebayListingProduct);
                $this->createInstruction($listingProduct);
            }

            return Result::createSuccess();
        }

        $videoUrl = $ebayListingProduct->findVideoUrlByPolicy();
        if ($videoUrl === null) {
            if ($ebayListingProduct->hasVideoUrl()) {
                $this->removeVideoDataFromProduct($ebayListingProduct);
                $this->createInstruction($listingProduct);
            }

            $message = (string)__('Product video URL was not found in the selected Magento Attribute.');
            if ($ebayListingProduct->getEbayDescriptionTemplate()->isVideoModeCustomValue()) {
                $message = (string)__('The product video URL could not be found in the custom value field.');
            }
            $this->addLogToListingProduct($listingProduct, $message);

            return Result::createFail($message);
        }

        if (!$this->isValidUrl($videoUrl)) {
            $message = (string)__(
                'Product Video was not uploaded on eBay: invalid video URL value in attribute "%label"',
                ['label' => $this->getLabel($ebayListingProduct)]
            );

            if ($ebayListingProduct->getEbayDescriptionTemplate()->isVideoModeCustomValue()) {
                $message = (string)__(
                    'Product Video was not uploaded on eBay: invalid video URL value in custom value field.'
                );
            }

            $this->addLogToListingProduct($listingProduct, $message);

            return Result::createFail($message);
        }

        if ($ebayListingProduct->getVideoUrl() !== $videoUrl) {
            $this->updateVideoUrlInProduct($ebayListingProduct, $videoUrl);
        }

        $accountId = $listingProduct->getListing()->getAccountId();
        $video = $this->videoService->find($accountId, $videoUrl);

        if ($video === null) {
            $this->videoService->create($accountId, $videoUrl);

            return Result::createInProgress();
        }

        if (
            $video->isStatusPending()
            || $video->isStatusUploading()
        ) {
            return Result::createInProgress();
        }

        if ($video->isStatusFailed()) {
            return Result::createFail($video->getError());
        }

        if (
            $video->isStatusSuccess()
            && $ebayListingProduct->getVideoId() !== $video->getVideoId()
        ) {
            $this->updateVideoIdInProduct($ebayListingProduct, $video->getVideoId());
            $this->createInstruction($listingProduct);
        }

        return Result::createSuccess();
    }

    private function isValidUrl(string $url): bool
    {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }

    private function getLabel($listingProduct): string
    {
        $magentoVideoAttribute = $listingProduct->findVideoUrlByPolicy();

        return $this->magentoAttributeHelper->getAttributeLabel($magentoVideoAttribute);
    }

    private function removeVideoDataFromProduct(\Ess\M2ePro\Model\Ebay\Listing\Product $ebayListingProduct): void
    {
        $ebayListingProduct->setVideoUrl(null);
        $ebayListingProduct->setVideoId(null);
        $ebayListingProduct->save();
    }

    public function updateVideoIdInProduct(
        \Ess\M2ePro\Model\Ebay\Listing\Product $ebayListingProduct,
        string $videoId
    ): void {
        $ebayListingProduct->setVideoId($videoId);
        $ebayListingProduct->save();
    }

    public function updateVideoUrlInProduct(
        \Ess\M2ePro\Model\Ebay\Listing\Product $ebayListingProduct,
        string $videoUrl
    ): void {
        $ebayListingProduct->setVideoUrl($videoUrl);
        $ebayListingProduct->save();
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

    private function addLogToListingProduct(\Ess\M2ePro\Model\Listing\Product $listingProduct, string $message): void
    {
        /** @var \Ess\M2ePro\Model\Ebay\Listing\Log $log */
        $log = $this->logFactory->create();
        $log->setComponentMode($listingProduct->getComponentMode());

        $log->addProductMessage(
            $listingProduct->getListingId(),
            $listingProduct->getProductId(),
            $listingProduct->getId(),
            \Ess\M2ePro\Helper\Data::INITIATOR_EXTENSION,
            null,
            \Ess\M2ePro\Model\Listing\Log::ACTION_VIDEO,
            $message,
            \Ess\M2ePro\Model\Log\AbstractModel::TYPE_ERROR
        );
    }
}
