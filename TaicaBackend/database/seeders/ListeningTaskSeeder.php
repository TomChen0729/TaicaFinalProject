<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\ListeningTask;
class ListeningTaskSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tasks = [
            // 1. 速食店
            [
                'id' => 'fast_food_pickup',
                'title' => '🍔 速食店取餐廣播',
                'script' => 'Order number 8, your spicy chicken combo is ready at the counter.',
                'question' => '請問廣播中提到哪一份餐點已經準備好了？',
                'options' => [
                    'A' => '一份起司漢堡',
                    'B' => '一份辣味炸雞套餐',
                    'C' => '一杯冰咖啡'
                ],
                'correct_answer' => 'B',
                'suggestion' => '聽懂點餐廣播是生存必備技能，"combo" (套餐) 這個字在日常生活中非常實用。'
            ],
            // 2. 車站
            [
                'id' => 'train_station',
                'title' => '🚆 車站月台廣播',
                'script' => 'Attention passengers, the train to London will depart from platform 3 in five minutes.',
                'question' => '請問前往倫敦的火車即將在第幾月台發車？',
                'options' => [
                    'A' => '第 3 月台',
                    'B' => '第 5 月台',
                    'C' => '第 1 月台'
                ],
                'correct_answer' => 'A',
                'suggestion' => '搭車必備聽力！"platform" 是月台，"depart" 是發車，抓準這兩個關鍵字就不會搭錯車。'
            ],
            // 3. 咖啡廳 (新)
            [
                'id' => 'coffee_shop_pickup',
                'title' => '☕ 咖啡廳取餐叫號',
                'script' => 'Large iced latte with oat milk for Tom! Your drink is ready at the pickup area.',
                'question' => '請問廣播中提到 Tom 的飲料加了哪一種奶類？',
                'options' => [
                    'A' => '全脂鮮牛奶',
                    'B' => '杏仁奶',
                    'C' => '燕麥奶'
                ],
                'correct_answer' => 'C',
                'suggestion' => '"Oat milk" 是燕麥奶，近年在國外咖啡廳非常流行。"pickup area" 則是取餐區。'
            ],
            // 4. 機場 (新)
            [
                'id' => 'airport_gate_change',
                'title' => '✈️ 機場登機門變更',
                'script' => 'Attention passengers for flight BR215 to Tokyo. The boarding gate has been changed from gate 12 to gate 25.',
                'question' => '請問前往東京的班機，登機門更改為幾號？',
                'options' => [
                    'A' => '12 號登機門',
                    'B' => '25 號登機門',
                    'C' => '15 號登機門'
                ],
                'correct_answer' => 'B',
                'suggestion' => '機場常見廣播！"boarding gate" 是登機門，聽到 "changed from A to B" 代表起初是 A 變更為 B。'
            ],
            // 5. 超市 (新)
            [
                'id' => 'supermarket_promotion',
                'title' => '🍓 超市限時促銷廣播',
                'script' => 'Special announcement! For the next thirty minutes, all fresh strawberries are buy one get one free.',
                'question' => '請問超市現在有什麼特別優惠？',
                'options' => [
                    'A' => '新鮮草莓買一送一',
                    'B' => '進口蘋果半價優惠',
                    'C' => '有機蔬菜打八折'
                ],
                'correct_answer' => 'A',
                'suggestion' => '"Buy one get one free" 是最標準的「買一送一」說法。"thirty minutes" 則是三十分鐘內。'
            ],
            // 6. 便利商店 (新)
            [
                'id' => 'convenience_store_checkout',
                'title' => '🏪 超商結帳對話詢問',
                'script' => 'That will be twelve dollars. Do you have a membership, or do you need a bag?',
                'question' => '店員在結帳告知金額後，詢問了什麼？',
                'options' => [
                    'A' => '是否需要列印收據',
                    'B' => '是否有會員或需要袋子',
                    'C' => '是否使用信用卡付款'
                ],
                'correct_answer' => 'B',
                'suggestion' => '超商生存必備聽力！"membership" 是會員身份，"bag" 在這裡通常指店內的塑膠提袋。'
            ],
            // 7. 飯店 (新)
            [
                'id' => 'hotel_breakfast_info',
                'title' => '🏨 飯店早餐用餐通知',
                'script' => 'Good morning guests, complimentary breakfast is served in the main dining room on the second floor until 10 AM.',
                'question' => '請問免費早餐在哪裡提供，且供應到幾點？',
                'options' => [
                    'A' => '一樓大廳，供應到 10 點',
                    'B' => '二樓主餐廳，供應到 10 點',
                    'C' => '三樓露天吧台，供應到 9 點'
                ],
                'correct_answer' => 'B',
                'suggestion' => '"Complimentary" 代表免費贈送的，飯店常用此字代替 free。"dining room" 是餐廳、用餐區。'
            ],
            // 8. 服飾店 (新)
            [
                'id' => 'clothing_store_discount',
                'title' => '🛍️ 服飾店促銷廣播',
                'script' => 'Welcome to Fashion Open! Today only, take an extra twenty percent off all summer shirts at checkout.',
                'question' => '請問今天服飾店針對夏日襯衫有什麼優惠？',
                'options' => [
                    'A' => '結帳享有再打八折優惠',
                    'B' => '第二件夏日襯衫打折 20 元',
                    'C' => '夏日襯衫買二送一'
                ],
                'correct_answer' => 'A',
                'suggestion' => '購物必學！"twenty percent off" 代表打八折（少 20% 的意思），"extra" 則是結帳再額外扣除。'
            ]
        ];

        foreach ($tasks as $task) {
            ListeningTask::updateOrCreate(['id' => $task['id']], $task);
        }
    }
}
