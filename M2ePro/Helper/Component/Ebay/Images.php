<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Helper\Component\Ebay;

use Ess\M2ePro\Model\Magento\Product\Image;

/**
 * Class \Ess\M2ePro\Helper\Component\Ebay\Images
 */
class Images extends \Ess\M2ePro\Helper\AbstractHelper
{
    const SHOULD_BE_URLS_SECURE_NO  = 0;
    const SHOULD_BE_URLS_SECURE_YES = 1;

    //########################################

    public function shouldBeUrlsSecure()
    {
        return (bool)(int)$this->getHelper('Module')->getConfig()->getGroupValue(
            '/ebay/description/',
            'should_be_ulrs_secure'
        );
    }

    //########################################

    /**
     * @param Image[] $images
     * @param string|NULL $attributeLabel for Variation product
     * @return string $hash
     */
    public function getHash(array $images, $attributeLabel = null)
    {
        if (empty($images)) {
            return null;
        }

        $hashes = [];
        $haveNotSelfHostedImage = false;

        foreach ($images as $image) {
            $tempImageHash = $image->getHash();

            if (!$image->isSelfHosted()) {
                $haveNotSelfHostedImage = true;
            }

            $hashes[] = $tempImageHash;
        }

        $hash = sha1($this->getHelper('Data')->jsonEncode($hashes));
        $attributeLabel && $hash .= $attributeLabel;

        if ($haveNotSelfHostedImage) {
            $date = new \DateTime('now', new \DateTimeZone('UTC'));
            $hash .= '##' . $date->getTimestamp();
        }
        return $hash;
    }

    /**
     * @param string $hash
     * @param int $lifetime (in days) 2 by default
     * @return bool
     */
    public function isHashBelated($hash, $lifetime = 2)
    {
        if (strpos($hash, '##') === false) {
            return false;
        }

        $parts = explode('##', $hash);

        if (empty($parts[1])) {
            return true;
        }

        $validTill = new \DateTime('now', new \DateTimeZone('UTC'));
        $validTill->setTimestamp((int)$parts[1]);
        $validTill->modify("+ {$lifetime} days");

        $now = new \DateTime('now', new \DateTimeZone('UTC'));

        return $now->getTimestamp() >= $validTill->getTimestamp();
    }

    //----------------------------------------

    /**
     * @param string $savedHash
     * @param string $currentHash
     * @return bool
     */
    public function areHashesTheSame($savedHash, $currentHash)
    {
        if ($savedHash == $currentHash) {
            return true;
        }

        if (strpos($savedHash, '##') === false || strpos($currentHash, '##') === false) {
            return false;
        }

        $savedHash = explode('##', $savedHash);
        $currentHash = explode('##', $currentHash);

        return $savedHash[0] == $currentHash[0];
    }

    //########################################
}
