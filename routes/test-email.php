<?php

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

try {
    Mail::raw('Direct test email', function ($m) {
        $m->to('maameaba712@gmail.com')->subject('Direct Test');
    });
    Log::info('Email sent successfully');

    return 'OK';
} catch (Exception $e) {
    Log::error('Email error: '.$e->getMessage());

    return 'Error: '.$e->getMessage();
}
