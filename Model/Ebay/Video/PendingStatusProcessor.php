<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\Video;

class PendingStatusProcessor
{
    private const MAX_COUNT_PER_ONE_REQUEST = 10;

    private \Ess\M2ePro\Model\Ebay\Video\Repository $repository;
    private \Ess\M2ePro\Model\Ebay\Connector\Dispatcher $dispatcher;

    public function __construct(
        \Ess\M2ePro\Model\Ebay\Video\Repository $videoRepository,
        \Ess\M2ePro\Model\Ebay\Connector\Dispatcher $dispatcher
    ) {
        $this->repository = $videoRepository;
        $this->dispatcher = $dispatcher;
    }

    public function process(\Ess\M2ePro\Model\Account $account): void
    {
        $videos = $this->repository->findReadyToUpload(
            \Ess\M2ePro\Model\Ebay\Video::STATUS_PENDING,
            (int)$account->getId(),
            self::MAX_COUNT_PER_ONE_REQUEST,
        );

        if (empty($videos)) {
            return;
        }

        $videoUrls = [];
        foreach ($videos as $video) {
            $videoUrls[] = $video->getUrl();
        }

        /** @var \Ess\M2ePro\Model\Ebay\Connector\Video\Upload\ItemsRequester $connectorObj */
        $connectorObj = $this->dispatcher->getCustomConnector(
            'Ebay_Connector_Video_Upload_ItemsRequester',
            [
                'videos' => $videoUrls,
            ],
            null,
            $account
        );

        $this->dispatcher->process($connectorObj);

        foreach ($videos as $video) {
            $video->setStatusUploading();
            $this->repository->save($video);
        }
    }
}
