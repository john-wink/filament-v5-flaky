<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(
    Tests\TestCase::class,
    LazilyRefreshDatabase::class,
)->in('Feature');
