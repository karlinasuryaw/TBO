<?php

$db = new mysqli("localhost", "root", "", "text_search");
if ($db->connect_errno > 0) {
    die('Unable to connect to database [' . $db->connect_error . ']');
}
//variable yang dibutuhkan
$val = trim($_GET['cari'], " ");

if ($val == '') {
    echo "<script>
    alert('Tolong diisi search nya');
    window.location.href='index.php';
    </script>";
}
$cari = $val;
$cari = explode(" ", $cari);
$loop = count($cari);
$index = 0;
for ($i = 0; $i < $loop; $i++) {
    if ($cari[$i] == '') unset($cari[$i]);
    else {
        $tnp = $cari[$i];
        unset($cari[$i]);
        $cari[$index] = $tnp;
        $index++;
    }
}

$batasan = $_GET['batasan'];

//untuk pagination
if (isset($_GET['page']))
    $page = $_GET['page'];
else
    $page = 1;
$awalPage = (5 * $page) - 5;
$akhirPage = 5 * $page;

//query untuk ke database
$query = "SELECT * FROM dokumen WHERE id BETWEEN 1 AND " . $batasan;
$hasil = $db->query($query);
$hasil = $hasil->fetch_all(MYSQLI_ASSOC);

//gunanya untuk nampilin dokumen judulnya deskripsi dll
$total = 0;
$dokumen = [];
$judul = [];
$deskripsi = [];
$katanya = [];

//nyari quintuple nfa nya
$t = str_replace(" ", "", $val);
$startState = 0;
$jumlahState = strlen($t);
$finalState = [];
for ($i = 0; $i < count($cari); $i++) {
    array_push($finalState, strlen($cari[$i]));
}

foreach ($hasil as $ok) {
    $kalimat = [];
    $isi = $ok['isi'];
    $ada = []; //ada ini untuk variable index ke brapa dia
    for ($i = 0; $i < count($cari); $i++) {
        $final = mencari($isi, $cari[$i]);
        if ($final['state'] == strlen($cari[$i])) {
            array_push($ada, $i);
            array_push($kalimat, $final['kalimat']);
        }
    }
    if (!empty($ada)) {
        $total++;
        array_push($dokumen, $ok['id']);
        array_push($judul, $ok['judul']);
        array_push($deskripsi, $kalimat[count($kalimat) - 1]);
        $tmp = "";
        for ($i = 0; $i < count($ada); $i++) {
            $tmp = $tmp . $cari[$ada[$i]] . ", ";
        }
        array_push($katanya, $tmp);
    }
}

$jmlHalaman = ceil($total / 5);

function mencari($text, $cari)
{
    $c = 0;
    for ($i = 0; $i < strlen($text); $i++) {
        if (strtolower($text[$i]) == strtolower($cari[$c])) {
            $c++; //lanjut next state
        } else {
            $c = 0; //kembali ke startstate
        }
        if ($c == strlen($cari)) {
            if ($text[$i - $c] != " " || $text[$i + 1] != " ") {
                $c = 0; //kembali ke startstate
                continue;
            };
            //ini untuk ngambil kata kiri sm kanannya
            $kiri = $i - $c;
            if ($kiri < 0) $kiri = 0;
            $startkiri = $kiri - 25;
            if ($startkiri < 0) {
                $startkiri = 0;
                $katakiri = substr($text, $startkiri, $kiri);
            } else {
                $tmp = 25;
                //untuk nyari kata kiri biar ketemu spasi
                while ($text[$startkiri] != " ") {
                    $startkiri--;
                    $tmp++;
                }
                $katakiri = substr($text, $startkiri, $tmp);
            }
            $katakanan = substr($text, $i + 1, 150);
            $ok['kalimat'] = $katakiri . " " . "<b>" . $cari . "</b>" . $katakanan . "...";
            break;
        }
    }
    $ok['state'] = $c;
    return $ok;
}
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
                        <input type="text" name="cari" class="form-control mr-sm-2" style="width: 600px;" value="<?= $val ?>" required>
                        <input type="hidden" class="batasan" value="<?= $batasan ?>" name="batasan">
                        <button class="btn btn-info my-2 my-sm-0" type="submit">Search</button>
                        <button type="button" class="btn btn-danger ml-4 quinn">Quintuple</button>
                    </form>
                </div>
            </li>
        </ul>
    </nav>
    <div class="container" style="margin-top: 75px;">
        <div class="quin">
            State :
            <?php for ($i = 0; $i <= $jumlahState; $i++) : ?>
                q<?= $i ?>,
            <?php endfor; ?>
            <br>
            Input :
            <?php for ($i = 0; $i < strlen($t); $i++) : ?>
                <?= $t[$i] ?>
            <?php endfor; ?>
            <br>
            Start State : q0
            <br>
            Final State :
            <?php for ($i = 1; $i < count($finalState); $i++) : ?>
                <?php $finalState[$i] = $finalState[$i - 1] + $finalState[$i] ?>
            <?php endfor; ?>
            <?php for ($i = 0; $i < count($finalState); $i++) : ?>
                q<?= $finalState[$i] ?>,
            <?php endfor; ?>
            <br>
            Delta : <br>
            <?php $temps = 0;
            for ($i = 0; $i < count($cari); $i++) {
                for ($j = 0; $j < strlen($cari[$i]); $j++) {
                    if ($i > 0)
                        if ($temps == $finalState[$i - 1])
                            echo "δ(q0," . $cari[$i][$j] . ") = q" . ++$temps . "<br>";
                        else
                            echo "δ(q" . $temps . ", " . $cari[$i][$j] . ") = q" . ++$temps . "<br>";
                    else
                        echo "δ(q" . $temps . ", " . $cari[$i][$j] . ") = q" . ++$temps . "<br>";
                }
            } ?>
        </div>

        <h5 class="text-muted">Terdapat <?= $total ?> dokumen dari <?= $batasan ?> dokumen.</h5>

        <?php for ($i = $awalPage; $i < $akhirPage; $i++) : ?>
            <?php if (isset($judul[$i])) : ?>
                <div class="card mb-2">
                    <div class="card-body">
                        <h5 class="card-title"><?= $judul[$i] ?></h5>
                        <h6 class="card-subtitle mb-2 text-muted">Terdapat kata <?= $katanya[$i] ?> dalam dokumen <?= $dokumen[$i] ?>.</h6>
                        <p class="card-text"><?= $deskripsi[$i] ?></p>
                        <a href="dokumen.php?id=<?= $dokumen[$i] ?>" class="card-link">Selengkapnya..</a>
                    </div>
                </div>
            <?php endif; ?>
        <?php endfor; ?>
        <nav class="my-4">
            <ul class="pagination">
                <li class="page-item">
                    <a class="page-link" href="?cari=<?= $val ?>&batasan=<?= $batasan ?>&page=<?= $page - 1 ?>" aria-label="Previous">
                        <span aria-hidden="true">&laquo;</span>
                    </a>
                </li>
                <?php for ($i = 1; $i <= $jmlHalaman; $i++) : ?>
                    <li class="page-item <?php if ($page == $i) echo 'active'; ?>">
                        <a class="page-link" href="?cari=<?= $val ?>&batasan=<?= $batasan ?>&page=<?= $i ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
                <li class="page-item">
                    <a class="page-link" href="?cari=<?= $val ?>&batasan=<?= $batasan ?>&page=<?= $page + 1 ?>" aria-label="Next">
                        <span aria-hidden="true">&raquo;</span>
                    </a>
                </li>
            </ul>
        </nav>
    </div>
    <script src="js/jquery.js"></script>
    <script src="js/bootstrap.js"></script>
    <script>
        var s = parseInt($(".batasan").val());
        $(document).ready(function() {
            $(".quin").hide();
            var click = 1;
            $(".quinn").click(function() {
                if (click == 1) {
                    $(".quin").show(300);
                    click++;
                } else {
                    $(".quin").hide(300);
                    click = 1
                }
            })
            $(".pencarian").submit(function() {
                s = s + 50;
                if (s > 225) s = 225
                $(".batasan").val(s);
                console.log($(".batasan").val());
            })
        });
    </script>
</body>

</html>