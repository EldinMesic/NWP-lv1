<?php
    include('DiplomskiRadovi.php');

    $diplomskiRadovi = new DiplomskiRadovi();
    if(isset($_POST['submitButton'])){
        $diplomskiRadovi->create($_POST['pageN']);
        $diplomskiRadovi->save();
    }
    header('Location: http://localhost/NWP/lv1/index.php');
    die();
?>