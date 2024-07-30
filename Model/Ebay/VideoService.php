<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay;

class VideoService
{
    private \Ess\M2ePro\Model\Ebay\Video\Repository $videoRepository;
    private \Ess\M2ePro\Model\Ebay\VideoFactory $videoFactory;

    public function __construct(
        \Ess\M2ePro\Model\Ebay\Video\Repository $videoRepository,
        \Ess\M2ePro\Model\Ebay\VideoFactory $videoFactory
    ) {
        $this->videoRepository = $videoRepository;
        $this->videoFactory = $videoFactory;
    }

    public function find(int $accountId, string $url): ?\Ess\M2ePro\Model\Ebay\Video
    {
        return $this->videoRepository->findByAccountIdAndUrl($accountId, $url);
    }

    public function create(int $accountId, string $url): void
    {
        if (!$this->isValidUrl($url)) {
            return;
        }

        $video = $this->videoFactory->create($accountId, $url);

        $this->videoRepository->create($video);
    }

    private function isValidUrl(string $url): bool
    {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }
}
