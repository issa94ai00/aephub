<?php

namespace App\Domain\LiveSession\Repositories;

use App\Domain\LiveSession\DTOs\AssetDTO;
use App\Domain\LiveSession\Enums\AssetType;
use App\Domain\LiveSession\Models\LiveSessionAsset;
use App\Domain\LiveSession\Repositories\Contracts\AssetRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class AssetRepository implements AssetRepositoryInterface
{
    public function findById(int $id): ?LiveSessionAsset
    {
        return LiveSessionAsset::find($id);
    }

    public function create(AssetDTO $dto): LiveSessionAsset
    {
        return LiveSessionAsset::create($dto->toArray());
    }

    public function update(LiveSessionAsset $asset, AssetDTO $dto): LiveSessionAsset
    {
        $asset->update($dto->toArray());
        return $asset->fresh();
    }

    public function delete(LiveSessionAsset $asset): bool
    {
        return $asset->delete();
    }

    public function getBySession(int $sessionId): Collection
    {
        return LiveSessionAsset::where('session_id', $sessionId)->get();
    }

    public function getPdfAssetsBySession(int $sessionId): Collection
    {
        return LiveSessionAsset::where('session_id', $sessionId)->pdf()->get();
    }

    public function getImageAssetsBySession(int $sessionId): Collection
    {
        return LiveSessionAsset::where('session_id', $sessionId)->image()->get();
    }

    public function getByType(AssetType $type): Collection
    {
        return LiveSessionAsset::where('type', $type->value)->get();
    }

    public function setThumbnail(LiveSessionAsset $asset, string $path): bool
    {
        return $asset->update(['thumbnail_path' => $path]);
    }
}
