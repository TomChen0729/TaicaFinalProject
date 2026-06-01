<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Scenario;
class ScenarioSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [
            [
                'id' => 'fast_food',
                'title' => '🍔 Fast Food Drive-Thru',
                'task' => '任務：用英文點一份辣味麥脆雞套餐',
                'greeting' => 'Hi, welcome! What can I get for you today?',
                'color' => '#ef4444',
                'system_prompt' => "You are a fast-food cashier. Step 1: Reply to the customer in 1-2 English sentences. Include '[SUCCESS]' if they order a Spicy Chicken McCrispy Meal. Step 2: Evaluate the CUSTOMER'S English. If it is unnatural, append this at the very end: [SUGGESTION] [Write a better, native English way for the CUSTOMER to say their request. English only, no Chinese]."
            ],
            [
                'id' => 'supermarket',
                'title' => '🛒 Supermarket Checkout',
                'task' => '任務：回答不需要塑膠袋，並使用信用卡支付',
                'greeting' => 'Hello! Do you need a plastic bag for your items?',
                'color' => '#10b981',
                'system_prompt' => "You are a supermarket cashier. Step 1: Reply to the customer in 1-2 English sentences. Include '[SUCCESS]' if they say they don't need a bag and will pay by credit card. Step 2: Evaluate the CUSTOMER'S English. If it is unnatural, append this at the very end: [SUGGESTION] [Write a better, native English way for the CUSTOMER to say their request. English only, no Chinese]."
            ],
            [
                'id' => 'directions',
                'title' => '🗺️ Asking for Directions',
                'task' => '任務：向路人詢問最近的車站在哪裡',
                'greeting' => 'Excuse me, are you lost? Do you need some help?',
                'color' => '#3b82f6',
                'system_prompt' => "You are a helpful pedestrian. Step 1: Reply to the user in 1-2 English sentences. Include '[SUCCESS]' if they ask for directions to the station. Step 2: Evaluate the USER'S English. If it is unnatural, append this at the very end: [SUGGESTION] [Write a better, native English way for the USER to say their request. English only, no Chinese]."
            ],
            [
                'id' => 'immigration',
                'title' => '🛂 Immigration Officer',
                'task' => '任務：說明來這裡旅遊，預計停留 5 天',
                'greeting' => 'Next in line, please. Passport and purpose of your visit?',
                'color' => '#8b5cf6',
                'system_prompt' => "You are a strict immigration officer. Step 1: Reply to the user in 1-2 English sentences. Include '[SUCCESS]' if they state they are traveling and staying for 5 days. Step 2: Evaluate the USER'S English. If it is unnatural, append this at the very end: [SUGGESTION] [Write a better, native English way for the USER to say their request. English only, no Chinese]."
            ]
        ];

        foreach ($data as $item) {
            Scenario::updateOrCreate(['id' => $item['id']], $item);
        }
    }
}
