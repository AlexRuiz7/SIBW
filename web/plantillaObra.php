<?php
  $conexion = mysqli_connect ("localhost", "usuario", "", "museo");

  if ( mysqli_connect_errno() ) {
    die("No se ha podido conectar a la base de datos: " . mysqli_connect_error());
  }

  mysqli_set_charset($conexion, "utf8");
?>


<html>
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Museo SIBW</title>
    <link rel="stylesheet" type="text/css" href="css/estilo.css">
    <link rel="stylesheet" type="text/css" href="css/estilo_obra.css">
    <script src="js/funciones.js"></script>
  </head>

  <body>
    <?php
      include("php/header.php");
    ?>
    <!-- SECCIONES: comienzo -->
    <div class="secciones">
      <?php
      include("php/sidebar.php");
      include("php/obra.php");
      ?>
    </div>
    <!-- SECCIONES: fin -->
    <?php
      include("php/comentarios.php");
      include("php/footer.php");
    ?>
  </body>

</html>


<?php
  mysqli_close ($conexion);
?>