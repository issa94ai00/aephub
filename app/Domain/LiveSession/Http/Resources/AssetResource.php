<?php

namespace App\Domain\LiveSession\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AssetResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type->value,
            'file_name' => $this->file_name,
            'file_size' => $this->file_size,
            'human_file_size' => $this->human_file_size,
            'mime_type' => $this->mime_type,
            'page_count' => $this->page_count,
            'download_url' => $this->download_url,
            'thumbnail_url' => $this->thumbnail_url,
            'uploaded_by' => [
                'id' => $this->uploader->id,
                'name' => $this->uploader->name,
            ],
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
        ];
    }
}
