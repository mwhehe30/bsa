<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
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
        return new PrivateChannel('student-status.' . $this->studentId);
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs()
    {
        return 'StudentBlockStatusChanged';
    }
}
