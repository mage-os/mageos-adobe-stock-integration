<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MediaGallerySynchronization\Model;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\MediaGalleryApi\Api\Data\AssetInterface;
use Magento\MediaGalleryApi\Api\Data\AssetInterfaceFactory;
use Magento\MediaGalleryMetadataApi\Api\ExtractMetadataInterface;
use Magento\MediaGallerySynchronization\Model\Filesystem\SplFileInfoFactory;
use Magento\MediaGallerySynchronizationApi\Model\GetContentHashInterface;

class CreateAssetFromFile
{
    /**
     * Date format
     */
    private const DATE_FORMAT = 'Y-m-d H:i:s';

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var File
     */
    private $driver;

    /**
     * @var TimezoneInterface;
     */
    private $date;

    /**
     * @var AssetInterfaceFactory
     */
    private $assetFactory;

    /**
     * @var GetContentHashInterface
     */
    private $getContentHash;

    /**
     * @var ExtractMetadataInterface
     */
    private $extractMetadata;

    /**
     * @var SplFileInfoFactory
     */
    private $splFileInfoFactory;

    /**
     * @param Filesystem $filesystem
     * @param File $driver
     * @param TimezoneInterface $date
     * @param AssetInterfaceFactory $assetFactory
     * @param GetContentHashInterface $getContentHash
     * @param ExtractMetadataInterface $extractMetadata
     * @param SplFileInfoFactory $splFileInfoFactory
     */
    public function __construct(
        Filesystem $filesystem,
        File $driver,
        TimezoneInterface $date,
        AssetInterfaceFactory $assetFactory,
        GetContentHashInterface $getContentHash,
        ExtractMetadataInterface $extractMetadata,
        SplFileInfoFactory $splFileInfoFactory
    ) {
        $this->filesystem = $filesystem;
        $this->driver = $driver;
        $this->date = $date;
        $this->assetFactory = $assetFactory;
        $this->getContentHash = $getContentHash;
        $this->extractMetadata = $extractMetadata;
        $this->splFileInfoFactory = $splFileInfoFactory;
    }

    /**
     * Create and format media asset object
     *
     * @param string $path
     * @return AssetInterface
     * @throws FileSystemException
     */
    public function execute(string $path): AssetInterface
    {
        $file = $this->splFileInfoFactory->create($path);
        $absolutePath = $file->getPath() . '/' . $file->getFileName();
        [$width, $height] = getimagesize($absolutePath);

        $metadata = $this->extractMetadata->execute($absolutePath);

        return $this->assetFactory->create(
            [
                'id' => null,
                'path' => $path,
                'title' => $metadata->getTitle() ?: $file->getBasename('.' . $file->getExtension()),
                'description' => $metadata->getDescription(),
                'createdAt' => $this->date->date($file->getCTime())->format(self::DATE_FORMAT),
                'updatedAt' => $this->date->date($file->getMTime())->format(self::DATE_FORMAT),
                'width' => $width,
                'height' => $height,
                'hash' => $this->getHash($path),
                'size' => $file->getSize(),
                'contentType' => 'image/' . $file->getExtension(),
                'source' => 'Local'
            ]
        );
    }

    /**
     * Get hash image content.
     *
     * @param string $path
     * @return string
     * @throws FileSystemException
     */
    private function getHash(string $path): string
    {
        return $this->getContentHash->execute(
            $this->filesystem->getDirectoryRead(DirectoryList::MEDIA)->readFile($path)
        );
    }
}
