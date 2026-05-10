<?php

namespace App\Domain\LiveSession\Services;

use App\Domain\LiveSession\DTOs\AssetDTO;
use App\Domain\LiveSession\Enums\AssetType;
use App\Domain\LiveSession\Models\LiveSessionAsset;
use App\Domain\LiveSession\Repositories\Contracts\AssetRepositoryInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class AssetService
{
    public function __construct(
        private readonly AssetRepositoryInterface $repository,
    ) {}

    /**
     * Upload an asset for a session.
     */
    public function upload(
        int $sessionId,
        UploadedFile $file,
        int $uploadedBy,
    ): LiveSessionAsset {
        // Determine asset type from MIME type
        $assetType = $this->determineAssetType($file->getMimeType());
        if (!$assetType) {
            throw new \Exception('Invalid file type');
        }

        // Validate file size
        $maxSize = config('live-session.limits.max_file_size', 52428800); // 50MB
        if ($file->getSize() > $maxSize) {
            throw new \Exception('File size exceeds limit');
        }

        // Generate storage path
        $disk = config('live-session.storage.assets_disk', 's3');
        $path = $this->generateStoragePath($sessionId, $file->getClientOriginalName());

        // Upload file
        Storage::disk($disk)->put($path, file_get_contents($file));

        // Generate thumbnail if PDF
        $thumbnailPath = null;
        if ($assetType === AssetType::PDF) {
            $thumbnailPath = $this->generateThumbnail($disk, $path);
        }

        // Get page count for PDF
        $pageCount = null;
        if ($assetType === AssetType::PDF) {
            $pageCount = $this->getPdfPageCount($disk, $path);
        }

        // Create asset record
        $dto = new AssetDTO(
            type: $assetType->value,
            storageDisk: $disk,
            storagePath: $path,
            fileName: $file->getClientOriginalName(),
            fileSize: $file->getSize(),
            mimeType: $file->getMimeType(),
            pageCount: $pageCount,
            thumbnailPath: $thumbnailPath,
            uploadedBy: $uploadedBy,
        );

        return $this->repository->create($dto);
    }

    /**
     * Delete an asset.
     */
    public function delete(LiveSessionAsset $asset): bool
    {
        // Delete file from storage
        Storage::disk($asset->storage_disk)->delete($asset->storage_path);
        
        // Delete thumbnail if exists
        if ($asset->thumbnail_path) {
            Storage::disk($asset->storage_disk)->delete($asset->thumbnail_path);
        }

        // Delete record
        return $this->repository->delete($asset);
    }

    /**
     * Get assets for a session.
     */
    public function getBySession(int $sessionId): \Illuminate\Database\Eloquent\Collection
    {
        return $this->repository->getBySession($sessionId);
    }

    /**
     * Get PDF assets for a session.
     */
    public function getPdfAssetsBySession(int $sessionId): \Illuminate\Database\Eloquent\Collection
    {
        return $this->repository->getPdfAssetsBySession($sessionId);
    }

    /**
     * Get image assets for a session.
     */
    public function getImageAssetsBySession(int $sessionId): \Illuminate\Database\Eloquent\Collection
    {
        return $this->repository->getImageAssetsBySession($sessionId);
    }

    /**
     * Determine asset type from MIME type.
     */
    private function determineAssetType(string $mimeType): ?AssetType
    {
        return AssetType::fromMimeType($mimeType);
    }

    /**
     * Generate storage path for an asset.
     */
    private function generateStoragePath(int $sessionId, string $originalName): string
    {
        $basePath = config('live-session.storage.assets_path', 'live-sessions/assets');
        $filename = pathinfo($originalName, PATHINFO_FILENAME);
        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
        $uniqueName = sprintf('%s_%s.%s', $filename, uniqid(), $extension);
        
        return sprintf('%s/%d/%s', $basePath, $sessionId, $uniqueName);
    }

    /**
     * Generate thumbnail for a PDF.
     */
    private function generateThumbnail(string $disk, string $path): ?string
    {
        // This would use a PDF library like spatie/pdf-to-image
        // For now, return null as placeholder
        return null;
    }

    /**
     * Get page count of a PDF.
     */
    private function getPdfPageCount(string $disk, string $path): ?int
    {
        // This would use a PDF library to count pages
        // For now, return null as placeholder
        return null;
    }
}
