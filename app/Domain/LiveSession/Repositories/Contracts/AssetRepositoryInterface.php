<?php

namespace App\Domain\LiveSession\Repositories\Contracts;

use App\Domain\LiveSession\DTOs\AssetDTO;
use App\Domain\LiveSession\Enums\AssetType;
use App\Domain\LiveSession\Models\LiveSessionAsset;
use Illuminate\Database\Eloquent\Collection;

interface AssetRepositoryInterface
{
    /**
     * Find an asset by ID.
     */
    public function findById(int $id): ?LiveSessionAsset;

    /**
     * Create a new asset.
     */
    public function create(AssetDTO $dto): LiveSessionAsset;

    /**
     * Update an existing asset.
     */
    public function update(LiveSessionAsset $asset, AssetDTO $dto): LiveSessionAsset;

    /**
     * Delete an asset.
     */
    public function delete(LiveSessionAsset $asset): bool;

    /**
     * Get assets for a session.
     */
    public function getBySession(int $sessionId): Collection;

    /**
     * Get PDF assets for a session.
     */
    public function getPdfAssetsBySession(int $sessionId): Collection;

    /**
     * Get image assets for a session.
     */
    public function getImageAssetsBySession(int $sessionId): Collection;

    /**
     * Get assets by type.
     */
    public function getByType(AssetType $type): Collection;

    /**
     * Set thumbnail path for an asset.
     */
    public function setThumbnail(LiveSessionAsset $asset, string $path): bool;
}
