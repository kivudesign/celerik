<?php

declare(strict_types=1);

namespace Celerik\Ui\Tests\Adapters\Laravel;

use Illuminate\Database\Eloquent\Model;

final class Person extends Model
{
    public $timestamps = false;
    protected $table = 'people';
    protected $guarded = [];
    protected $hidden = ['password'];
}
