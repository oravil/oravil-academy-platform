<?php

namespace App\Models;

use Database\Factories\LearnerFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;

/** @property Carbon $enrolled_at */
class Learner extends Authenticatable
{
    /** @use HasFactory<LearnerFactory> */
    use HasFactory, HasUuids, Notifiable;

    public $timestamps = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'email',
        'display_name',
        'password_hash',
        'enrolled_at',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password_hash',
    ];

    public function getAuthPasswordName(): string
    {
        return 'password_hash';
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'enrolled_at' => 'datetime',
            'password_hash' => 'hashed',
        ];
    }
}
