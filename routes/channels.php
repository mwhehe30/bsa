<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Public channel for student block status updates (no authentication required)
Broadcast::channel('student.{id}', function () {
    return true;
});
