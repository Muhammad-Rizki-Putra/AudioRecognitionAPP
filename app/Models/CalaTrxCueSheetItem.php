<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CalaTrxCueSheetItem extends Model
{
    protected $table = 'cala_trx_cuesheetitem';
    protected $primaryKey = ['szcuesheetid', 'shitem'];
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'szcuesheetid',
        'szfileid',
        'shitem',
        'szswc',
        'szisrc',
        'songid',
        'szsongtitle',
        'szcomposerid',
        'szcomposername',
        'szartistid',
        'szartistname,',
        'dtmstarttime',
        'dtmendtime',
        'dtmduration',
        'rn_songsong_id'
    ];

    public static function find($ids, $columns = ['*'])
    {
        // Convert array of IDs to where clauses
        $query = static::where($ids);
        return $query->first($columns);
    }

    protected function setKeysForSaveQuery($query)
    {
        foreach ($this->primaryKey as $key) {
            $query->where($key, '=', $this->getAttribute($key));
        }
        return $query;
    }

    public function file()
    {
        return $this->belongsTo(CalaTrxFile::class, 'szfileid', 'szfileid');
    }

    public function cueSheet()
    {
        return $this->belongsTo(CalaTrxCueSheet::class, 'szcuesheetid', 'szcuesheetid');
    }
}
