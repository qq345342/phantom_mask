<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use App\Models\Users;
use App\Models\UserPurchaseHistories;

class ImportUsersDataCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:import-users-data-command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $json_data = Storage::get('users.json');
        $datas = json_decode($json_data, true);

        foreach($datas as $data) {
            $dataExists = Users::where('name', $data['name'])->first();
            if(!$dataExists) {
                $user = Users::create([
                    'name'         => $data['name'],
                    'cash_balance' => $data['cashBalance']
                ]);
                foreach($data['purchaseHistories'] as $purchaseHistory) {
                    UserPurchaseHistories::create([
                        'user_id'            => $user->id,
                        'pharmacy_name'      => $purchaseHistory['pharmacyName'],
                        'mask_name'          => $purchaseHistory['maskName'],
                        'transaction_amount' => $purchaseHistory['transactionAmount'],
                        'transaction_date'   => $purchaseHistory['transactionDate']
                    ]);
                }
            }
        }
    }
}
