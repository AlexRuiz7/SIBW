<?php

class Noticia extends EntidadBase {
  /* Conexiones con las tablas relacionadas */
  private $con_imagen, $con_etiquetas, $con_comentarios;
  /* Objetos dependientes y construibles por esta clase */
  private $noticia, $barra_lateral, $grid_inicio;
  /* Parámetros varios */
  private $tam_sidebar;


  /**
   * Constructor
   */
  public function __construct() {
    parent::__construct("Noticias");

    $this -> con_imagen       = new ConsultasCRUD("ImagenesEnNoticia");
    $this -> con_etiquetas    = new ConsultasCRUD("EtiquetasEnNoticia");
    $this -> con_comentarios  = new ConsultasCRUD("Comentarios");

    $this -> noticia        = array();
    $this -> barra_lateral  = array();
    $this -> grid_inicio    = array();

    $this -> tam_sidebar    = 4;

    $this -> setBarraLateral();
    $this -> setGridInicio();
  }


  /**
   * Destructor
   */
  public function __destruct() {
    parent::__destruct();

    unset(  $this -> con_imagen       );
    unset(  $this -> con_etiquetas    );
    unset(  $this -> con_comentarios  );

    unset(  $this -> noticia          );
    unset(  $this -> barra_lateral    );
    unset(  $this -> grid_inicio      );

    unset(  $this -> tam_sidebar      );
  }


  /**
   * Actualiza el tamaño de la barra lateral de la página. Este valor define la
   * cantidad de noticias que aparecerán en ella.
   *
   * @return int $tam
   */
  public function setTamBarraLateral($tam) {
    $this -> tam_sidebar = $tam;
  }


  /**
   * Devuelve el tamaño de la barra lateral de la página. Este valor define la
   * cantidad de noticias que aparecerán en ella.
   *
   * @return int $tam
   */
  public function getTamBarraLateral() {
    return $this -> tam_sidebar;
  }


  /**
   * Recupera los datos para la barra lateral con las noticias más visitadas de
   * la página. Almacena los datos en una estructura de datos propia para ser
   * utilizada por Twig.
   */
  private function setBarraLateral() {
    $datos = $this->modelo->getValuesOrdered("id, titular", "visitas DESC");

    $temp = array();
    for($i=0; $i < $this -> tam_sidebar; $i++) {
      $fila = $datos->fetch(PDO::FETCH_ASSOC);
      if($fila)
        $temp[] = array(
          'id'      => $fila['id'],
          'titulo'  => $fila['titular']
        );
    }

    $this->barra_lateral = array('noticias_sidebar' => $temp);;
  }


  /**
   * Devuelve los datos de la barra lateral en forma de array
   *
   * @return Array datos de la barra lateral
   */
  public function getBarraLateral() {
    return $this->barra_lateral;
  }


  /**
   * Recupera los datos para la sección de noticias de la página de inicio.
   * Almacena los datos en una estructura de datos propia para ser utilizada por
   * Twig.
   */
  private function setGridInicio() {
    $datos = $this->modelo->getValuesOrdered("id, titular", "fecha DESC LIMIT 9");

    $temp = array();
    for($i=0; $i < 9; $i++) {
      $fila = $datos->fetch(PDO::FETCH_ASSOC);
      if($fila) {
        $id = $fila['id'];
        $img = $this->setImgs($id);
        $temp[] = array(
          'id'      => $id,
          'titulo'  => $fila['titular'],
          'imagen'  => 'media/imgs/noticias/' . $img
        );
      }
    }

    $this->grid_inicio = array('noticias_grid' => $temp);
  }


  /**
   * Devuelve los datos del grid de noticias de la página de inicio
   *
   * @return Array datos del grid de noticias de inicio
   */
  public function getGridInicio() {
    return $this->grid_inicio;
  }


  /**
   * Obtiene y devuelve la imagen asociada a cada noticia.
   *
   * NOTE solo devuelve 1 imagen ya que solo hay una por noticia de momento.
   *
   * @param  Int $id_noticia identificador de la noticia a consultar
   * @return String
   */
  private function setImgs($id_noticia) {
    $img_url = $this->con_imagen->getValuesBy("url", "id_noticia=$id_noticia");
    return $img_url->fetch(PDO::FETCH_ASSOC)['url'];
  }


  /**
   * Obtiene y devuelve todas las etiquetas asociadas a una noticia
   *
   * @param Int $id_noticia identificador de la noticia a consultar
   */
  private function setEtiquetas($id_noticia) {
    $datos = $this->con_etiquetas->getValuesBy("tema", "id_noticia=$id_noticia");

    while($fila = $datos->fetch(PDO::FETCH_ASSOC)) {
      $temp[] = $fila['tema'];
    }

    return implode(", ", $temp);
  }



  /**
   * Obtiene y devuelve todos los comentarios de la noticia identificada por
   * su id, dado como parámetro
   *
   * @param [type] $id_noticia [description]
   */
  private function setComentarios($id_noticia) {
    $datos = $this->con_comentarios->getValuesByOrdered("texto, email_usuario, fecha", "id_noticia=$id_noticia", "fecha DESC");

    $temp = NULL;

    while($fila = $datos->fetch(PDO::FETCH_ASSOC)) {
      $temp[] = array(
        'texto'   => $fila['texto'],
        'usuario' => $fila['email_usuario'],
        'fecha'   => $fila['fecha']
      );
    }

    return $temp;
  }


  /**
   * Inicializa la noticia solicitada recuperando todos los datos relacionados
   * con ella.
   */
  private function setNoticia($id_noticia) {

    if(isset($_POST["comentario"])) {
      $this -> crearComentario($id_noticia);
    }

    /* Recuperación de información */
    $datos = $this->modelo->getValuesBy("*", "id=$id_noticia")->fetch(PDO::FETCH_ASSOC);
    $imagenes     = $this -> setImgs($id_noticia);
    $etiquetas    = $this -> setEtiquetas($id_noticia);
    $comentarios  = $this -> setComentarios($id_noticia);
    $parrafos     = explode("##", $datos["texto"]);

    /* Actualización del contador de visitas */
    $visitas = $datos["visitas"] + 1;
    $this->modelo->updateValues("visitas=$visitas", "id=$id_noticia");

    /* Construcción de la estructura de datos */
    $temp = array(
      "id"      => $datos["id"],
      "titulo"  => $datos["titular"],
      "autor"   => $datos["autor"],
      "fecha"   => date('H:i:s  --  d-m-Y', strtotime($datos["fecha"])),
      "visitas" => $datos["visitas"],
      "enlace"  => $datos["link"],
      "texto"   => $parrafos,
      "imagen"  => 'media/imgs/noticias/' . $imagenes,
      "etiquetas" => $etiquetas,
      "comentarios" => $comentarios
    );

    return array('noticia' => $temp);
  }


  /**
   * Devuelve los datos de la noticia solicitada
   *
   * @param  Int $id_noticia id de la noticia
   * @return Array           datos de la noticia solicitada
   */
  public function getNoticia($id_noticia) {
    return $this -> setNoticia($id_noticia);
  }


  /**
   * Añade un comentario a la base de datos
   *
   * @param  Int $id_noticia id de la noticia
   */
  private function crearComentario($id_noticia) {
    $campos = "ip, texto, email_usuario, id_noticia";
    $temp = array(
      $_SERVER['REMOTE_ADDR'],
      $_POST["comentario"],
      "cuenta_falsa_1@dominio.com",
      $id_noticia
    );

    $valores = "'" . implode("', '", $temp) . "'";

    $this->con_comentarios->insertValues($campos, $valores);
  }

}

?>
