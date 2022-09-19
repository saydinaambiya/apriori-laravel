<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\AprioriImport;

class AprioriController extends Controller
{
    public function getData(Request $request){
        function frekuensiItem($data)
        {
            $arr = [];
            for ($i = 0; $i < count($data); $i++) {
                $jum = array_count_values($data[$i]);
                foreach ($jum as $key => $v) {
                    if (array_key_exists($key, $arr)) {
                        $arr[$key] += 1;
                    } else {
                        $arr[$key] = 1;
                    }
                }
            }
            return $arr;
        }

        function eliminasiItem($data, $minSupport)
        {
            $arr = [];
            foreach ($data as $key => $v) {
                if ($v >= $minSupport) {
                    $arr[$key] = $v;
                }
            }
            return $arr;
        }
        function pasanganItem($data_filter)
        {
            $n = 0;
            $arr = [];
            foreach ($data_filter as $key1 => $v1) {
                $m = 1;
                foreach ($data_filter as $key2 => $v2) {
                    $str = explode("_", $key2);
                    for ($i = 0; $i < count($str); $i++) {

                        if (!strstr($key1, $str[$i])) {
                            if ($m > $n + 1 && count($data_filter) > $n + 1) {
                                $arr[$key1 . "_" . $str[$i]] = 0;
                            }
                        }
                    }
                    $m++;
                }
                $n++;
            }
            return $arr;
        }

        function frekuensiPasanganItem($data_pasangan, $data)
        {
            $arr = $data_pasangan;
            $ky = "";
            $kali = 0;
            foreach ($data_pasangan as $key1 => $k) {
                for ($i = 0; $i < count($data); $i++) {
                    $kk = explode("_", $key1);
                    $jm = 0;
                    for ($k = 0; $k < count($kk); $k++) {

                        for ($j = 0; $j < count($data[$i]); $j++) {
                            if ($data[$i][$j] == $kk[$k]) {
                                $jm += 1;
                                break;
                            }
                        }
                    }
                    if ($jm > count($kk) - 1) {
                        $arr[$key1] += 1;
                    }
                }
            }
            return $arr;
        }
        $rows = Excel::toArray(new AprioriImport,$request->file);
        $data_item = $rows[0];

        $minSupport = 3;
        $arr = [];
        for ($i = 0; $i < count($data_item); $i++) {
            $ar = [];
            $val = explode(",", $data_item[$i]["item"]);
            for ($j = 0; $j < count($val); $j++) {
                $ar[] = $val[$j];
            }
            array_push($arr, $ar);
        }

        $frekuensi_item = frekuensiItem($arr);
        $dataEliminasi = eliminasiItem($frekuensi_item, $minSupport);

        // print_r($dataEliminasi);

        do {
            $pasangan_item = pasanganItem($dataEliminasi);
            $frekuensi_item = FrekuensiPasanganItem($pasangan_item, $arr);

            foreach ($frekuensi_item as $key => $val) {

                $ex = explode("_", $key);
                $item = "";
                $vl = "";
                for ($k = 0; $k < count($ex); $k++) {
                    if ($k !== count($ex) - 1) {
                        $item .= "," . $ex[$k];
                    } else {
                        $vl = $ex[$k];
                    }
                }
                $aturan_asosiasi[] = array("item" => substr($item, 1), "val" => $vl, "sc" => $val);
            }
            $dataEliminasi = eliminasiItem($frekuensi_item, $minSupport);
        } while ($dataEliminasi == $frekuensi_item);

        $data2= [];
        for ($i = 0; $i < count($aturan_asosiasi); $i++) {
            $x = 0;
            // $i++;
            // echo $i + 1 . " Nilai confident, ";
            // echo $aturan_asosiasi[$i]["item"] . " => " . $aturan_asosiasi[$i]["val"] . "=";
            $ex = explode(",", $aturan_asosiasi[$i]["item"]);

            for ($l = 0; $l < count($arr); $l++) {
                $jum = 0;
                for ($k = 0; $k < count($ex); $k++) {

                    for ($j = 0; $j < count($arr[$l]); $j++) {
                        if ($arr[$l][$j] == $ex[$k]) {
                            $jum += 1;
                        }
                    }
                }
                if (count($ex) == $jum) {
                    $x += 1;
                }
            }
            $convident = (floatval($aturan_asosiasi[$i]["sc"]) / floatval($x)) * 100;
            $aturan_asosiasi[$i]["c"] = number_format($convident, 2, ".", ",");
            // $data2[$i] = $aturan_asosiasi[$i]["sc"] . "/" . $x . "=" . number_format(floatval($aturan_asosiasi[$i]["sc"]) / floatval($x), 2, ".", ",") . "=" . number_format($convident, 0, ".", ",") . "%";
            // echo  "<br>";

            $kirim = [];

            for ($i = 0; $i < count($aturan_asosiasi); $i++) {
                $x = 0;
                // array_push($kirim,$aturan_asosiasi[$i]["item"]);
                // array_push($kirim,$aturan_asosiasi[$i]["val"]);
                // $kirim[$i]=$aturan_asosiasi[$i]["item"].'maka'.$aturan_asosiasi[$i]["val"];
                $kirim[$i]=$aturan_asosiasi[$i];

            }

        if($data_item){
            return ResponseFormatter::success($kirim,'Data Berhasil Diambil');
        }else{
            return ResponseFormatter::error(null,'Data Kosong',404);
        }
    }
}
}
