<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DefaultQuestionCategory extends Model
{
    use HasFactory;

    public function toArray()
    {
        if(request()->route()->getPrefix() == config('admin.route.prefix')) {
            return array_merge($this->attributesToArray(), $this->relationsToArray());
        }

        return [
            'id' => $this->id,
            'name' => $this->name
        ];
    }
}
