# 008/003 - Obtención remota de información con cURL

## ⚡ Idea central

```
fetch()  → el NAVEGADOR llama a tu PHP
cURL     → tu PHP llama a OTRO servidor
```

> El cliente nunca habla directamente con el proveedor.  
> Siempre pasa por tu PHP intermediario.

---

## 🗂️ Archivos

```
cliente.html                        ← interfaz + JavaScript
servidor/controlador.php            ← intermediario: valida, llama con cURL, devuelve HTML
proveedorExterno/ofertas.php        ← servicio externo simulado: devuelve JSON
```

---

## ¿Qué devuelve cada capa?

```
proveedorExterno/ofertas.php  → JSON
servidor/controlador.php      → HTML  ← distinto a todo lo anterior
cliente.html                  → pinta ese HTML en #resultado con innerHTML
```

---

## 📦 Flujo completo

```
cliente.html
  → fetch("servidor/controlador.php?categoria=Rol")
  → controlador.php recibe $_GET["categoria"]
  → valida que la categoría esté permitida
  → construirUrlProveedor("Rol")
  → obtenerContenidoRemoto($url) ← aquí entra cURL
  → proveedor devuelve JSON
  → json_decode() → array PHP
  → mostrarOfertas() → genera HTML
  → cliente.html mete el HTML en #resultado
```

---

## 🔧 Funciones reutilizables

---

### ⭐ `obtenerContenidoRemoto($url)` — servidor/controlador.php

> **Prioridad máxima — va siempre que uses un proveedor externo con cURL**  
> **Para qué sirve:** hace la petición HTTP desde PHP a otra URL usando cURL.  
> **Se consume en:** el controlador, después de construir la URL del proveedor.  
> **Qué devuelve:** `["contenido" => "...", "error" => ""]` si va bien. `["contenido" => "", "error" => "mensaje"]` si falla.

```php
// ✅ Copiar tal cual — no necesita cambios
function obtenerContenidoRemoto($url) {

    // Comprobar que la extensión cURL está activada en PHP
    // cURL es una librería externa — no siempre está disponible
    if (!function_exists("curl_init")) {
        return ["contenido" => "", "error" => "La extensión cURL no está activada en PHP."];
    }

    $ch = curl_init(); // inicia la sesión cURL

    curl_setopt($ch, CURLOPT_URL, $url);            // URL a la que PHP va a llamar
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // guarda la respuesta en $contenido, no la imprime
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);    // máximo 5s para conectar
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);          // máximo 10s para toda la petición

    $contenido  = curl_exec($ch);                            // ejecuta la petición
    $error      = curl_error($ch);                           // "" si no hay error
    $codigoHttp = curl_getinfo($ch, CURLINFO_HTTP_CODE);     // 200, 404, 500...

    // Error de conexión
    if ($contenido === false || $error !== "") {
        return ["contenido" => "", "error" => $error];
    }

    // El proveedor respondió pero con error HTTP
    if ($codigoHttp < 200 || $codigoHttp >= 300) {
        return ["contenido" => "", "error" => "Código HTTP no válido: " . $codigoHttp];
    }

    return ["contenido" => $contenido, "error" => ""];
}
```

**Cómo se consume en el controlador:**

```php
$respuestaRemota = obtenerContenidoRemoto($urlProveedor);

// Siempre comprobar el error antes de usar el contenido
if ($respuestaRemota["error"] !== "") {
    mostrarError("No se pudo conectar con el proveedor remoto.");
    exit;
}

// Si no hay error, usar el contenido
$ofertas = json_decode($respuestaRemota["contenido"], true);
```

---

### ⭐ `construirUrlProveedor($categoria)` — servidor/controlador.php

> **Prioridad máxima — va siempre que uses un proveedor externo con cURL**  
> **Para qué sirve:** construye la URL absoluta del proveedor detectando automáticamente  
> el protocolo (http/https) y el host actual. Codifica la ruta para evitar  
> problemas con tildes, espacios y caracteres especiales.  
> **Se consume en:** el controlador, antes de llamar a `obtenerContenidoRemoto()`.  
> **Qué devuelve:** string con la URL completa → `"http://localhost/carpeta/proveedorExterno/ofertas.php?categoria=Rol"`

```php
// ⚠️ Cambiar: ruta al archivo del proveedor y nombre del parámetro
function construirUrlProveedor($categoria) {

    // Detectar protocolo
    $protocolo = "http";
    if (!empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off") {
        $protocolo = "https";
    }

    $host = $_SERVER["HTTP_HOST"]; // → "localhost"

    // Obtener la carpeta del proyecto y codificar cada parte
    // rawurlencode() evita problemas con tildes y espacios en la ruta
    // Sin codificar: /008-generación → rompe la URL
    // Con codificar: /008-generaci%C3%B3n → URL válida
    $carpetaProyecto = dirname(dirname($_SERVER["SCRIPT_NAME"]));
    $partesRuta = explode("/", trim($carpetaProyecto, "/"));
    $rutaCodificada = "";

    foreach ($partesRuta as $parte) {
        $rutaCodificada .= "/" . rawurlencode($parte);
    }

    // ⚠️ Cambiar la ruta al archivo del proveedor
    $url = $protocolo . "://" . $host . $rutaCodificada . "/proveedorExterno/ofertas.php";

    // ⚠️ Cambiar "categoria" por el nombre de tu parámetro
    if ($categoria !== "") {
        $url .= "?categoria=" . urlencode($categoria);
    }

    return $url;
}
```

**Cómo se consume en el controlador:**

```php
$urlProveedor = construirUrlProveedor($categoria);
// → "http://localhost/carpeta/proveedorExterno/ofertas.php?categoria=Rol"

$respuestaRemota = obtenerContenidoRemoto($urlProveedor);
```

---

### ⭐ `mostrarError($mensaje)` — servidor/controlador.php

> **Prioridad máxima — va siempre que uses cURL**  
> **Para qué sirve:** devuelve HTML de error cuando cURL falla o el JSON no es válido.  
> El cliente recibe este HTML y lo mete en `#resultado` con `innerHTML`.  
> **Se consume en:** el controlador, en cada punto donde puede fallar algo.

```php
// ✅ Copiar tal cual — no necesita cambios
function mostrarError($mensaje) {
    echo '<p class="error">' . htmlspecialchars($mensaje) . '</p>';
    // htmlspecialchars evita que el mensaje de error rompa el HTML
}
```

**Cómo se consume en el controlador:**

```php
// En cada punto de fallo — siempre seguido de exit
if ($respuestaRemota["error"] !== "") {
    mostrarError("No se pudo conectar con el proveedor remoto.");
    exit;
}

if (!is_array($ofertas)) {
    mostrarError("El proveedor remoto no ha devuelto un JSON válido.");
    exit;
}
```

---

### `mostrarOfertas($ofertas)` — servidor/controlador.php

> **Para qué sirve:** convierte el array de ofertas recibido del proveedor en HTML.  
> Usa `htmlspecialchars()` en todos los campos porque el contenido viene de fuera  
> y podría contener caracteres peligrosos.  
> **Se consume en:** al final del controlador, cuando todo ha ido bien.  
> **⚠️ Cambiar:** estructura HTML y nombres de campos según tu recurso.

```php
// ⚠️ Cambiar: estructura HTML y campos según tu recurso
function mostrarOfertas($ofertas) {
    echo '<div class="ofertas">';

    foreach ($ofertas as $oferta) {
        echo '<article class="oferta">';
        // ⚠️ Cambiar los campos por los de tu recurso
        // ✅ htmlspecialchars() siempre — el contenido viene de un proveedor externo
        echo '<h3>'                        . htmlspecialchars($oferta["titulo"])      . '</h3>';
        echo '<p>Categoría: '              . htmlspecialchars($oferta["categoria"])   . '</p>';
        echo '<p class="descuento">Descuento: ' . htmlspecialchars($oferta["descuento"]) . '%</p>';
        echo '<p>'                         . htmlspecialchars($oferta["descripcion"]) . '</p>';
        echo '</article>';
    }

    echo '</div>';
}
```

**Cómo se consume en el controlador:**

```php
// Solo se llega aquí si todo ha ido bien
mostrarOfertas($ofertas);
// No hace falta exit — es la última instrucción del controlador
```

---

### `cargarOfertas()` — cliente.html

> **Para qué sirve:** recoge la categoría del `<select>`, construye la URL con  
> `URLSearchParams`, hace el fetch y mete el HTML recibido en `#resultado`.  
> **⚠️ Clave:** usa `.text()` no `.json()` porque el controlador devuelve HTML.  
> **Se consume en:** el evento submit del formulario.

```javascript
// ⚠️ Cambiar: id del select, URL base y id del contenedor resultado
async function cargarOfertas() {
  const categoria = selectCategoria.value; // ⚠️ cambiar variable del select

  // Construir URL con parámetros
  const parametros = new URLSearchParams();
  parametros.append("categoria", categoria); // ⚠️ cambiar nombre del parámetro
  const url = URL_OFERTAS + "?" + parametros.toString();
  // → "servidor/controlador.php?categoria=Rol"

  resultado.innerHTML = '<p class="cargando">Cargando ofertas externas...</p>'; // ⚠️ cambiar variable

  try {
    const respuesta = await fetch(url);

    if (!respuesta.ok) throw new Error("Error en la petición al servidor PHP");

    // ✅ .text() NO .json() — el controlador devuelve HTML, no JSON
    const html = await respuesta.text();

    resultado.innerHTML = html; // ⚠️ cambiar variable contenedor
  } catch (error) {
    resultado.innerHTML =
      '<p class="error">No se pudieron cargar las ofertas.</p>';
  }
}
```

**Cómo se consume en cliente.html:**

```javascript
// Se llama en el submit del formulario
formOfertas.addEventListener("submit", async function (event) {
  event.preventDefault();
  await cargarOfertas();
});
```

---

### `proveedorExterno/ofertas.php` — el proveedor simulado

> **Para qué sirve:** simula un servicio externo. Devuelve JSON filtrado por categoría.  
> **Se consume en:** cURL lo llama desde `obtenerContenidoRemoto()`.  
> **⚠️ Cambiar:** datos del array y nombre del parámetro de filtro.

```php
// ⚠️ Cambiar: datos del array y parámetro de filtro
<?php
header("Content-Type: application/json; charset=utf-8");

$ofertas = [
    ["id" => 1, "titulo" => "Oferta RPG",     "categoria" => "Rol",          "descuento" => 20, "descripcion" => "..."],
    ["id" => 2, "titulo" => "Pack velocidad",  "categoria" => "Carreras",     "descuento" => 15, "descripcion" => "..."],
    ["id" => 3, "titulo" => "Aventura épica",  "categoria" => "Aventura",     "descuento" => 25, "descripcion" => "..."],
    ["id" => 4, "titulo" => "Construye",       "categoria" => "Construcción", "descuento" => 10, "descripcion" => "..."],
    ["id" => 5, "titulo" => "Rol clásico",     "categoria" => "Rol",          "descuento" => 30, "descripcion" => "..."],
];

// ⚠️ Cambiar "categoria" por tu parámetro de filtro
$categoria = $_GET["categoria"] ?? "";

if ($categoria !== "") {
    $ofertasFiltradas = [];
    foreach ($ofertas as $oferta) {
        if ($oferta["categoria"] === $categoria) { // ⚠️ cambiar campo de filtro
            $ofertasFiltradas[] = $oferta;
        }
    }
    $ofertas = $ofertasFiltradas;
}

echo json_encode($ofertas, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
?>
```

---

## 🔴 Errores controlados — en orden de comprobación

```php
// 1. ¿cURL está activado?
if (!function_exists("curl_init")) { ... }

// 2. ¿Conectó con el proveedor?
if ($contenido === false || $error !== "") { ... }

// 3. ¿El código HTTP es 2xx?
if ($codigoHttp < 200 || $codigoHttp >= 300) { ... }

// 4. ¿El JSON es válido?
if (!is_array($ofertas)) { ... }

// 5. ¿Hay resultados?
if (count($ofertas) === 0) { ... }
```

---

## ⚔️ 002 vs 003 — diferencias clave

|                       | 002 Controlador + servicios | 003 cURL proveedor externo    |
| --------------------- | --------------------------- | ----------------------------- |
| ¿Quién llama a quién? | JS → PHP → servicio propio  | JS → PHP → proveedor externo  |
| Datos                 | Archivo `.json` local       | Otro servidor PHP             |
| PHP devuelve          | JSON                        | HTML                          |
| JS lee respuesta      | `.json()`                   | `.text()`                     |
| Función clave         | mapa `$servicios[]`         | `obtenerContenidoRemoto()`    |
| Error en JS           | `resultado.datos.error`     | `resultado.innerHTML = error` |

---

## 🧠 Resumen mental para el examen

| Pregunta                                             | Respuesta                                                  |
| ---------------------------------------------------- | ---------------------------------------------------------- |
| ¿Qué es cURL?                                        | Extensión PHP para hacer peticiones HTTP desde el servidor |
| ¿Por qué no fetch directo al proveedor?              | CORS, ocultar URL, validar parámetros, transformar datos   |
| ¿Cómo compruebo si cURL está disponible?             | `function_exists("curl_init")`                             |
| ¿Qué hace `CURLOPT_RETURNTRANSFER`?                  | Guarda la respuesta en `$contenido`, no la imprime         |
| ¿Por qué `.text()` y no `.json()` en JS?             | Porque el controlador devuelve HTML, no JSON               |
| ¿Por qué `htmlspecialchars()` en `mostrarOfertas()`? | El contenido viene de fuera — puede ser peligroso          |
| ¿Por qué `rawurlencode()` en la URL?                 | Evita que tildes y espacios rompan la URL                  |
| ¿Qué devuelve siempre `obtenerContenidoRemoto()`?    | `["contenido" => "...", "error" => ""]`                    |
