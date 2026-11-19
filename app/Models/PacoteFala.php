<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Model;

#[Table(name: 'pacotes_fala', timestamps: false)]
#[Unguarded]
class PacoteFala extends Model {}
