<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StudentBlockStatusChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $studentId;

    public $isBlocked;

    public $examGroupId;

    public $violationCount;

    /**
     * Create a new event instance.
     */
    public function __construct($studentId, $isBlocked, $examGroupId = null, $violationCount = 0)
    {
        $this->studentId = $studentId;
        $this->isBlocked = $isBlocked;
        $this->examGroupId = $examGroupId;
        $this->violationCount = $violationCount;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn()
    {
        // Channel publik 'student.{id}' — harus sama persis dengan deklarasi di
        // routes/channels.php dan channel yang didengarkan frontend (Show.vue).
        // Sebelumnya memakai 'student-status.{id}' yang tidak cocok, sehingga
        // event tidak pernah diterima browser (realtime mati, hanya bertahan
        // berkat fallback polling 3 detik).
        return new Channel('student.'.$this->studentId);
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs()
    {
        return 'StudentBlockStatusChanged';
    }
}
