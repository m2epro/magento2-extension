<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay;

class VideoFactory
{
    private \Magento\Framework\ObjectManagerInterface $objectManager;

    public function __construct(\Magento\Framework\ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    public function create(
        int $accountId,
        string $url
    ): Video {
        $video = $this->objectManager->create(Video::class);
        $video->init($accountId, $url);

        return $video;
    }
}
