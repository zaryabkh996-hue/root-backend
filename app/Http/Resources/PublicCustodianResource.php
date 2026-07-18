<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PublicCustodianResource extends JsonResource
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
            'name' => $this->name,
            'picture' => $this->picture,
            'location' => $this->location,
            'country' => $this->country,
            'years_experience' => $this->years_experience,
            'specialty' => $this->specialty,
            'avatar_initials' => $this->avatar_initials,
            'avatar_class' => $this->avatar_class,
            'gradient_bg' => $this->gradient_bg,
            'availability' => $this->availability,
            'availability_text' => $this->availability_text,
            'share_text' => $this->share_text,
            'about' => $this->about,
            'languages' => $this->languages,
            'hourly_rate' => $this->hourly_rate,
            'rating' => $this->rating,
            'about_me' => $this->about_me,
            'specialties' => $this->specialties,
            'testimonials' => $this->testimonials,
            'services' => $this->services,
            'sessions' => $this->sessions ?? $this->bookings_count,
        ];
    }
}
