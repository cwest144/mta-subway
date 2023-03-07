<?php

namespace App\Console\Commands;

use App\Models\Division;
use App\Services\MtaService;
use App;
use Illuminate\Console\Command;

class RefreshFeeds extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'feeds:refresh';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch new feeds from MTA for all endpoints';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        logger()->info(
            'Executing RefreshFeedsJob.'
        );

        $divs = Division::all();

        $service = App::make(MtaService::class);

        foreach ($divs as $div) {
            $service->callMta($div);
        }

        return Command::SUCCESS;
    }
}
