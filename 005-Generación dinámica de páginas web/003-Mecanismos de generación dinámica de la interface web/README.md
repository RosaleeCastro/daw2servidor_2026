# 🌐 Comunicación JavaScript ↔ PHP — Guía de referencia

> POST · GET · JSON · Cookies — los 4 patrones que usarás en TODOS tus proyectos con servidor

---

## 📁 Archivos de esta carpeta

| Archivo         | Qué contiene                          | Cuándo consultarlo                                  |
| --------------- | ------------------------------------- | --------------------------------------------------- |
| `post-get.html` | Los 4 tipos de comunicación JS → PHP  | Siempre que necesites enviar datos al servidor      |
| `procesar.php`  | Cómo recibe PHP cada tipo de petición | Siempre que escribas el lado del servidor           |
| `cookies.html`  | Crear, leer y borrar cookies desde JS | Cuando necesites guardar datos en el navegador      |
| `cookies.php`   | Leer y crear cookies desde PHP        | Cuando el servidor necesite leer o escribir cookies |

---

## 🗺️ Mapa mental — ¿qué método usar?

```
¿Qué necesito hacer?
        │
        ├── Enviar datos simples (un número, un texto)
        │       ├── y no quiero que se vea en la URL  →  POST form-urlencoded
        │       └── y no me importa que se vea en la URL →  GET (parámetros en URL)
        │
        ├── Enviar datos complejos (objeto, varios campos)
        │       ├── y solo necesito respuesta en texto  →  POST con JSON → respuesta texto
        │       └── y necesito respuesta estructurada   →  POST con JSON → respuesta JSON
        │
        └── Guardar datos entre páginas sin base de datos
                ├── desde JavaScript  →  setCookie() / getCookie()
                └── desde PHP         →  setcookie() / $_COOKIE[]
```

---

## 📡 Los 4 tipos de comunicación JS → PHP

---

### 1️⃣ POST — datos simples (form-urlencoded)

> 📄 Ver: `post-get.html` botón 1 · `procesar.php` bloque 1

**JavaScript — enviar:**

```js
const respuesta = await fetch("procesar.php", {
  method: "POST",
  headers: {
    "Content-Type": "application/x-www-form-urlencoded",
  },
  body: "accion=post_numero&numero=" + encodeURIComponent(numero),
});

const texto = await respuesta.text();
```

**PHP — recibir:**

```php
if (isset($_POST["accion"]) && $_POST["accion"] === "post_numero") {
    $numero = $_POST["numero"] ?? "No recibido";
    echo "Número recibido: $numero";
    exit;
}
```

> ✅ Úsalo para: formularios simples, enviar un campo o dos, login básico.

---

### 2️⃣ GET — datos en la URL

> 📄 Ver: `post-get.html` botón 2 · `procesar.php` bloque 2

**JavaScript — enviar:**

```js
const respuesta = await fetch(
  "procesar.php?accion=get_numero&numero=" + encodeURIComponent(numero),
);

const texto = await respuesta.text();
```

**PHP — recibir:**

```php
if (isset($_GET["accion"]) && $_GET["accion"] === "get_numero") {
    $numero = $_GET["numero"] ?? "No recibido";
    echo "Número recibido: $numero";
    exit;
}
```

> ✅ Úsalo para: búsquedas, filtros, paginación — datos que pueden verse en la URL.  
> ❌ Nunca para: contraseñas, datos sensibles.

---

### 3️⃣ POST con JSON → respuesta en texto

> 📄 Ver: `post-get.html` botón 3 · `procesar.php` bloque 3

**JavaScript — enviar un objeto:**

```js
const datos = { nombre: "Ana", edad: 22 };

const respuesta = await fetch("procesar.php", {
  method: "POST",
  headers: { "Content-Type": "application/json" },
  body: JSON.stringify({
    accion: "json_datos",
    persona: datos,
  }),
});

const texto = await respuesta.text();
```

**PHP — recibir JSON:**

```php
// ⚠️ Con JSON no se usa $_POST — hay que leer el body raw
$rawJSON = file_get_contents("php://input");
$data = json_decode($rawJSON, true);

if (isset($data["accion"]) && $data["accion"] === "json_datos") {
    $nombre = $data["persona"]["nombre"] ?? "Sin nombre";
    $edad   = $data["persona"]["edad"]   ?? -1;
    $mayoria = ($edad >= 18) ? "Mayor de edad." : "Menor de edad.";
    echo "Hola $nombre, tienes $edad años. $mayoria";
    exit;
}
```

> ✅ Úsalo para: enviar objetos o arrays completos al servidor.

---

### 4️⃣ POST con JSON → respuesta en JSON ⭐ (el más completo)

> 📄 Ver: `post-get.html` botón 4 · `procesar.php` bloque 4

**JavaScript — enviar y recibir JSON:**

```js
const respuesta = await fetch("procesar.php", {
  method: "POST",
  headers: {
    "Content-Type": "application/json",
    Accept: "application/json",
  },
  body: JSON.stringify({
    accion: "json_datos_json",
    persona: { nombre: "Ana", edad: 22 },
  }),
});

const json = await respuesta.json(); // parsea automáticamente

// Acceder a los campos de la respuesta
console.log(json.saludo); // "Hola Ana"
console.log(json.mensaje); // "Eres mayor de edad"
console.log(json.mayor_de_edad); // true
```

**PHP — recibir y devolver JSON:**

```php
$rawJSON = file_get_contents("php://input");
$data    = json_decode($rawJSON, true);

if (isset($data["accion"]) && $data["accion"] === "json_datos_json") {

    header("Content-Type: application/json; charset=utf-8"); // ⚠️ cambiar header

    $nombre  = $data["persona"]["nombre"] ?? "Sin nombre";
    $edad    = $data["persona"]["edad"]   ?? -1;
    $mayoria = ($edad >= 18);

    $respuesta = [
        "saludo"        => "Hola $nombre",
        "edad"          => $edad,
        "mayor_de_edad" => $mayoria,
        "mensaje"       => $mayoria ? "Eres mayor de edad" : "Eres menor de edad"
    ];

    echo json_encode($respuesta, JSON_PRETTY_PRINT);
    exit;
}
```

> ✅ Úsalo para: APIs, aplicaciones CRUD, cualquier proyecto real donde el servidor devuelve datos estructurados.

---

## 🍪 Cookies

> 📄 Ver: `cookies.html` · `cookies.php`

Las cookies guardan datos en el navegador del usuario que **persisten entre páginas y sesiones**.

### Desde JavaScript

```js
// Crear cookie (duración en días)
function setCookie(nombre, valor, dias) {
  const fecha = new Date();
  fecha.setTime(fecha.getTime() + dias * 24 * 60 * 60 * 1000);
  document.cookie =
    nombre +
    "=" +
    encodeURIComponent(valor) +
    "; expires=" +
    fecha.toUTCString() +
    "; path=/";
}

// Leer cookie
function getCookie(nombre) {
  const nombreEQ = nombre + "=";
  const cookies = document.cookie.split(";");
  for (let c of cookies) {
    c = c.trim();
    if (c.indexOf(nombreEQ) === 0)
      return decodeURIComponent(c.substring(nombreEQ.length));
  }
  return null;
}

// Borrar cookie (se borra poniendo fecha pasada)
function deleteCookie(nombre) {
  document.cookie =
    nombre + "=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";
}
```

### Desde PHP

```php
// Crear cookie desde el servidor
// ⚠️ setcookie() SIEMPRE antes de cualquier echo o header
setcookie("mensaje_servidor", "Hola desde PHP", [
    "expires"  => time() + 3600,   // 1 hora
    "path"     => "/",
    "secure"   => false,           // true si usas HTTPS
    "httponly" => false,           // false = JS puede leerla
    "samesite" => "Lax"
]);

// Leer cookie en PHP (llega automáticamente en cada petición)
$valor = $_COOKIE["nombre_cookie"] ?? "no existe";
```

### Flujo cliente ↔ servidor con cookies

```
Cliente (JS)                        Servidor (PHP)
     │                                    │
     │  setCookie("usuario", "Ana")       │
     │  ──────────────────────────────►  │
     │       fetch() con credentials     │
     │                                    │  $_COOKIE["usuario"] = "Ana"
     │                                    │  setcookie("mensaje_servidor", ...)
     │  ◄──────────────────────────────  │
     │  getCookie("mensaje_servidor")     │
     │  → "Hola Ana desde el servidor"   │
```

---

## ⚠️ Reglas críticas para no olvidar

| Regla                                             | Por qué                                                        |
| ------------------------------------------------- | -------------------------------------------------------------- |
| Con JSON usar `file_get_contents("php://input")`  | Con JSON `$_POST` está vacío — los datos llegan en el body raw |
| `setcookie()` antes de cualquier `echo`           | Los headers deben enviarse antes que el cuerpo                 |
| `credentials: "same-origin"` en fetch con cookies | Sin esto el navegador no envía ni acepta cookies en fetch      |
| `httponly: false` si JS necesita leer la cookie   | `httponly: true` hace la cookie invisible para JavaScript      |
| `encodeURIComponent` al enviar datos en URL/body  | Evita que caracteres especiales rompan la petición             |

---

## 🔁 Función reutilizable — fetch genérico con JSON

```js
// Envía datos a cualquier PHP y recibe la respuesta como objeto
async function enviarAlServidor(archivo, accion, datos = {}) {
  const respuesta = await fetch(archivo, {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
      Accept: "application/json",
    },
    body: JSON.stringify({ accion, ...datos }),
  });
  return await respuesta.json();
}

// Uso:
// const resultado = await enviarAlServidor("procesar.php", "json_datos_json", {
//     persona: { nombre: "Ana", edad: 22 }
// });
// console.log(resultado.saludo);
```

## 🔁 Función reutilizable — PHP: leer JSON del body

```php
// Pegar al principio de cualquier PHP que reciba JSON
function leerJSON() {
    $raw  = file_get_contents("php://input");
    $data = json_decode($raw, true);
    return $data ?? [];
}

function responderJSON($datos) {
    header("Content-Type: application/json; charset=utf-8");
    echo json_encode($datos, JSON_PRETTY_PRINT);
    exit;
}

// Uso:
// $data = leerJSON();
// $accion = $data["accion"] ?? "";
//
// if ($accion === "mi_accion") {
//     responderJSON(["ok" => true, "mensaje" => "Procesado"]);
// }
```
