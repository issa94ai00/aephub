<?php

namespace App\Domain\LiveSession\DTOs;

readonly class AssetDTO
{
    public function __construct(
        public string $type,
        public string $storageDisk,
        public string $storagePath,
        public string $fileName,
        public int $fileSize,
        public string $mimeType,
        public ?int $pageCount,
        public ?string $thumbnailPath,
        public int $uploadedBy,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            type: $data['type'],
            storageDisk: $data['storage_disk'],
            storagePath: $data['storage_path'],
            fileName: $data['file_name'],
            fileSize: $data['file_size'],
            mimeType: $data['mime_type'],
            pageCount: $data['page_count'] ?? null,
            thumbnailPath: $data['thumbnail_path'] ?? null,
            uploadedBy: $data['uploaded_by'],
        );
    }

    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'storage_disk' => $this->storageDisk,
            'storage_path' => $this->storagePath,
            'file_name' => $this->fileName,
            'file_size' => $this->fileSize,
            'mime_type' => $this->mimeType,
            'page_count' => $this->pageCount,
            'thumbnail_path' => $this->thumbnailPath,
            'uploaded_by' => $this->uploadedBy,
        ];
    }
}
