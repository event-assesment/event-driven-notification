<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('notifications:mark-unknown')->hourly();
