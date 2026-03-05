<?php

namespace Cesargb\KeyValueStore\Models;

use Illuminate\Database\Eloquent\Model;

class KeyValue extends Model
{
    /** @var bool */
    public $timestamps = false;

    /** @var bool */
    public $incrementing = false;

    /** @var string */
    protected $table = 'key_value_store';

    /** @var string */
    protected $primaryKey = 'key';

    /** @var string */
    protected $keyType = 'string';

    /** @var list<string> */
    protected $fillable = ['key', 'value'];
}
