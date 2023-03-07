<?php

namespace App\Jobs;

use App\Services\MtaService;
use App\Models\Division;
use App\Models\Feed;
use App;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RemoveFeedsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(): void
    {
        logger()->info(
            'Executing RemoveFeedsJob.'
        );

        $feeds = Feed::where('created_at', '<', now()->subMinutes(30))->get();

        foreach ($feeds as $feed) {
            unlink($feed->path);
            $feed->delete();
        }
    }
}
