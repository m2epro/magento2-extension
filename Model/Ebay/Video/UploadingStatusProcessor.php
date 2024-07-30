<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\Video;

class UploadingStatusProcessor
{
    public const INSTRUCTION_TYPE_PRODUCT_VIDEO_URL_UPLOADED = 'product_video_url_uploaded';

    private \Ess\M2ePro\Model\Ebay\Video\Repository $repository;
    private \Ess\M2ePro\Model\ResourceModel\Listing\Product\CollectionFactory $listingProductCollectionFactory;
    private \Ess\M2ePro\Model\ResourceModel\Listing\Product\Instruction $instructionResource;
    private \Ess\M2ePro\Model\ResourceModel\Ebay\Listing\Product $ebayListingProductResource;
    private \Ess\M2ePro\Model\Listing\Log\Factory $logFactory;

    public function __construct(
        \Ess\M2ePro\Model\Ebay\Video\Repository $repository,
        \Ess\M2ePro\Model\ResourceModel\Listing\Product\CollectionFactory $listingProductCollectionFactory,
        \Ess\M2ePro\Model\ResourceModel\Listing\Product\Instruction $instructionResource,
        \Ess\M2ePro\Model\ResourceModel\Ebay\Listing\Product $ebayListingProductResource,
        \Ess\M2ePro\Model\Listing\Log\Factory $logFactory
    ) {
        $this->repository = $repository;
        $this->listingProductCollectionFactory = $listingProductCollectionFactory;
        $this->instructionResource = $instructionResource;
        $this->ebayListingProductResource = $ebayListingProductResource;
        $this->logFactory = $logFactory;
    }

    /**
     * @param \Ess\M2ePro\Model\Account $account
     * @param \Ess\M2ePro\Model\Ebay\Video\Channel\Video[] $channelVideos
     */
    public function processResponseData(\Ess\M2ePro\Model\Account $account, array $channelVideos): void
    {
        foreach ($channelVideos as $channelVideo) {
            $this->processChannelVideo($account, $channelVideo);
        }
    }

    private function processChannelVideo(
        \Ess\M2ePro\Model\Account $account,
        \Ess\M2ePro\Model\Ebay\Video\Channel\Video $channelVideo
    ): void {
        $video = $this->repository->findByAccountIdAndUrl(
            (int)$account->getId(),
            $channelVideo->getUrl()
        );

        if ($video === null || !$video->isStatusUploading()) {
            return;
        }

        $listingProducts = $this->findListingProductsWithVideoUrl((int)$account->getId(), $video->getUrl());

        if ($channelVideo->isUploaded()) {
            $this->handleSuccess($video, $channelVideo->getVideoId());

            $this->updateProductVideoIdAndCreateInstructions($video, $listingProducts);
        } else {
            $this->handleFailure($video, $channelVideo->getError());

            $this->writeErrorLogs($listingProducts);
        }
    }

    private function handleSuccess(\Ess\M2ePro\Model\Ebay\Video $video, string $videoId): void
    {
        $video->setStatusSuccess($videoId);
        $this->repository->save($video);
    }

    private function handleFailure(\Ess\M2ePro\Model\Ebay\Video $video, string $error): void
    {
        $video->setStatusFailed($error);
        $this->repository->save($video);
    }

    /**
     * @param \Ess\M2ePro\Model\Ebay\Video $video
     * @param \Ess\M2ePro\Model\Listing\Product[] $listingProducts
     */
    private function updateProductVideoIdAndCreateInstructions(
        \Ess\M2ePro\Model\Ebay\Video $video,
        array $listingProducts
    ): void {
        foreach ($listingProducts as $listingProduct) {
            if ($listingProduct->getChildObject()->getVideoId() === $video->getVideoId()) {
                continue;
            }

            $listingProduct->getChildObject()->setVideoId($video->getVideoId());
            $listingProduct->getChildObject()->save();

            $this->createInstruction($listingProduct);
        }
    }

    /**
     * @param \Ess\M2ePro\Model\Listing\Product[] $listingProducts
     */
    private function writeErrorLogs(array $listingProducts): void
    {
        foreach ($listingProducts as $listingProduct) {
            $this->logListingProductMessage($listingProduct);
        }
    }

    private function logListingProductMessage(\Ess\M2ePro\Model\Listing\Product $listingProduct): void
    {
        /** @var \Ess\M2ePro\Model\Ebay\Listing\Log $log */
        $log = $this->logFactory->create();
        $log->setComponentMode($listingProduct->getComponentMode());

        $message = (string)__(
            'Product Video was not uploaded to eBay: the Video source has an invalid format.',
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

    /**
     * @return \Ess\M2ePro\Model\Listing\Product[]
     */
    private function findListingProductsWithVideoUrl(int $accountId, string $videoUrl): array
    {
        $listingsProductsCollection = $this->listingProductCollectionFactory->create();
        $listingsProductsCollection->joinListingTable();
        $listingsProductsCollection->join(
            ['elp' => $this->ebayListingProductResource->getMainTable()],
            sprintf(
                '`main_table`.`%s`=`elp`.`%s`',
                \Ess\M2ePro\Model\ResourceModel\Listing\Product::COLUMN_ID,
                \Ess\M2ePro\Model\ResourceModel\Ebay\Listing\Product::COLUMN_LISTING_PRODUCT_ID
            ),
        );
        $listingsProductsCollection->addFieldToFilter(
            'l.' . \Ess\M2ePro\Model\ResourceModel\Listing::COLUMN_ACCOUNT_ID,
            $accountId
        );
        $listingsProductsCollection->addFieldToFilter(
            'elp.' . \Ess\M2ePro\Model\ResourceModel\Ebay\Listing\Product::COLUMN_VIDEO_URL,
            $videoUrl
        );

        return $listingsProductsCollection->getItems();
    }

    private function createInstruction(\Ess\M2ePro\Model\Listing\Product $listingProduct): void
    {
        $this->instructionResource->add(
            [
                [
                    'listing_product_id' => $listingProduct->getId(),
                    'component' => \Ess\M2ePro\Helper\Component\Ebay::NICK,
                    'type' => self::INSTRUCTION_TYPE_PRODUCT_VIDEO_URL_UPLOADED,
                    'initiator' => 'ebay_video_uploading_status_processor',
                    'priority' => 30,
                ],
            ]
        );
    }
}
