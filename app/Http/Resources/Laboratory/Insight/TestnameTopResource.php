<?php

namespace App\Http\Resources\Laboratory\Insight;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TestnameTopResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return parent::toArray($request);
    }
}
