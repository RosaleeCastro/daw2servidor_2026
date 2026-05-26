# 008 - Generación dinámica de páginas web interactivas

## ⚡ Idea central

```
El usuario NO recarga la página.
JavaScript pide datos al servidor con fetch().
El servidor responde JSON o HTML.
JavaScript actualiza solo una parte del DOM.
```

---

## 🗺️ Mapa del tema

| Carpeta | Qué hace                                | JS devuelve | PHP devuelve |
| ------- | --------------------------------------- | ----------- | ------------ |
| `002`   | PHP con servicios propios y controlador | `.json()`   | JSON         |
| `003`   | PHP llama a proveedor externo con cURL  | `.text()`   | HTML         |

---

## ¿Cuándo usar cada una?

| El enunciado dice                     | Usa |
| ------------------------------------- | --- |
| "cargar datos sin recargar"           | 002 |
| "formulario dinámico"                 | 002 |
| "controlador que reparte a servicios" | 002 |
| "consumir servicio externo"           | 003 |
| "proveedor remoto"                    | 003 |
| "usar cURL en PHP"                    | 003 |
| "PHP llama a otro servidor"           | 003 |

---

## 📁 BLOQUE 1 — 002: Controlador + Servicios propios

### Flujo

```
cliente.html
  → fetch("controlador.php/videojuegos")
  → controlador.php lee PATH_INFO → "videojuegos"
  → busca en $servicios["videojuegos"]
  → carga servicios/servicioVideojuegos.php
  → llama servicioVideojuegos($metodo, $datosEntrada)
  → devuelve ["codigo" => 200, "datos" => [...]]
  → controlador.php llama responderJson()
  → cliente.html actualiza el DOM
```

### Funciones que van siempre en el controlador

```php
<?php
header("Content-Type: application/json; charset=utf-8");

// ── Mapa de servicios ────────────────────────────────────────────
// ⚠️ Cambiar: nombres de recursos, archivos y funciones
$servicios = [
    "videojuegos" => [
        "archivo" => "servicios/servicioVideojuegos.php", // ← tu archivo
        "funcion" => "servicioVideojuegos"                // ← tu función
    ],
    "alumnos" => [
        "archivo" => "servicios/servicioAlumnos.php",
        "funcion" => "servicioAlumnos"
    ]
];

// ── Detectar método y ruta ───────────────────────────────────────
// ✅ Copiar tal cual
$metodo = $_SERVER["REQUEST_METHOD"];
$ruta   = trim($_SERVER["PATH_INFO"] ?? "", "/"); // "videojuegos" o "alumnos"

if ($ruta === "")              responderJson(["error" => "Sin servicio"], 400);
if (!isset($servicios[$ruta])) responderJson(["error" => "No encontrado"], 404);

// ── Cargar servicio y leer body ──────────────────────────────────
// ✅ Copiar tal cual
require_once $servicios[$ruta]["archivo"];
$funcion      = $servicios[$ruta]["funcion"];
$entrada      = file_get_contents("php://input");
$datosEntrada = json_decode($entrada, true) ?? [];

$resultado = $funcion($metodo, $datosEntrada);
responderJson($resultado["datos"], $resultado["codigo"]);

// ── responderJson ────────────────────────────────────────────────
// ✅ Copiar tal cual
function responderJson($datos, $codigo = 200) {
    http_response_code($codigo);
    echo json_encode($datos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}
?>
```

### Funciones que van siempre en cada servicio

```php
<?php
// ⚠️ Cambiar: nombre función, archivo JSON y campos
function servicioVideojuegos($metodo, $datosEntrada) {
    $archivo = "datos/videojuegos.json"; // ← cambiar

    if ($metodo === "GET")  return consultarVideojuegos($archivo);
    if ($metodo === "POST") return añadirVideojuego($archivo, $datosEntrada);

    return ["codigo" => 405, "datos" => ["error" => "Método no permitido"]];
}

// ── leerJson / guardarJson / generarNuevoId ──────────────────────
// ✅ Copiar tal cual en cualquier servicio sin MySQL
function leerJson($archivo) {
    if (!file_exists($archivo)) return [];
    return json_decode(file_get_contents($archivo), true) ?? [];
}

function guardarJson($archivo, $datos) {
    file_put_contents($archivo, json_encode($datos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

function generarNuevoId($datos) {
    $mayorId = 0;
    foreach ($datos as $elemento) {
        if ($elemento["id"] > $mayorId) $mayorId = $elemento["id"];
    }
    return $mayorId + 1; // equivale a AUTO_INCREMENT sin MySQL
}
?>
```

### Cliente JS — GET y POST

```javascript
const API_VIDEOJUEGOS = "controlador.php/videojuegos"; // ⚠️ cambiar recurso

// GET — cargar y pintar
function cargarVideojuegos() {
  limpiarMensaje(mensajeVideojuegos);
  fetch(API_VIDEOJUEGOS)
    .then(function (r) {
      return r.json();
    })
    .then(function (datos) {
      mostrarVideojuegos(datos);
    })
    .catch(function () {
      mostrarMensaje(mensajeVideojuegos, "Error al cargar", "error");
    });
}

// POST — enviar formulario
function añadirVideojuego() {
  const titulo = document.getElementById("tituloVideojuego").value; // ⚠️ cambiar id
  const genero = document.getElementById("generoVideojuego").value; // ⚠️ cambiar id
  limpiarMensaje(mensajeVideojuegos);

  fetch(API_VIDEOJUEGOS, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ titulo: titulo, genero: genero }), // ⚠️ cambiar campos
  })
    .then(function (r) {
      // ✅ patrón que no cambia — mezcla código HTTP y datos
      return r.json().then(function (d) {
        return { codigo: r.status, datos: d };
      });
    })
    .then(function (res) {
      if (res.codigo === 201) {
        mostrarMensaje(mensajeVideojuegos, res.datos.mensaje, "ok");
        formVideojuego.reset(); // ⚠️ cambiar variable form
        cargarVideojuegos(); // ⚠️ cambiar función cargar
      } else {
        mostrarMensaje(mensajeVideojuegos, res.datos.error, "error");
      }
    });
}

// Pintar lista en el DOM
function mostrarVideojuegos(lista) {
  listadoVideojuegos.innerHTML = ""; // ⚠️ cambiar variable contenedor
  if (lista.length === 0) {
    listadoVideojuegos.innerHTML = "<p>No hay datos.</p>";
    return;
  }
  const ul = document.createElement("ul");
  lista.forEach(function (v) {
    const li = document.createElement("li");
    li.textContent = v.id + " - " + v.titulo + " (" + v.genero + ")"; // ⚠️ cambiar campos
    ul.appendChild(li);
  });
  listadoVideojuegos.appendChild(ul); // ⚠️ cambiar variable contenedor
}

// ✅ Copiar tal cual
function mostrarMensaje(elemento, texto, tipo) {
  elemento.textContent = texto;
  elemento.className = tipo;
}
function limpiarMensaje(elemento) {
  elemento.textContent = "";
  elemento.className = "";
}
```

### ⚠️ Error típico de examen

```
El archivo se llama:          servicioAlumonos.php   ← typo
El controlador referencia:    servicioAlumnos.php    ← distinto
→ el servicio no carga
```

---

## 📁 BLOQUE 2 — 003: cURL con proveedor externo

### Flujo

```
cliente.html
  → fetch("servidor/controlador.php?categoria=Rol")
  → controlador.php valida $_GET["categoria"]
  → construirUrlProveedor("Rol")
  → obtenerContenidoRemoto($url) ← cURL aquí
  → proveedor devuelve JSON
  → json_decode() → array PHP
  → mostrarOfertas() → genera HTML
  → cliente.html mete HTML en #resultado con innerHTML
```

### ⭐ Funciones que van SIEMPRE con cURL

```php
// ⭐ obtenerContenidoRemoto() — ✅ Copiar tal cual
// Para qué: hace la petición HTTP desde PHP a otra URL
// Devuelve: ["contenido" => "...", "error" => ""] o ["contenido" => "", "error" => "msg"]
function obtenerContenidoRemoto($url) {
    if (!function_exists("curl_init")) {
        return ["contenido" => "", "error" => "cURL no está activado en PHP."];
    }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);            // URL a llamar
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // guarda respuesta como string
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);    // máximo 5s para conectar
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);          // máximo 10s total

    $contenido  = curl_exec($ch);
    $error      = curl_error($ch);
    $codigoHttp = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if ($contenido === false || $error !== "") {
        return ["contenido" => "", "error" => $error];
    }
    if ($codigoHttp < 200 || $codigoHttp >= 300) {
        return ["contenido" => "", "error" => "HTTP " . $codigoHttp];
    }

    return ["contenido" => $contenido, "error" => ""];
}

// ⭐ construirUrlProveedor() — ⚠️ Cambiar ruta del proveedor y parámetro
// Para qué: construye la URL absoluta del proveedor con protocolo y host automáticos
// Devuelve: "http://localhost/carpeta/proveedorExterno/ofertas.php?categoria=Rol"
function construirUrlProveedor($categoria) {
    $protocolo = "http";
    if (!empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off") $protocolo = "https";

    $host = $_SERVER["HTTP_HOST"];

    $carpeta = dirname(dirname($_SERVER["SCRIPT_NAME"]));
    $partes  = explode("/", trim($carpeta, "/"));
    $ruta    = "";
    foreach ($partes as $parte) {
        $ruta .= "/" . rawurlencode($parte); // rawurlencode evita problemas con tildes
    }

    $url = $protocolo . "://" . $host . $ruta . "/proveedorExterno/ofertas.php"; // ⚠️ cambiar ruta

    if ($categoria !== "") {
        $url .= "?categoria=" . urlencode($categoria); // ⚠️ cambiar parámetro
    }

    return $url;
}

// ⭐ mostrarError() — ✅ Copiar tal cual
// Para qué: devuelve HTML de error cuando cURL falla
// Se consume: en cada punto de fallo del controlador, siempre con exit
function mostrarError($mensaje) {
    echo '<p class="error">' . htmlspecialchars($mensaje) . '</p>';
}
```

### Controlador completo con los 5 puntos de error

```php
<?php
// servidor/controlador.php
header("Content-Type: text/html; charset=utf-8"); // ← HTML no JSON

$categoria = $_GET["categoria"] ?? "";
$permitidas = ["", "Rol", "Carreras", "Aventura", "Construcción"]; // ⚠️ cambiar
if (!in_array($categoria, $permitidas, true)) $categoria = "";

// Punto 1 — construir URL
$urlProveedor    = construirUrlProveedor($categoria);

// Punto 2 — llamar con cURL
$respuestaRemota = obtenerContenidoRemoto($urlProveedor);
if ($respuestaRemota["error"] !== "") {
    mostrarError("No se pudo conectar con el proveedor remoto."); exit;
}

// Punto 3 — decodificar JSON
$ofertas = json_decode($respuestaRemota["contenido"], true);
if (!is_array($ofertas)) {
    mostrarError("El proveedor no devolvió JSON válido."); exit;
}

// Punto 4 — comprobar resultados
if (count($ofertas) === 0) {
    echo '<p class="mensaje">No hay ofertas para esa categoría.</p>'; exit;
}

// Punto 5 — generar HTML
mostrarOfertas($ofertas);
?>
```

### Cliente JS — fetch que espera HTML

```javascript
// ⚠️ Cambiar: URL base, parámetro, variable select y variable contenedor
async function cargarOfertas() {
  const categoria = selectCategoria.value; // ⚠️ cambiar variable

  const parametros = new URLSearchParams();
  parametros.append("categoria", categoria); // ⚠️ cambiar parámetro
  const url = URL_OFERTAS + "?" + parametros.toString();

  resultado.innerHTML = '<p class="cargando">Cargando...</p>'; // ⚠️ cambiar variable

  try {
    const respuesta = await fetch(url);
    if (!respuesta.ok) throw new Error("Error en el servidor");

    // ✅ .text() NO .json() — el controlador devuelve HTML
    const html = await respuesta.text();
    resultado.innerHTML = html; // ⚠️ cambiar variable contenedor
  } catch (error) {
    resultado.innerHTML =
      '<p class="error">No se pudieron cargar las ofertas.</p>';
  }
}
```

---

## ⚔️ 002 vs 003 — diferencias clave

|                       | 002 Controlador + servicios | 003 cURL proveedor externo        |
| --------------------- | --------------------------- | --------------------------------- |
| ¿Quién llama a quién? | JS → PHP → servicio propio  | JS → PHP → proveedor externo      |
| Datos                 | Archivo `.json` local       | Otro servidor PHP                 |
| PHP devuelve          | JSON                        | HTML                              |
| JS lee respuesta      | `.json()`                   | `.text()`                         |
| Función clave         | mapa `$servicios[]`         | `obtenerContenidoRemoto()`        |
| Error en JS           | `res.datos.error`           | `innerHTML = '<p class="error">'` |

---

## 🧠 Resumen mental para el examen

| Pregunta                                             | Respuesta                                                    |
| ---------------------------------------------------- | ------------------------------------------------------------ |
| ¿Qué hace el controlador en 002?                     | Lee ruta y método, carga el servicio correcto, devuelve JSON |
| ¿Cómo lee PHP la ruta en 002?                        | `trim($_SERVER["PATH_INFO"], "/")`                           |
| ¿Qué devuelve siempre un servicio en 002?            | `["codigo" => 2xx/4xx, "datos" => [...]]`                    |
| ¿Cómo genero ID sin MySQL?                           | `generarNuevoId()` → busca el mayor y suma 1                 |
| Error típico 002                                     | Nombre del archivo distinto al del controlador               |
| ¿Qué es cURL?                                        | Extensión PHP para hacer peticiones HTTP desde el servidor   |
| ¿Por qué no fetch directo al proveedor?              | CORS, ocultar URL, validar, transformar datos                |
| ¿Cómo compruebo si cURL está disponible?             | `function_exists("curl_init")`                               |
| ¿Qué hace `CURLOPT_RETURNTRANSFER`?                  | Guarda la respuesta como string, no la imprime               |
| ¿Por qué `.text()` y no `.json()` en 003?            | Porque el controlador devuelve HTML                          |
| ¿Por qué `htmlspecialchars()` en `mostrarOfertas()`? | El contenido viene de fuera, puede ser peligroso             |
| ¿Por qué `rawurlencode()` en la URL?                 | Evita que tildes y espacios rompan la URL                    |
