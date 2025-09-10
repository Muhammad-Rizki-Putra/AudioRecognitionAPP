<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CalaTrxFile extends Model
{
    // If the table name is not 'cala_trx_files', you must specify it.
    protected $table = 'cala_trx_file';

    // The columns that can be mass assigned.
    protected $fillable = [
        'szfileid',
        'szfilename',
        'szurl',
        'szformat',
        'dtmduration',
        'decsizemb',
        'szdescription',
        'szstatus',
        'bactive',
        'szcreatedby',
        'szupdatedby',
    ];
}
