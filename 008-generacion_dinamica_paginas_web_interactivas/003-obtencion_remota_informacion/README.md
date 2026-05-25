# 003 - Obtencion remota de informacion con cURL

Esta carpeta muestra como un servidor PHP puede pedir informacion a otro servicio.

Aqui hay tres capas:

```text
cliente.html
  -> servidor/controlador.php
  -> proveedorExterno/ofertas.php
```

El navegador no llama directamente al proveedor externo. Llama a tu servidor, y tu servidor usa `cURL`.

## Archivos

| Archivo | Papel |
| --- | --- |
| `cliente.html` | Pantalla con un `select` de categorias y JavaScript `fetch()`. |
| `servidor/controlador.php` | Intermediario. Recibe categoria, llama con cURL al proveedor y devuelve HTML. |
| `proveedorExterno/ofertas.php` | Servicio externo simulado. Devuelve JSON de ofertas. |

## Flujo completo

```text
1. Usuario elige categoria.
2. `cliente.html` ejecuta `cargarOfertas()`.
3. JS construye URL con `URLSearchParams`.
4. JS hace `fetch("servidor/controlador.php?categoria=Rol")`.
5. `controlador.php` recibe `$_GET["categoria"]`.
6. PHP valida que la categoria este permitida.
7. PHP crea la URL del proveedor con `construirUrlProveedor()`.
8. PHP llama al proveedor con `obtenerContenidoRemoto()`.
9. Dentro de esa funcion se usa cURL.
10. El proveedor devuelve JSON.
11. PHP hace `json_decode()`.
12. PHP genera HTML con `mostrarOfertas()`.
13. JS recibe texto HTML.
14. JS lo mete en `resultado.innerHTML`.
```

## Diagrama de flujo de cURL

```text
[cliente.html]
     |
     | fetch("servidor/controlador.php?categoria=Rol")
     v
[servidor/controlador.php]
     |
     | valida categoria
     v
[construirUrlProveedor()]
     |
     | devuelve URL absoluta del proveedor
     v
[obtenerContenidoRemoto($url)]
     |
     v
[curl_init()]
     |
     v
[curl_setopt()]
     |-- CURLOPT_URL = URL del proveedor
     |-- CURLOPT_RETURNTRANSFER = true
     |-- CURLOPT_CONNECTTIMEOUT = 5
     |-- CURLOPT_TIMEOUT = 10
     v
[curl_exec()]
     |
     +--> si falla: curl_error() -> mostrarError()
     |
     v
[curl_getinfo(CURLINFO_HTTP_CODE)]
     |
     +--> si no es 2xx: mostrarError()
     |
     v
[json_decode()]
     |
     +--> si no es JSON valido: mostrarError()
     |
     v
[mostrarOfertas()]
     |
     v
[HTML al cliente]
```

## Funciones reutilizables

### `construirUrlProveedor($categoria)`

Archivo: `servidor/controlador.php`.

Construye una URL absoluta hacia el proveedor externo simulado.

Hace tres cosas importantes:

1. Detecta si la peticion usa `http` o `https`.
2. Usa `$_SERVER["HTTP_HOST"]` para obtener el host actual.
3. Codifica partes de la ruta con `rawurlencode()` para evitar problemas con espacios o caracteres especiales.

### `obtenerContenidoRemoto($url)`

Archivo: `servidor/controlador.php`.

Es la funcion mas importante de esta carpeta.

Sirve para pedir informacion remota desde PHP usando cURL.

Plantilla:

```php
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

$contenido = curl_exec($ch);
$error = curl_error($ch);
$codigoHttp = curl_getinfo($ch, CURLINFO_HTTP_CODE);
```

Que significa cada opcion:

| Opcion | Significado |
| --- | --- |
| `CURLOPT_URL` | URL a la que PHP va a llamar. |
| `CURLOPT_RETURNTRANSFER` | Hace que `curl_exec()` devuelva el contenido en vez de imprimirlo. |
| `CURLOPT_CONNECTTIMEOUT` | Maximo tiempo para conectar. |
| `CURLOPT_TIMEOUT` | Maximo tiempo total de la peticion. |
| `CURLINFO_HTTP_CODE` | Codigo HTTP recibido del proveedor. |

### `mostrarOfertas($ofertas)`

Archivo: `servidor/controlador.php`.

Convierte el array de ofertas en HTML.

Usa `htmlspecialchars()` para evitar que el contenido externo se pinte como HTML peligroso.

### `mostrarError($mensaje)`

Archivo: `servidor/controlador.php`.

Devuelve un bloque HTML de error.

Es util porque el cliente espera HTML, no JSON.

### `cargarOfertas()`

Archivo: `cliente.html`.

Hace la peticion desde el navegador:

```js
const parametros = new URLSearchParams();
parametros.append("categoria", categoria);

const url = URL_OFERTAS + "?" + parametros.toString();
const respuesta = await fetch(url);
const html = await respuesta.text();
resultado.innerHTML = html;
```

## Que devuelve cada archivo

| Archivo | Devuelve |
| --- | --- |
| `proveedorExterno/ofertas.php` | JSON |
| `servidor/controlador.php` | HTML |
| `cliente.html` | No devuelve datos, actualiza la pagina |

## Errores controlados

El controlador controla:

- categoria no permitida,
- extension cURL no activada,
- error de conexion,
- codigo HTTP remoto no valido,
- JSON remoto invalido,
- lista de ofertas vacia.

## Chuleta para examen

Si el examen pide "consumir servicio externo con cURL", escribe esta estructura:

```text
1. Recibo parametro con $_GET.
2. Valido el parametro.
3. Construyo la URL remota.
4. Inicio cURL con curl_init().
5. Configuro cURL con curl_setopt().
6. Ejecuto con curl_exec().
7. Compruebo curl_error().
8. Compruebo codigo HTTP con curl_getinfo().
9. Decodifico JSON con json_decode().
10. Devuelvo HTML o JSON al cliente.
```

## Diferencia clave

```text
fetch() = navegador llama a tu PHP.
cURL    = tu PHP llama a otro servidor.
```

