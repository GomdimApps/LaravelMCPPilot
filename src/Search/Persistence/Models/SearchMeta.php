<?php

namespace GomdimApps\LaravelMCPPilot\Search\Persistence\Models;

use GomdimApps\LaravelMCPPilot\Search\Persistence\SearchConnection;
use Illuminate\Database\Eloquent\Model;

/** Single-row table (id = 1) holding the index's generated_at timestamp. */
class SearchMeta extends Model
{
    protected $table = 'search_meta';

    protected $connection = SearchConnection::NAME;

    public $timestamps = false;

    public $incrementing = false;

    protected $keyType = 'int';

    protected $guarded = [];
}
