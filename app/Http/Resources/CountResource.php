<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CountResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            "result" => [
                "all" => $this->date_ordered,
                "pending" => $this->date_needed,
                "approve" => $this->date_approved,
                "disapprove" => $this->deleted_at,
            ],
        ];
    }
}
