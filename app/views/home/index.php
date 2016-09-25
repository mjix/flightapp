<? include resource_path('/views/layouts/header.php'); ?>

<style type="text/css">
h1{text-align: center; font-size: 95px; color: #ccc; font-family: "Agency FB"; font-weight: normal; margin-top: 100px;}
</style>

<div class="wrapper">
    <h1>HELLO<?=($name ? ', '.$name : ""); ?></h1>
</div>

<? include resource_path('/views/layouts/footer.php'); ?>

