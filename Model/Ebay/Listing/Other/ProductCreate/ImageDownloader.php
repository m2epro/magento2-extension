<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\Listing\Other\ProductCreate;

use Magento\Framework\App\Filesystem\DirectoryList;

class ImageDownloader
{
    private const TEMP_SUBDIR = 'tmp/m2e_tmp';

    private \Magento\Framework\Filesystem $filesystem;
    private \Magento\Framework\HTTP\Client\Curl $curl;
    private \Magento\Framework\Filesystem\Io\File $file;

    public function __construct(
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Framework\HTTP\Client\Curl $curl,
        \Magento\Framework\Filesystem\Io\File $file
    ) {
        $this->filesystem = $filesystem;
        $this->curl = $curl;
        $this->file = $file;
    }

    /**
     * @return string|null Absolute file path or null on failure
     */
    public function execute(string $pictureUrl): ?string
    {
        $cleanUrl = $this->cleanUrl($pictureUrl);
        $fileName = $this->generateFileName($cleanUrl);

        try {
            $mediaDirectory = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);

            $mediaDirectory->create(self::TEMP_SUBDIR);

            $pictureContent = $this->downloadPicture($cleanUrl);
            if (empty($pictureContent)) {
                return null;
            }

            $relativeFilePath = self::TEMP_SUBDIR . DIRECTORY_SEPARATOR . $fileName;
            $mediaDirectory->writeFile($relativeFilePath, $pictureContent);

            return $mediaDirectory->getAbsolutePath($relativeFilePath);
        } catch (\Exception $e) {
            return null;
        }
    }

    private function cleanUrl(string $pictureUrl): string
    {
        return strtok($pictureUrl, '?');
    }

    private function generateFileName(string $url): string
    {
        $extension = $this->file->getPathInfo($url)['extension'] ?: 'jpg';

        return 'import_' . uniqid() . '.' . $extension;
    }

    private function downloadPicture(string $pictureUrl): ?string
    {
        try {
            $this->curl->setTimeout(30);
            $this->curl->setOption(CURLOPT_FOLLOWLOCATION, true);
            $this->curl->get($pictureUrl);

            if ($this->curl->getStatus() === 200) {
                return $this->curl->getBody();
            }
        } catch (\Exception $e) {
            return null;
        }

        return null;
    }
}
