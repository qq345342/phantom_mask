<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use App\Models\Pharmacies;
use App\Models\PharmacyOpeningHours;
use App\Models\PharmacyMasks;

class ImportPharmaciesDataCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:import-pharmacies-data-command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import pharmacies data from json.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $json_data = Storage::get('pharmacies.json');
        $datas = json_decode($json_data, true);

        if($datas) foreach($datas as $data) {
            $dataExists = Pharmacies::where("name", $data['name'])->first();
            if(!$dataExists) {
                $pharmacy = Pharmacies::create([
                    'name'         => $data['name'],
                    'cash_balance' => $data['cashBalance']
                ]);

                $opening_hours = $this->parseOpeningHours($data['openingHours']);
                foreach($opening_hours as $opening_hour) {
                    PharmacyOpeningHours::create([
                        'pharmacy_id' => $pharmacy->id,
                        'week'       => $opening_hour['week'],
                        'start_time' => $opening_hour['start_time'],
                        'end_time'   => $opening_hour['end_time']
                    ]);
                }

                foreach($data['masks'] as $mask) {
                    PharmacyMasks::create([
                        'pharmacy_id' => $pharmacy->id,
                        'mask_name'   => $mask['name'],
                        'mask_price'  => $mask['price']
                    ]);
                }
            }
        }
    }
    private function parseOpeningHours($openingHours)
    {
        $timeSlots = explode(" / ", $openingHours);
        $daysOfWeek = ["Mon", "Tue", "Wed", "Thu", "Fri", "Sat", "Sun"];
        $result = [];

        foreach ($timeSlots as $slot) {
            preg_match('/([A-Za-z,\- ]+)\s+(\d{2}:\d{2})\s*-\s*(\d{2}:\d{2})/', $slot, $matches);
            if ($matches) {
                $dayPart = trim($matches[1]);
                $startTime = $matches[2];
                $endTime = $matches[3];

                $days = [];

                if (strpos($dayPart, '-') !== false) {
                    [$startDay, $endDay] = array_map('trim', explode('-', $dayPart));

                    $startIndex = array_search($startDay, $daysOfWeek);
                    $endIndex = array_search($endDay, $daysOfWeek);

                    if ($startIndex !== false && $endIndex !== false) {
                        $days = array_slice($daysOfWeek, $startIndex, $endIndex - $startIndex + 1);
                    }
                } else {
                    $days = array_map('trim', explode(',', $dayPart));
                }

                foreach ($days as $day) {
                    $result[] = [
                        "week" => $day,
                        "start_time" => $startTime,
                        "end_time" => $endTime
                    ];
                }
            }
        }

        return $result;
    }
}
