<?php

namespace App\Message;

class SendScheduledEmail
{
    public function __construct( public int $scheduledEmailID) {}
}