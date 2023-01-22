<?php

namespace App\Jobs;

use App\Http\Controllers\BotController;
use App\Models\Student;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Milly\Laragram\Laragram;

class TaimerJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $message_id;
    private $chat_id;
    private $telegram_id;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($chat_id, $message_id, $telegram_id)
    {
        $this->chat_id = $chat_id;
        $this->message_id = $message_id;
        $this->telegram_id = $telegram_id;
    }

    /**
     * Execute the job.
     *
     * @return array
     */
    public function handle()
    {
        Laragram::deleteMessage([
            'chat_id' => $this->chat_id,
            'message_id' => $this->message_id
        ]);
        Laragram::sendMessage([
            'chat_id' => $this->chat_id,
            'text' => 'Taymer tugadi!'
        ]);
        return BotController::sendTest(Student::where('telegram_id', $this->telegram_id)->first());
    }
}
