<?php

namespace App;

use App\Barang;
use Illuminate\Database\Eloquent\Model;

class DetailPemesanan extends Model
{
    protected $table = 'detailpemesanans';
    protected $primaryKey = 'kode_detail_pemesanan';

    protected $fillable = [
        'kode_detail_pemesanan',
        'kode_pemesanan',
        'kode_barang',
        'jumlah_pemesanan',
        'jumlah_disetujui',
        'status'
    ];

    static protected $codePrefix = 'DP';
    
    public $incrementing = false;

    public function barang() {
        return $this->belongsTo(Barang::class, 'kode_barang');
    }

    public function pemesanan() {
        return $this->belongsTo(Pemesanan::class, 'kode_pemesanan');
    }

    public static function nextID() {
        // $data = self::latest()->first() ? (self::orderBy('kode_detail_pemesanan', 'desc')->latest()->first()) : self::$codePrefix . 0;
        // $data = str_replace(self::$codePrefix, '', $data);
        $data = self::all()->count();
        return self::$codePrefix . ($data + 1);
    }
}
