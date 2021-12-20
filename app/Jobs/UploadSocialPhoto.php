<?php

namespace App\Jobs;

use App\Models\User;
use App\Http\Controllers\API\ImageLoaderController;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UploadSocialPhoto implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private User $user;
    private string $photoURL;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(User $user, string $photoURL)
    {
        $this->user = $user;

        $this->photoURL = $photoURL;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $user = $this->user;

        $image = file_get_contents($this->photoURL);
        $image_base64 = base64_encode($image);
        $image_path = (new ImageLoaderController)->uploadImage4User($image_base64, $user->id);
        $user->photo = $image_path;
        $user->save();
    }
}
