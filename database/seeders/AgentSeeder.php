<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Agent;
use Faker\Factory as Faker;

class AgentSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create('en_US');
        $groups = ['GRP_MARKETING', 'GRP_SALES_EAST', 'GRP_SALES_WEST', 'GRP_ENTERPRISE', 'GRP_SMB'];

        for ($i = 1; $i <= 50; $i++) {
            $firstName = $faker->firstName;
            $lastName = $faker->lastName;
            $code = 'AGT-' . str_pad($i, 4, '0', STR_PAD_LEFT);
            
            Agent::firstOrCreate(
                ['agent_code' => $code],
                [
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'full_name' => $firstName . ' ' . $lastName,
                    'group_team_sales' => $faker->randomElement($groups),
                    'email' => strtolower($firstName . '.' . $lastName . '@internal.ims.com'),
                    'phone' => $faker->numerify('(###) ###-####'),
                    'is_active' => $faker->boolean(90),
                ]
            );
        }
    }
}
