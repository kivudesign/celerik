<?php

declare(strict_types=1);

namespace Celerik\Ui\Tests\Adapters\Laravel;

use Illuminate\Database\Eloquent\Model;

final class Meeting extends Model
{
    public $timestamps = false;
    protected $table = 'meetings';
    protected $guarded = [];
    protected $casts = ['starts_at' => 'datetime', 'ends_at' => 'datetime', 'all_day' => 'boolean'];
}
