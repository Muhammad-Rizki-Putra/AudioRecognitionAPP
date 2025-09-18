<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CalaTrxCueSheet extends Model
{
    protected $table = 'cala_trx_cuesheet';
    protected $primaryKey = 'szcuesheetid';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'szcuesheetid',
        'szfileid',
        'dectotalsongs',
        'dtmtotalduration',
        'bactive',
        'szcreatedby',
        'szupdateby',
        'dtmcreated',
        'dtmupdated'
    ];

    public function file(){
        return $this->belongsTo(CalaTrxFile::class,'szfileid','szfileid');
    }
}
