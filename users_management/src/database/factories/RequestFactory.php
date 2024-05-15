<?php

namespace Database\Factories;

use App\Enums\Requests\AuthenticationType;
use App\Enums\Requests\ServiceType;
use App\Enums\Requests\Status;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Request>
 */
class RequestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'submit_username' => $this->get_username(),
            'identity' => $this->get_identity(),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'phone' => '05'.fake()->randomNumber(8, true),
            'email' => fake()->email(),
            'unit' => fake()->word(5),
            'sub' => fake()->word(5),
            'authentication_type' => fake()->randomElement(AuthenticationType::cases()),
            'service_type' => fake()->randomElement(ServiceType::cases()),
            'validity' => fake()->randomNumber(5, false),
            'status' => Status::New,
            'description' => fake()->sentence(5),
        ];
    }

    private function get_username()
    {
        $requester_name = explode(' ', fake()->name());
        $requester_end_ID = fake()->randomNumber(6, true);

        return substr($requester_name[0], 0, 1).substr($requester_name[1], 0, 1).$requester_end_ID;
    }

    private function get_identity()
    {
        $start_id = (string) fake()->unique()->randomNumber(8, true);
        $sum = 0;
        for ($i = 0; $i < 8; $i++) {
            $digit = (int) $start_id[$i];
            $sum += ($i % 2 === 0) ? $digit : array_sum(str_split($digit * 2));
        }

        return $start_id.((10 - ($sum % 10)) % 10);
    }
}
