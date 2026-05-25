# Guia de examen - Generacion dinamica de paginas web interactivas

Este README es la chuleta principal de la carpeta `008-generacion_dinamica_paginas_web_interactivas`.

La idea central de esta unidad es:

```text
El usuario no recarga toda la pagina.
JavaScript pide datos al servidor.
El servidor responde JSON o HTML.
JavaScript actualiza solo una parte del DOM.
```

## Mapa rapido

| Carpeta | Tema | Flujo principal | Formato |
| --- | --- | --- | --- |
| `002-generacion_dinamica _paginas_interactivas` | Pagina interactiva con servicios propios | HTML -> fetch -> controlador PHP -> servicio PHP -> JSON | JSON |
| `003-obtencion_remota_informacion` | PHP consume informacion externa con cURL | HTML -> fetch -> controlador PHP -> cURL -> proveedor externo -> JSON -> HTML | JSON remoto + HTML final |

## Bloque 1 - Generacion dinamica con fetch

Usalo cuando el ejercicio diga:

- "cargar datos sin recargar la pagina",
- "anadir elementos desde un formulario",
- "mostrar una lista dinamicamente",
- "consumir un controlador PHP desde JavaScript".

Flujo:

```text
cliente.html
  -> fetch("controlador.php/videojuegos")
  -> controlador.php detecta ruta y metodo
  -> servicioVideojuegos.php lee o guarda JSON
  -> controlador.php responde JSON
  -> cliente.html pinta el DOM
```

Archivos clave:

- `002-.../cliente.html`
- `002-.../controlador.php`
- `002-.../servicios/servicioVideojuegos.php`
- `002-.../servicios/servicioAlumonos.php`
- `002-.../datos/videojuegos.json`
- `002-.../datos/alumnos.json`

Funciones reutilizables:

| Funcion o patron | Archivo | Para que sirve |
| --- | --- | --- |
| `responderJson($datos, $codigo)` | `002-.../controlador.php` | Responder JSON con codigo HTTP. |
| Mapa `$servicios` | `002-.../controlador.php` | Enrutar una URL a un archivo y una funcion. |
| `file_get_contents("php://input")` | `002-.../controlador.php` | Leer JSON enviado por `POST`. |
| `servicioVideojuegos($metodo, $datosEntrada)` | `002-.../servicios/servicioVideojuegos.php` | Separar la logica del recurso videojuegos. |
| `servicioAlumnos($metodo, $datosEntrada)` | `002-.../servicios/servicioAlumonos.php` | Separar la logica del recurso alumnos. |
| `leerJson($archivo)` | `002-.../servicios/servicioVideojuegos.php` | Leer datos desde un archivo `.json`. |
| `guardarJson($archivo, $datos)` | `002-.../servicios/servicioVideojuegos.php` | Guardar arrays PHP como JSON. |
| `generarNuevoId($datos)` | `002-.../servicios/servicioVideojuegos.php` | Crear ids incrementales sin base de datos. |
| `cargarVideojuegos()` | `002-.../cliente.html` | Hacer `GET` y pintar lista. |
| `anadirVideojuego()` | `002-.../cliente.html` | Hacer `POST` con JSON. |
| `mostrarMensaje()` | `002-.../cliente.html` | Mostrar errores o confirmaciones. |

## Bloque 2 - Obtencion remota con cURL

Usalo cuando el ejercicio diga:

- "obtener informacion remota",
- "consumir un proveedor externo",
- "usar cURL en PHP",
- "el servidor debe llamar a otro servidor",
- "transformar JSON remoto en HTML".

Flujo:

```text
cliente.html
  -> fetch("servidor/controlador.php?categoria=Rol")
  -> servidor/controlador.php valida categoria
  -> construirUrlProveedor("Rol")
  -> obtenerContenidoRemoto($urlProveedor)
  -> cURL llama a proveedorExterno/ofertas.php
  -> proveedorExterno/ofertas.php devuelve JSON
  -> controlador.php decodifica JSON
  -> mostrarOfertas($ofertas)
  -> cliente.html recibe HTML y lo mete en #resultado
```

Archivos clave:

- `003-.../cliente.html`
- `003-.../servidor/controlador.php`
- `003-.../proveedorExterno/ofertas.php`

Funciones reutilizables:

| Funcion o patron | Archivo | Para que sirve |
| --- | --- | --- |
| `construirUrlProveedor($categoria)` | `003-.../servidor/controlador.php` | Crear la URL completa del servicio externo. |
| `obtenerContenidoRemoto($url)` | `003-.../servidor/controlador.php` | Hacer una peticion HTTP con cURL. |
| `curl_init()` | `003-.../servidor/controlador.php` | Iniciar una peticion cURL. |
| `curl_setopt()` | `003-.../servidor/controlador.php` | Configurar URL, timeout y retorno. |
| `curl_exec()` | `003-.../servidor/controlador.php` | Ejecutar la peticion remota. |
| `curl_error()` | `003-.../servidor/controlador.php` | Detectar errores de conexion. |
| `curl_getinfo(..., CURLINFO_HTTP_CODE)` | `003-.../servidor/controlador.php` | Saber si el proveedor respondio 200, 404, 500, etc. |
| `json_decode($contenido, true)` | `003-.../servidor/controlador.php` | Convertir JSON remoto a array PHP. |
| `mostrarOfertas($ofertas)` | `003-.../servidor/controlador.php` | Transformar datos remotos en HTML. |
| `mostrarError($mensaje)` | `003-.../servidor/controlador.php` | Mostrar errores al cliente en HTML. |
| `cargarOfertas()` | `003-.../cliente.html` | Pedir ofertas al servidor y actualizar `#resultado`. |

## Diagrama de flujo de cURL

```text
[Usuario selecciona categoria]
              |
              v
[cliente.html ejecuta fetch()]
              |
              v
[servidor/controlador.php recibe categoria]
              |
              v
[Valida categoria permitida]
              |
              v
[construirUrlProveedor()]
              |
              v
[curl_init()]
              |
              v
[curl_setopt(): URL, RETURNTRANSFER, TIMEOUT]
              |
              v
[curl_exec()]
      |                       |
      v                       v
[Error de conexion]     [Respuesta recibida]
      |                       |
      v                       v
[mostrarError()]       [curl_getinfo() revisa HTTP]
                              |
                    +---------+---------+
                    |                   |
                    v                   v
              [HTTP no 2xx]        [HTTP 2xx]
                    |                   |
                    v                   v
              [mostrarError()]   [json_decode()]
                                        |
                              +---------+---------+
                              |                   |
                              v                   v
                         [JSON invalido]     [Array de ofertas]
                              |                   |
                              v                   v
                         [mostrarError()]   [mostrarOfertas()]
                                                  |
                                                  v
                                      [HTML vuelve al cliente]
                                                  |
                                                  v
                                      [cliente actualiza #resultado]
```

## Plantilla reutilizable de cURL

```php
function obtenerContenidoRemoto($url) {
    if (!function_exists("curl_init")) {
        return [
            "contenido" => "",
            "error" => "La extension cURL no esta activada."
        ];
    }

    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);

    $contenido = curl_exec($ch);
    $error = curl_error($ch);
    $codigoHttp = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if ($contenido === false || $error !== "") {
        return ["contenido" => "", "error" => $error];
    }

    if ($codigoHttp < 200 || $codigoHttp >= 300) {
        return [
            "contenido" => "",
            "error" => "Codigo HTTP no valido: " . $codigoHttp
        ];
    }

    return ["contenido" => $contenido, "error" => ""];
}
```

## Preguntas tipicas de examen

### Que diferencia hay entre `fetch()` y `cURL`?

`fetch()` se ejecuta en el navegador con JavaScript.

`cURL` se ejecuta en el servidor con PHP.

### Por que usar PHP con cURL en vez de llamar directamente al proveedor desde JS?

Porque el servidor puede:

- ocultar URLs internas o claves,
- validar parametros antes de llamar al proveedor,
- controlar errores,
- transformar JSON externo en HTML o en otro JSON,
- evitar algunos problemas de CORS,
- centralizar la comunicacion con servicios externos.

### Que devuelve cada capa?

```text
proveedorExterno/ofertas.php       -> JSON
servidor/controlador.php           -> HTML
cliente.html                       -> pinta ese HTML en pantalla
```

## Chuleta rapida para adaptar

Para crear otro ejercicio con cURL:

1. Crea un `cliente.html` con formulario.
2. En JS usa `fetch("servidor/controlador.php?...")`.
3. En PHP valida los parametros recibidos por `$_GET`.
4. Construye la URL remota.
5. Llama a `obtenerContenidoRemoto($url)`.
6. Decodifica JSON con `json_decode()`.
7. Comprueba que sea array.
8. Genera HTML o JSON de respuesta.
9. En JS mete la respuesta en el DOM.

