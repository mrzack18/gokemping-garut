<?php

namespace App\Support;

use OpenSpout\Writer\XLSX\Writer;
use RuntimeException;

/**
 * Penulisan berkas Excel untuk ekspor admin (ROADMAP 5.2).
 *
 * OpenSpout hanya bisa menulis ke berkas atau langsung ke browser, jadi
 * helper ini menulisnya ke berkas sementara lalu membaca isinya kembali.
 * Dengan begitu controller bisa mengembalikan response yang seragam dan test
 * bisa membaca berkasnya seperti berkas Excel biasa.
 *
 * Berkas sementara selalu dihapus di blok `finally`, termasuk kalau penulisan
 * gagal, supaya folder temp tidak menumpuk sisa ekspor yang tidak pernah
 * terpakai.
 */
final class Spreadsheets
{
    /**
     * Isi berkas XLSX sebagai string biner.
     *
     * @param  callable(Writer): void  $fill
     *
     * @throws RuntimeException kalau berkas sementara tidak bisa dibuat atau dibaca
     */
    public static function bytes(callable $fill): string
    {
        $path = tempnam(sys_get_temp_dir(), 'xlsx');

        if ($path === false) {
            throw new RuntimeException('Berkas sementara untuk ekspor gagal dibuat.');
        }

        try {
            $writer = new Writer;
            $writer->openToFile($path);
            $fill($writer);
            $writer->close();

            $contents = file_get_contents($path);

            if ($contents === false) {
                throw new RuntimeException('Isi berkas ekspor gagal dibaca.');
            }

            return $contents;
        } finally {
            @unlink($path);
        }
    }
}
