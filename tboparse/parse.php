<?php
function carirules($arr, $terminal)
{
    $bandingin = "";
    global $adaobjek;
    global $adasubjek;
    foreach ($arr as $h => $key) {
        if ($key['terminal'] == $terminal) {
            if (empty($bandingin)) $bandingin = $key['rule'];
            else 
            if (!$adasubjek) $bandingin = $key['rule'];
            elseif (!$adaobjek && $adasubjek) $bandingin = $key['rule'];
            if (strcmp($key['rule'], "O") == 0) {
                $adaobjek = true;
            }
            if (strcmp($key['rule'], "S") == 0) {
                $adasubjek = true;
                break;
            }
        }
    }

    return $bandingin;
}
$adaobjek = false;
$adasubjek = false;
//koneksi database
$db = new mysqli('localhost', 'root', '', 'text_search');
$hasil = $db->query("SELECT * FROM cnf")->fetch_all(MYSQLI_ASSOC);


$string = $_GET['cari'];
//variabel yg dibutuhkan
$k = explode(" ", $string);
$lwt = -1; //lwt ni jika k (string) digabung. msl: Luh Sari
$z = -1; //variable increment index w
$x = 0; //variable increment index kal
$tandai = -2; //tandai ni jika w sebelumnya dah ada pasangan
$zx = 0; ////variable increment index blmjodoh
$ww = 0; ////variable increment index untuk concat kata
for ($i = 0; $i < count($k); $i++) {
    // if ($i == $lwt) continue;
    $tmpk = $i;
    $c = carirules($hasil, $k[$i]);
    //kalau satu kata gak ada (gabungin kata), kan ada yg dua kata tu
    if (!$c) {
        if ($ww == 0) $ww = $i + 1;
        else $ww++;
        if ($ww >= count($k)) break;
        $k[$i] .= " " . $k[$ww];
        // $lwt = $i + 1;
        $i--;
        continue;
    }
    //ngecek variabel w yaitu terminal dari stringnya
    if ($c) {
        $w[++$z] = $c;
        $s[$z] = $k[$i];
        if ($ww > 0) $i = $ww;
        $ww = 0;
    }
    if ($z <= 0) {
        $blmjodoh[$zx++] = [$z => $w[$z]];
        continue; //kalau masih di awal ga bisa dibandingin
    }
    if ($tandai != $tmpk) {
        //ngecek sama sebelumnya
        $gb = $w[$z - 1] . " " . $w[$z];
        $c = carirules($hasil, $gb);
        if ($c) {
            $kal[$x++] = [$z - 1 . "-" . $z => $c];
            $tandai = $i + 1;
            if (isset($blmjodoh) && !empty($blmjodoh))
                unset($blmjodoh[--$zx]);
            continue;
        } else if (isset($kal)) $blmjodoh[$zx] = [$z => $w[$z]];
        else $blmjodoh[$zx++] = [$z => $w[$z]];
    }

    //cek antar kal dan w
    if (isset($kal)) {
        $tmp = array_values($kal[$x - 1]);
        $gb = $tmp[0] . " " . $w[$z];
        $c = carirules($hasil, $gb);
        if ($c) {
            $tx = $x - 1;
            $kal[$x++] = ["kal" . $tx . "-" . $z => $c];
            $tandai = $i + 1;
            if (isset($blmjodoh) && !empty($blmjodoh))
                unset($blmjodoh[--$zx]);
            continue;
        } else $blmjodoh[$zx++] = [$z => $w[$z]];
    }
}

if (isset($w)) {
    if (isset($kal))
        for ($i = count($kal) - 1; $i >= 0; $i--) {
            //cek antar kal dan blmjodoh
            if (isset($blmjodoh) && !empty($blmjodoh)) {
                for ($j = count($blmjodoh) - 1; $j >= 0; $j--) {
                    $tmp = array_values($blmjodoh[$j]);
                    $huh = key($blmjodoh[$j]);
                    $huh++;
                    $ind = (string)$huh;
                    for ($o = 0; $o < count($kal); $o++)
                        if (strpos(key($kal[$o]), $ind) !== false)
                            $ind = $o;
                    /*logikanya di blmjodoh tu ada index sm value terminalnya ex:(0 => Pn). trus di kal ada key index nya
                misal (1-2 => P). nah dia nyari index value setelah blmjodoh, misal 0 setelahnya 1 maka dia cari yg 1-...
                itu yang akan dibandingin klo blmjodoh nyimpen index 2 maka dia carai 3-... di variable kal */
                    $ttmp = array_values($kal[$ind]);
                    $gb = $tmp[0] . " " . $ttmp[0];
                    $c = carirules($hasil, $gb);
                    if ($c) {
                        $kal[$x++] = [key($blmjodoh[$j]) . "-" . "kal" . $ind => $c];
                        $tandai = $i;
                        $i += 2;
                        unset($blmjodoh[$j]);
                        continue;
                    }
                }
            } else {
                //cek antar kal, 
                if ($i - 1 < 0 || $i - 2 < 0) continue;
                if (($i - 1) == $tandai) $cekk = $i - 2;
                else $cekk = $i - 1;
                $tmp = array_values($kal[$cekk]);
                $ttmp = array_values($kal[$i]);
                $gb = $tmp[0] . " " . $ttmp[0];
                $c = carirules($hasil, $gb);
                if ($c) {
                    $kal[$x++] = ["kal" . $cekk . "-" . "kal" . $i => $c];
                }
            }
        }
    $ket = "";
    $subjek = "";
    $objek = "";
    $predikat = "";
    $pelengkap = "";
    $znfc = "";
    //dari sini kebawah, buat nyari CNF yang dipakai sama nyari S P O K
    if (isset($kal))
        foreach (array_reverse($kal) as $rule) {
            $kan = "";
            $tmp = explode("-", key($rule));
            foreach ($tmp as $tt) {
                if (strpos($tt, "kal") !== false) {
                    $p = explode("kal", $tt);
                    $yeah = array_values($kal[$p[1]]);
                    $kan .= $yeah[0] . " ";
                } else {
                    $kan .= $w[$tt] . " ";
                }
            }
            $znfc .= $rule[key($rule)] . " => " . $kan . "<br>";
            if (empty($ket))
                if (strcmp($rule[key($rule)], "Ket") == 0) {
                    $tmp = explode("-", key($rule));
                    foreach ($tmp as $tt) {
                        if (strpos($tt, "kal") !== false) {
                            $p = explode("kal", $tt);
                            $y = key($kal[$p[1]]);
                            $ttmp = explode("-", $y);
                            foreach ($ttmp as $t) {
                                $ket .= $s[$t] .  " ";
                            }
                        } else {
                            $ket .= $s[$tt] . " ";
                        }
                    }
                }
            if (empty($subjek))
                if (strcmp($rule[key($rule)], "S") == 0) {
                    $tmp = explode("-", key($rule));
                    foreach ($tmp as $tt) {
                        if (strpos($tt, "kal") !== false) {
                            $p = explode("kal", $tt);
                            $y = key($kal[$p[1]]);
                            $ttmp = explode("-", $y);
                            foreach ($ttmp as $t) {
                                $subjek .= $s[$t] .  " ";
                            }
                        } else {
                            $subjek .= $s[$tt] . " ";
                        }
                    }
                }
            if (empty($predikat))
                if (strcmp($rule[key($rule)], "P") == 0) {
                    $tmp = explode("-", key($rule));
                    foreach ($tmp as $tt) {
                        if (strpos($tt, "kal") !== false) {
                            $p = explode("kal", $tt);
                            $y = key($kal[$p[1]]);
                            $ttmp = explode("-", $y);
                            foreach ($ttmp as $t) {
                                $predikat .= $s[$t] .  " ";
                            }
                        } else {
                            $predikat .= $s[$tt] . " ";
                        }
                    }
                }
            if (empty($objek))
                if (strcmp($rule[key($rule)], "O") == 0) {
                    $tmp = explode("-", key($rule));
                    foreach ($tmp as $tt) {
                        if (strpos($tt, "kal") !== false) {
                            $p = explode("kal", $tt);
                            $y = key($kal[$p[1]]);
                            $ttmp = explode("-", $y);
                            foreach ($ttmp as $t) {
                                $objek .= $s[$t] .  " ";
                            }
                        } else {
                            $objek .= $s[$tt] . " ";
                        }
                    }
                }
            if (empty($pelengkap))
                if (strcmp($rule[key($rule)], "Pel") == 0) {
                    $tmp = explode("-", key($rule));
                    foreach ($tmp as $tt) {
                        if (strpos($tt, "kal") !== false) {
                            $p = explode("kal", $tt);
                            $y = key($kal[$p[1]]);
                            $ttmp = explode("-", $y);
                            foreach ($ttmp as $t) {
                                $pelengkap .= $s[$t] .  " ";
                            }
                        } else {
                            $pelengkap .= $s[$tt] . " ";
                        }
                    }
                }
        }
    foreach (array_combine($w, $s) as $one => $str) {

        $znfc .= $one . " => " . $str . "<br>";
    }
    if (empty($subjek)) {
        for ($i = 0; $i < count($w); $i++) {
            if (strcmp($w[$i], "S") == 0) {
                $subjek = $s[$i];
            }
        }
    }
    if (empty($predikat)) {
        for ($i = 0; $i < count($w); $i++) {
            if (strcmp($w[$i], "P") == 0) {
                $predikat = $s[$i];
            }
        }
    }
    if (empty($objek)) {
        for ($i = 0; $i < count($w); $i++) {
            if (strcmp($w[$i], "O") == 0) {
                $objek = $s[$i];
            }
        }
    }
    if (empty($ket)) {
        for ($i = 0; $i < count($w); $i++) {
            if (strcmp($w[$i], "Ket") == 0) {
                $ket = $s[$i];
            }
        }
    }
    if (empty($pelengkap)) {
        for ($i = 0; $i < count($w); $i++) {
            if (strcmp($w[$i], "Pel") == 0) {
                $pelengkap = $s[$i];
            }
        }
    }
    $valid = true;
    if (!empty($subjek) && empty($predikat) && empty($objek) && empty($ket))
        $valid = false;
    if (!empty($predikat) && empty($subjek) && empty($objek) && empty($ket))
        $valid = false;
    if (!empty($objek) && empty($predikat) && empty($subjek) && empty($ket))
        $valid = false;
    if (!empty($ket) && empty($predikat) && empty($objek) && empty($subjek))
        $valid = false;
} else $valid = false;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Noogle</title>
    <link rel="stylesheet" href="css/bootstrap.css">
</head>

<body>
    <nav class="navbar fixed-top navbar-light bg-light">
        <a class="navbar-brand" href="index.php"><img src="logo.png" width="120"></a>
        <ul class="navbar-nav mr-auto">
            <li class="nav-item">
                <div class="justify-content-md-center">
                    <form class="form-inline my-2 my-lg-0 pencarian" action="" method="get">
                        <input type="text" name="cari" class="form-control mr-sm-2" style="width: 600px;" value="<?= $_GET['cari'] ?>" required>
                        <button class="btn btn-info my-2 my-sm-0" type="submit">Search</button>
                    </form>
                </div>
            </li>
        </ul>
    </nav>
    <div class="container" style="margin-top: 75px;">

        <div class="card mb-2">
            <div class="card-body">
                <h3 class="card-title"><?= $string ?></h3>
                <?php if (!$valid) : ?>
                    <h4 class="card-subtitle mb-2"><span class="badge badge-danger">Kalimat tidak valid</span></h4>
                <?php else : ?>
                    <h4 class="card-subtitle mb-2"><span class="badge badge-success">Kalimat Valid</span></h4>
                    <?php if (!empty($subjek)) : ?>
                        <h5 class="card-text text-success d-inline mr-2">S(<?= trim($subjek) ?>) </h5>
                    <?php endif; ?>
                    <?php if (!empty($predikat)) : ?>
                        <h5 class="card-text text-primary d-inline mr-2">P(<?= trim($predikat) ?>) </h5>
                    <?php endif; ?>
                    <?php if (!empty($objek)) : ?>
                        <h5 class="card-text text-info d-inline mr-2">O(<?= trim($objek) ?>) </h5>
                    <?php endif; ?>
                    <?php if (!empty($pelengkap)) : ?>
                        <h5 class="card-text text-warning d-inline mr-2">Pel(<?= trim($pelengkap) ?>) </h5>
                    <?php endif; ?>
                    <?php if (!empty($ket)) : ?>
                        <h5 class="card-text text-danger d-inline mr-2">Ket(<?= trim($ket) ?>) </h5>
                    <?php endif; ?>
                    <br>
                    <p class="card-text mt-3" style="margin-bottom: -3px;">CNF yang dipakai : </p>
                    <p class="card-text"><?= $znfc ?> </p>
                <?php endif; ?>
            </div>
        </div>

    </div>
    <script src="js/jquery.js"></script>
    <script src="js/bootstrap.js"></script>
</body>

</html>