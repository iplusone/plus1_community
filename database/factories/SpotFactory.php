<?php

namespace Database\Factories;

use App\Models\Spot;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Spot>
 */
class SpotFactory extends Factory
{
    protected $model = Spot::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->company().' '.fake()->randomElement(['本店', '支店', '営業所', '教室', 'スタジオ']);
        $prefecture = fake()->randomElement([
            '東京都',
            '神奈川県',
            '千葉県',
            '埼玉県',
            '大阪府',
            '愛知県',
        ]);

        return [
            'parent_id' => null,
            'depth' => 1,
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(100, 999),
            'postal_code' => fake()->postcode(),
            'prefecture' => $prefecture,
            'city' => fake()->city(),
            'town' => fake()->streetName(),
            'address_line' => fake()->streetAddress(),
            'phone' => fake()->phoneNumber(),
            'description' => fake()->realText(140),
            'features' => fake()->sentence(18),
            'access_text' => fake()->sentence(18),
            'nearest_station_max_walking_minutes' => 30,
            'business_hours_text' => '10:00 - 19:00',
            'holiday_text' => fake()->randomElement(['水曜', '日曜', '祝日']),
            'thumbnail_path' => null,
            'is_public' => true,
            'published_at' => now()->subDays(fake()->numberBetween(1, 60)),
            'view_count' => fake()->numberBetween(10, 5000),
            'sort_order' => fake()->numberBetween(0, 20),
        ];
    }

    public function private(): static
    {
        return $this->state(fn () => [
            'is_public' => false,
            'published_at' => null,
        ]);
    }
}
