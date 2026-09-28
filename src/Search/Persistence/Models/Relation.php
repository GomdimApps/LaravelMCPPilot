<?php

namespace GomdimApps\LaravelMCPPilot\Search\Persistence\Models;

use GomdimApps\LaravelMCPPilot\Search\Persistence\SearchConnection;
use Illuminate\Database\Eloquent\Model;

/** One directed graph edge: an entry pointing at another entry (or at an unresolved symbol). */
class Relation extends Model
{
    protected $table = 'search_relations';

    protected $connection = SearchConnection::NAME;

    public $timestamps = false;

    protected $guarded = [];
}
