<?php

namespace App\Console\Commands;

use App\Models\Answer;
use App\Models\Response;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class RemoveVideos extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clear:videos';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear all old videos';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $days = env('CLEAR_VIDEOS_DAYS', 60);

        $answers = Answer::where('created_at', '<', Carbon::now()->subDays($days))->get();

        foreach ($answers as $answer) {
            if ($answer->response && $answer->response->agency_id != 45 && $answer->response->agency_id != 486) {
                if (Storage::disk(env('FILESYSTEM_DRIVER'))->exists($answer->video)) {
                    Storage::disk(env('FILESYSTEM_DRIVER'))->delete($answer->video);
                }
                if (Storage::disk(env('FILESYSTEM_DRIVER'))->exists($answer->video_thumb)) {
                    Storage::disk(env('FILESYSTEM_DRIVER'))->delete($answer->video_thumb);
                }
                if (Storage::disk(env('FILESYSTEM_DRIVER'))->exists($answer->video_gif)) {
                    Storage::disk(env('FILESYSTEM_DRIVER'))->delete($answer->video_gif);
                }
                if (Storage::disk(env('FILESYSTEM_DRIVER'))->exists($answer->video_transcoded)) {
                    Storage::disk(env('FILESYSTEM_DRIVER'))->delete($answer->video_transcoded);
                }
            }
        }

        $responses = Response::where('created_at', '<', Carbon::now()->subDays($days))->get();

        foreach ($responses as $response) {
            if($response->agency_id != 45 && $response->agency_id != 486) {
                if (Storage::disk(env('FILESYSTEM_DRIVER'))->exists($response->default_cv)) {
                    Storage::disk(env('FILESYSTEM_DRIVER'))->delete($response->default_cv);
                }
                if (Storage::disk(env('FILESYSTEM_DRIVER'))->exists($response->ask_motivation_letter)) {
                    Storage::disk(env('FILESYSTEM_DRIVER'))->delete($response->ask_motivation_letter);
                }
                if (Storage::disk(env('FILESYSTEM_DRIVER'))->exists($response->ask_cv)) {
                    Storage::disk(env('FILESYSTEM_DRIVER'))->delete($response->ask_cv);
                }
            }
        }
    }
}
