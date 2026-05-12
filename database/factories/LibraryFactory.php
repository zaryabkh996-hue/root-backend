<?php

namespace Database\Factories;

use App\Models\Library;
use Illuminate\Database\Eloquent\Factories\Factory;

class LibraryFactory extends Factory
{
    protected $model = Library::class;

    public function definition(): array
    {
        $types = ['audio', 'video', 'pdf', 'text'];
        $categories = ['History', 'Language', 'Culture', 'Traditions', 'Heritage', 'Stories'];

        return [
            'title' => $this->faker->sentence(3),
            'description' => $this->faker->paragraph(3),
            'author' => $this->faker->name(),
            'category' => $this->faker->randomElement($categories),
            'type' => $this->faker->randomElement($types),
            'duration' => $this->faker->randomElement(['10 min', '15 min', '20 min', '30 min', '1 hour', '1.5 hour']),
            'image_url' => 'https://images.unsplash.com/photo-1506157786151-b8491531f063?w=400&h=300&fit=crop',
            'file_url' => 'https://example.com/file.mp3',
           
        ];
    }
}
