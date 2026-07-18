<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserBookingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'custodian_id' => $this->custodian_id,
            'booking_date' => $this->booking_date,
            'booking_time' => $this->booking_time,
            'message' => $this->message,
            'status' => $this->status,
            'session_type' => $this->session_type,
            'session_duration' => $this->session_duration,
            'platform_link' => $this->platform_link,
            'amount_charged_usd' => $this->amount_charged_usd,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'custodian' => $this->relationLoaded('custodian') && $this->custodian ? [
                'id' => $this->custodian->id,
                'name' => $this->custodian->name,
                'picture' => $this->custodian->picture,
                'avatar_initials' => $this->custodian->avatar_initials,
                'avatar_class' => $this->custodian->avatar_class,
                'specialty' => $this->custodian->specialty,
                'gradient_bg' => $this->custodian->gradient_bg,
            ] : null,
        ];
    }
}
