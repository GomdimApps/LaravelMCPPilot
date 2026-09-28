<?php

namespace GomdimApps\LaravelMCPPilot\Search\Persistence\Models;

use GomdimApps\LaravelMCPPilot\Search\Persistence\SearchConnection;
use Illuminate\Database\Eloquent\Model;

/** Postings table: one row per (term, entry_id) pair. Only ever used through the query builder. */
class Term extends Model
{
    protected $table = 'search_terms';

    protected $connection = SearchConnection::NAME;

    public $timestamps = false;

    public $incrementing = false;

    protected $guarded = [];
}
