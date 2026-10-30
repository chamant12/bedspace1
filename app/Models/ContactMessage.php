<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// A message sent through the website's contact form
class ContactMessage extends Model
{
    use Concerns\NeverDeleted;

    protected $fillable = ['name', 'email', 'phone', 'subject', 'message', 'ip_address', 'status'];
}
