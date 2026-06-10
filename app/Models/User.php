<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use App\Models\Role;
use App\Models\Permiso;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    public function role()
    {
    return $this->belongsTo(Role::class, 'rol_id');
    }

    public function permisos()
    {
    if (!$this->role) {
        return collect();
    }

    return $this->role->permisos;
    }

    public function tienePermiso(string $permiso): bool
    {
    if (!$this->role) {
        return false;
    }

    return $this->role->permisos
        ->contains('nombre', $permiso);
    }
    
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'rol_id',
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
    return [
        'estado' => 'boolean',
    ];
    }
}
