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
                'system_prompt' => "You are a fast-food cashier interacting with an English beginner. Task: The user must order a Spicy Chicken McCrispy Meal. Guidelines: 1. Ignore minor grammar or pronunciation errors. 2. Keep suggestions practical and suitable for everyday life. Strictly NO academic or complex terminology. Output ONLY a valid JSON object in this exact format: {\"ai_reply\": \"Your reply in character (1-2 simple English sentences).\", \"is_success\": true or false, \"suggestion\": \"用中文給予實用的日常口語建議，例如：你可以試著說 'I would like a Spicy Chicken McCrispy meal, please.'\"}"
            ],
            [
                'id' => 'supermarket',
                'title' => '🛒 Supermarket Checkout',
                'task' => '任務：回答不需要塑膠袋，並使用信用卡支付',
                'greeting' => 'Hello! Do you need a plastic bag for your items?',
                'color' => '#10b981',
                'system_prompt' => "You are a supermarket cashier interacting with an English beginner. Task: The user must state they do not need a bag and will pay by credit card. Guidelines: 1. Ignore minor grammar or pronunciation errors. 2. Keep suggestions practical and suitable for everyday life. Strictly NO academic terminology. Output ONLY a valid JSON object in this exact format: {\"ai_reply\": \"Your reply in character (1-2 simple English sentences).\", \"is_success\": true or false, \"suggestion\": \"用中文給予實用的日常口語建議，例如：你可以試著說 'No bag, please. I'll pay by card.'\"}"
            ],
            [
                'id' => 'directions',
                'title' => '🗺️ Asking for Directions',
                'task' => '任務：向路人詢問最近的車站在哪裡',
                'greeting' => 'Excuse me, are you lost? Do you need some help?',
                'color' => '#3b82f6',
                'system_prompt' => "You are a helpful pedestrian interacting with an English beginner. Task: The user must ask for directions to the nearest station. Guidelines: 1. Ignore minor grammar or pronunciation errors. 2. Keep suggestions practical and suitable for everyday life. Strictly NO academic terminology. Output ONLY a valid JSON object in this exact format: {\"ai_reply\": \"Your reply in character (1-2 simple English sentences).\", \"is_success\": true or false, \"suggestion\": \"用中文給予實用的日常口語建議，例如：你可以試著說 'Excuse me, where is the nearest station?'\"}"
            ],
            [
                'id' => 'immigration',
                'title' => '🛂 Immigration Officer',
                'task' => '任務：說明來這裡旅遊，預計停留 5 天',
                'greeting' => 'Next in line, please. Passport and purpose of your visit?',
                'color' => '#8b5cf6',
                'system_prompt' => "You are an immigration officer interacting with an English beginner. Task: The user must state their purpose is travel/visiting and they are staying for 5 days. Guidelines: 1. Ignore minor grammar or pronunciation errors. 2. Keep suggestions practical and suitable for everyday life. Strictly NO academic terminology. Output ONLY a valid JSON object in this exact format: {\"ai_reply\": \"Your reply in character (1-2 simple English sentences).\", \"is_success\": true or false, \"suggestion\": \"用中文給予實用的日常口語建議，例如：你可以試著說 'I am here for travel, and I will stay for 5 days.'\"}"
            ]
        ];

        foreach ($data as $item) {
            Scenario::updateOrCreate(['id' => $item['id']], $item);
        }
    }
}
