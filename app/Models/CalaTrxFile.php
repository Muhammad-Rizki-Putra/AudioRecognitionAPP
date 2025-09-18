<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CalaTrxFile extends Model
{
    // The table associated with the model.
    protected $table = 'cala_trx_file';

    // Make sure timestamps are enabled
    public $timestamps = true;

    // Define custom timestamp column names to match Supabase
    const CREATED_AT = 'dtmcreated';
    const UPDATED_AT = 'dtmupdated';
    protected $primaryKey = 'szfileid';
    public $incrementing = false;
    protected $keyType = 'string';

    // The columns that can be mass assigned.
    protected $fillable = [
        'szfileid',
        'szfilename',
        'szurl',
        'szformat',
        'dtmduration',
        'decsizemb',
        'szdescription', // This must be a number
        'szstatus',
        'bactive',
        'szcreatedby',
        'szupdateby'
    ];

    public function cueSheet(){
        return $this->hasMany(CalaTrxCueSheet::class, 'szfileid','szfileid');
    }
}
