# 008/002 - Generación dinámica de páginas interactivas

## ⚡ Idea central

```
El usuario NO recarga la página.
JavaScript pide datos al servidor con fetch().
El servidor responde JSON.
JavaScript actualiza solo una parte del DOM.
```

---

## 🗂️ Archivos

```
cliente.html                        ← interfaz + JavaScript
controlador.php                     ← punto de entrada único
servicios/servicioVideojuegos.php   ← lógica de videojuegos
servicios/servicioAlumonos.php      ← lógica de alumnos (ojo al typo)
datos/videojuegos.json              ← datos persistidos
datos/alumnos.json                  ← datos persistidos
```

> ⚠️ El archivo se llama `servicioAlumonos.php` pero el controlador referencia `servicioAlumnos.php`.  
> Si falla el servicio de alumnos, revisa que el nombre del archivo coincide exactamente.

---

## 📦 Flujo completo

```
cliente.html
  → fetch("controlador.php/videojuegos")
  → controlador.php lee ruta y método
  → carga servicios/servicioVideojuegos.php
  → llama servicioVideojuegos($metodo, $datosEntrada)
  → devuelve ["codigo" => 200, "datos" => [...]]
  → controlador.php responde JSON
  → cliente.html actualiza el DOM
```

---

## 🖥️ 1. controlador.php — el punto de entrada único

```php
<?php
header("Content-Type: application/json; charset=utf-8");

// Mapa de servicios — añade aquí cada recurso nuevo
$servicios = [
    "videojuegos" => [
        "archivo" => "servicios/servicioVideojuegos.php",
        "funcion" => "servicioVideojuegos"
    ],
    "alumnos" => [
        "archivo" => "servicios/servicioAlumnos.php",
        "funcion" => "servicioAlumnos"
    ]
];

$metodo = $_SERVER["REQUEST_METHOD"];

// PATH_INFO lee lo que hay después de controlador.php
// fetch("controlador.php/videojuegos") → PATH_INFO = "/videojuegos"
$ruta = $_SERVER["PATH_INFO"] ?? "";
$ruta = trim($ruta, "/");

// Sin servicio indicado
if ($ruta === "") {
    responderJson(["error" => "No se ha indicado ningún servicio"], 400);
}

// Servicio no existe
if (!isset($servicios[$ruta])) {
    responderJson(["error" => "Servicio no encontrado"], 404);
}

// Cargar solo el archivo necesario
$archivoServicio = $servicios[$ruta]["archivo"];
$funcionServicio = $servicios[$ruta]["funcion"];

require_once $archivoServicio;

// Leer JSON del body (llega en POST)
$entrada      = file_get_contents("php://input");
$datosEntrada = json_decode($entrada, true);
if ($datosEntrada === null) $datosEntrada = [];

// Delegar al servicio y responder
$resultado = $funcionServicio($metodo, $datosEntrada);
responderJson($resultado["datos"], $resultado["codigo"]);

// Función reutilizable de respuesta
function responderJson($datos, $codigo = 200) {
    http_response_code($codigo);
    echo json_encode($datos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}
?>
```

---

## 🖥️ 2. servicioVideojuegos.php — patrón del servicio

```php
<?php
function servicioVideojuegos($metodo, $datosEntrada) {
    $archivo = "datos/videojuegos.json";

    if ($metodo === "GET")  return consultarVideojuegos($archivo);
    if ($metodo === "POST") return añadirVideojuego($archivo, $datosEntrada);

    return ["codigo" => 405, "datos" => ["error" => "Método no permitido para videojuegos"]];
}

function consultarVideojuegos($archivo) {
    return ["codigo" => 200, "datos" => leerJson($archivo)];
}

function añadirVideojuego($archivo, $datosEntrada) {
    // Validar campos obligatorios
    if (!isset($datosEntrada["titulo"]) || trim($datosEntrada["titulo"]) === "") {
        return ["codigo" => 400, "datos" => ["error" => "El título del videojuego es obligatorio"]];
    }
    if (!isset($datosEntrada["genero"]) || trim($datosEntrada["genero"]) === "") {
        return ["codigo" => 400, "datos" => ["error" => "El género del videojuego es obligatorio"]];
    }

    $videojuegos = leerJson($archivo);

    $nuevoVideojuego = [
        "id"     => generarNuevoId($videojuegos),
        "titulo" => $datosEntrada["titulo"],
        "genero" => $datosEntrada["genero"]
    ];

    $videojuegos[] = $nuevoVideojuego;
    guardarJson($archivo, $videojuegos);

    return [
        "codigo" => 201,
        "datos"  => ["mensaje" => "Videojuego añadido correctamente", "videojuego" => $nuevoVideojuego]
    ];
}

// ── Funciones de archivo ─────────────────────────────────────────

function leerJson($archivo) {
    if (!file_exists($archivo)) return [];
    $datos = json_decode(file_get_contents($archivo), true);
    return $datos ?? [];
}

function guardarJson($archivo, $datos) {
    file_put_contents($archivo, json_encode($datos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

function generarNuevoId($datos) {
    $mayorId = 0;
    foreach ($datos as $elemento) {
        if ($elemento["id"] > $mayorId) $mayorId = $elemento["id"];
    }
    return $mayorId + 1;
}
?>
```

---

## 🌐 3. cliente.html — JavaScript

### Constantes de URL

```javascript
const API_VIDEOJUEGOS = "controlador.php/videojuegos";
const API_ALUMNOS = "controlador.php/alumnos";
```

### GET — cargar y pintar lista

```javascript
function cargarVideojuegos() {
  limpiarMensaje(mensajeVideojuegos);

  fetch(API_VIDEOJUEGOS)
    .then(function (respuesta) {
      return respuesta.json();
    })
    .then(function (videojuegos) {
      mostrarVideojuegos(videojuegos);
    })
    .catch(function (error) {
      mostrarMensaje(
        mensajeVideojuegos,
        "Error al cargar videojuegos",
        "error",
      );
    });
}
```

### POST — enviar formulario con `.then()`

```javascript
function añadirVideojuego() {
  const titulo = document.getElementById("tituloVideojuego").value;
  const genero = document.getElementById("generoVideojuego").value;

  limpiarMensaje(mensajeVideojuegos);

  fetch(API_VIDEOJUEGOS, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ titulo: titulo, genero: genero }),
  })
    // Paso 1 — mezcla código HTTP y datos JSON en un objeto
    .then(function (respuesta) {
      return respuesta.json().then(function (datos) {
        return { codigo: respuesta.status, datos: datos };
      });
    })
    // Paso 2 — decide qué mostrar
    .then(function (resultado) {
      if (resultado.codigo === 201) {
        mostrarMensaje(mensajeVideojuegos, resultado.datos.mensaje, "ok");
        formVideojuego.reset();
        cargarVideojuegos(); // refresca la lista
      } else {
        mostrarMensaje(mensajeVideojuegos, resultado.datos.error, "error");
      }
    })
    .catch(function (error) {
      mostrarMensaje(mensajeVideojuegos, "Error al añadir videojuego", "error");
    });
}
```

### Pintar lista en el DOM

```javascript
function mostrarVideojuegos(videojuegos) {
  listadoVideojuegos.innerHTML = "";

  if (videojuegos.length === 0) {
    listadoVideojuegos.innerHTML = "<p>No hay videojuegos almacenados.</p>";
    return;
  }

  const ul = document.createElement("ul");

  videojuegos.forEach(function (videojuego) {
    const li = document.createElement("li");
    li.textContent =
      videojuego.id +
      " - " +
      videojuego.titulo +
      " (" +
      videojuego.genero +
      ")";
    ul.appendChild(li);
  });

  listadoVideojuegos.appendChild(ul);
}
```

### Mensajes de error o éxito

```javascript
function mostrarMensaje(elemento, texto, tipo) {
  elemento.textContent = texto;
  elemento.className = tipo; // clase CSS "ok" o "error"
}

function limpiarMensaje(elemento) {
  elemento.textContent = "";
  elemento.className = "";
}
```

---

## 🔧 Funciones reutilizables

### `responderJson($datos, $codigo)` — controlador.php

> **Para qué sirve:** centraliza todas las respuestas JSON en un solo sitio. Así no repites `http_response_code` y `json_encode` en cada servicio.  
> **Dónde se llama:** al final del controlador, después de que el servicio devuelve su resultado.

```php
// ✅ Copiar tal cual — no necesita cambios
function responderJson($datos, $codigo = 200) {
    http_response_code($codigo);  // establece el código HTTP (200, 201, 404...)
    echo json_encode($datos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit; // para la ejecución — nada más se envía al cliente
}
```

---

### Mapa `$servicios[]` — controlador.php

> **Para qué sirve:** es el "directorio" del controlador. Asocia cada nombre de ruta con su archivo PHP y su función. Sin este mapa el controlador no sabe a quién llamar.  
> **Dónde se usa:** el controlador lee `$_SERVER["PATH_INFO"]`, busca en este mapa y carga el archivo correcto.

```php
// ⚠️ Cambiar: nombres de recursos, rutas de archivos y nombres de funciones
$servicios = [
    "videojuegos" => [                                    // ← nombre que va en la URL: controlador.php/videojuegos
        "archivo" => "servicios/servicioVideojuegos.php", // ← archivo PHP que contiene la lógica
        "funcion" => "servicioVideojuegos"                // ← función principal dentro de ese archivo
    ],
    "alumnos" => [                                        // ← añadir más recursos aquí
        "archivo" => "servicios/servicioAlumnos.php",
        "funcion" => "servicioAlumnos"
    ]
];
```

---

### Leer JSON del body — controlador.php

> **Para qué sirve:** recoge los datos que el cliente envía en el cuerpo del POST. Sin esto PHP no puede leer el JSON que manda `fetch()`.  
> **Dónde se usa:** justo antes de llamar al servicio, para pasarle `$datosEntrada`.

```php
// ✅ Copiar tal cual — no necesita cambios
$entrada      = file_get_contents("php://input"); // lee el body crudo del POST
$datosEntrada = json_decode($entrada, true);      // convierte JSON string → array PHP
if ($datosEntrada === null) $datosEntrada = [];   // si no llega JSON, usa array vacío
```

---

### `servicioVideojuegos($metodo, $datosEntrada)` — servicioVideojuegos.php

> **Para qué sirve:** es la función de entrada del servicio. Recibe el método HTTP y los datos, y decide si leer o escribir. Es el equivalente al `switch` de método que tenías en la carpeta 005.  
> **Dónde se llama:** el controlador la llama así → `$resultado = servicioVideojuegos($metodo, $datosEntrada)`.  
> **Qué devuelve:** siempre un array con dos claves: `["codigo" => 200, "datos" => [...]]`.

```php
// ⚠️ Cambiar: nombre de la función y del archivo JSON
function servicioVideojuegos($metodo, $datosEntrada) {
    $archivo = "datos/videojuegos.json"; // ← cambiar por tu archivo JSON

    if ($metodo === "GET")  return consultarVideojuegos($archivo);           // ← cambiar nombre
    if ($metodo === "POST") return añadirVideojuego($archivo, $datosEntrada); // ← cambiar nombre

    return ["codigo" => 405, "datos" => ["error" => "Método no permitido"]]; // ✅ no cambia
}
```

---

### `añadirVideojuego($archivo, $datosEntrada)` — servicioVideojuegos.php

> **Para qué sirve:** valida los campos recibidos, genera el nuevo elemento con ID, lo añade al JSON y devuelve el resultado al controlador.  
> **Dónde se llama:** la llama `servicioVideojuegos()` cuando el método es POST.  
> **Qué devuelve:** `["codigo" => 201, "datos" => ["mensaje" => "...", "videojuego" => $nuevo]]` si todo va bien, o `["codigo" => 400, "datos" => ["error" => "..."]]` si falta algún campo.

```php
// ⚠️ Cambiar: nombre función, campos validados y campos del nuevo objeto
function añadirVideojuego($archivo, $datosEntrada) {

    // ⚠️ Cambiar "titulo" y "genero" por los campos de tu recurso
    if (!isset($datosEntrada["titulo"]) || trim($datosEntrada["titulo"]) === "") {
        return ["codigo" => 400, "datos" => ["error" => "El título es obligatorio"]];
    }
    if (!isset($datosEntrada["genero"]) || trim($datosEntrada["genero"]) === "") {
        return ["codigo" => 400, "datos" => ["error" => "El género es obligatorio"]];
    }

    $lista = leerJson($archivo); // ✅ no cambia — lee el JSON actual

    $nuevo = [
        "id"     => generarNuevoId($lista),    // ✅ no cambia — genera ID automático
        "titulo" => $datosEntrada["titulo"],    // ⚠️ cambiar por tus campos
        "genero" => $datosEntrada["genero"]     // ⚠️ cambiar por tus campos
    ];

    $lista[] = $nuevo;           // añade al array
    guardarJson($archivo, $lista); // ✅ no cambia — guarda en el JSON

    return [
        "codigo" => 201,
        "datos"  => [
            "mensaje"    => "Videojuego añadido correctamente", // ⚠️ cambiar texto
            "videojuego" => $nuevo                              // ⚠️ cambiar clave por tu recurso
        ]
    ];
}
```

---

### `leerJson($archivo)` — servicioVideojuegos.php

> **Para qué sirve:** lee el archivo `.json` y lo convierte en array PHP. Si el archivo no existe devuelve array vacío en vez de un error.  
> **Dónde se usa:** en `consultarVideojuegos()` y al inicio de `añadirVideojuego()`.

```php
// ✅ Copiar tal cual — no necesita cambios
function leerJson($archivo) {
    if (!file_exists($archivo)) return []; // si no existe el archivo devuelve vacío
    return json_decode(file_get_contents($archivo), true) ?? [];
}
```

---

### `guardarJson($archivo, $datos)` — servicioVideojuegos.php

> **Para qué sirve:** convierte el array PHP en JSON y lo escribe en el archivo. Es la "escritura en base de datos" cuando no hay MySQL.  
> **Dónde se usa:** al final de `añadirVideojuego()`, después de añadir el nuevo elemento.

```php
// ✅ Copiar tal cual — no necesita cambios
function guardarJson($archivo, $datos) {
    file_put_contents(
        $archivo,
        json_encode($datos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        // JSON_UNESCAPED_UNICODE → guarda tildes y ñ correctamente
    );
}
```

---

### `generarNuevoId($datos)` — servicioVideojuegos.php

> **Para qué sirve:** busca el ID más alto del array y devuelve el siguiente. Equivale al `AUTO_INCREMENT` de MySQL pero sin base de datos.  
> **Dónde se usa:** dentro de `añadirVideojuego()` al crear el nuevo objeto.

```php
// ✅ Copiar tal cual — no necesita cambios
function generarNuevoId($datos) {
    $mayorId = 0;
    foreach ($datos as $elemento) {
        if ($elemento["id"] > $mayorId) $mayorId = $elemento["id"];
    }
    return $mayorId + 1; // si hay [1,2,3] devuelve 4
}
```

---

### `cargarVideojuegos()` — cliente.html

> **Para qué sirve:** hace un GET al controlador, recibe el array JSON y llama a `mostrarVideojuegos()` para pintarlo en el DOM. Se llama al pulsar el botón y después de añadir un elemento nuevo.  
> **Dónde se usa:** en el evento click del botón y al final de `añadirVideojuego()` para refrescar la lista.

```javascript
// ⚠️ Cambiar: nombre función, constante API y función mostrar
function cargarVideojuegos() {
  limpiarMensaje(mensajeVideojuegos); // ⚠️ cambiar variable de mensaje

  fetch(API_VIDEOJUEGOS) // ⚠️ cambiar constante de URL
    .then(function (respuesta) {
      return respuesta.json(); // ✅ no cambia — parsea el JSON
    })
    .then(function (videojuegos) {
      mostrarVideojuegos(videojuegos); // ⚠️ cambiar por tu función mostrar
    })
    .catch(function (error) {
      mostrarMensaje(mensajeVideojuegos, "Error al cargar", "error");
    });
}
```

---

### `añadirVideojuego()` — cliente.html

> **Para qué sirve:** recoge los valores del formulario, los envía como JSON con POST y gestiona la respuesta. Si el servidor devuelve 201 refresca la lista; si devuelve 400 muestra el error.  
> **Dónde se usa:** en el evento submit del formulario → `formVideojuego.addEventListener("submit", ...)`.  
> **Patrón clave:** el `.then()` anidado mezcla `respuesta.status` y `respuesta.json()` en un solo objeto para poder usar los dos juntos.

```javascript
// ⚠️ Cambiar: ids de inputs, constante API, campos del JSON y variables de mensaje/form
function añadirVideojuego() {
  const titulo = document.getElementById("tituloVideojuego").value; // ⚠️ cambiar id
  const genero = document.getElementById("generoVideojuego").value; // ⚠️ cambiar id

  limpiarMensaje(mensajeVideojuegos); // ⚠️ cambiar variable

  fetch(API_VIDEOJUEGOS, {
    // ⚠️ cambiar constante
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({
      titulo: titulo, // ⚠️ cambiar por tus campos — deben coincidir con lo que valida el servicio PHP
      genero: genero, // ⚠️ cambiar por tus campos
    }),
  })
    .then(function (respuesta) {
      // ✅ Este patrón no cambia — necesario para tener código HTTP y datos juntos
      return respuesta.json().then(function (datos) {
        return { codigo: respuesta.status, datos: datos };
      });
    })
    .then(function (resultado) {
      if (resultado.codigo === 201) {
        mostrarMensaje(mensajeVideojuegos, resultado.datos.mensaje, "ok");
        formVideojuego.reset(); // ⚠️ cambiar variable del form
        cargarVideojuegos(); // ⚠️ cambiar por tu función cargar — refresca la lista
      } else {
        mostrarMensaje(mensajeVideojuegos, resultado.datos.error, "error");
      }
    })
    .catch(function (error) {
      mostrarMensaje(mensajeVideojuegos, "Error al añadir", "error");
    });
}
```

---

### `mostrarVideojuegos(lista)` — cliente.html

> **Para qué sirve:** recibe el array de objetos y crea una lista `<ul><li>` en el DOM. Actualiza el HTML sin recargar la página.  
> **Dónde se usa:** la llama `cargarVideojuegos()` cuando recibe la respuesta del servidor.

```javascript
// ⚠️ Cambiar: nombre función, variable contenedor y campos del <li>
function mostrarVideojuegos(videojuegos) {
  listadoVideojuegos.innerHTML = ""; // ⚠️ cambiar variable contenedor — limpia antes de pintar

  if (videojuegos.length === 0) {
    listadoVideojuegos.innerHTML = "<p>No hay videojuegos.</p>"; // ⚠️ cambiar texto y variable
    return;
  }

  const ul = document.createElement("ul"); // ✅ no cambia

  videojuegos.forEach(function (videojuego) {
    const li = document.createElement("li");
    // ⚠️ Cambiar los campos que muestra según tu recurso
    li.textContent =
      videojuego.id +
      " - " +
      videojuego.titulo +
      " (" +
      videojuego.genero +
      ")";
    ul.appendChild(li);
  });

  listadoVideojuegos.appendChild(ul); // ⚠️ cambiar variable contenedor
}
```

---

### `mostrarMensaje()` / `limpiarMensaje()` — cliente.html

> **Para qué sirve:** `mostrarMensaje()` pinta un texto en un elemento HTML con una clase CSS (`"ok"` → verde, `"error"` → rojo). `limpiarMensaje()` borra el mensaje anterior antes de cada petición para que no se acumulen.  
> **Dónde se usan:** `limpiarMensaje()` al inicio de cada función fetch. `mostrarMensaje()` en el `.then()` según el código HTTP recibido.

```javascript
// ✅ Copiar tal cual — no necesitan cambios
function mostrarMensaje(elemento, texto, tipo) {
  elemento.textContent = texto;
  elemento.className = tipo; // "ok" → verde / "error" → rojo (según CSS del <style>)
}

function limpiarMensaje(elemento) {
  elemento.textContent = "";
  elemento.className = "";
}
```

---

---

### `añadirVideojuego($archivo, $datosEntrada)` — servicioVideojuegos.php

```php
// ⚠️ Cambiar: nombre función, campos validados y campos del nuevo objeto
function añadirVideojuego($archivo, $datosEntrada) {

    // ⚠️ Cambiar "titulo" y "genero" por los campos de tu recurso
    if (!isset($datosEntrada["titulo"]) || trim($datosEntrada["titulo"]) === "") {
        return ["codigo" => 400, "datos" => ["error" => "El título es obligatorio"]];
    }
    if (!isset($datosEntrada["genero"]) || trim($datosEntrada["genero"]) === "") {
        return ["codigo" => 400, "datos" => ["error" => "El género es obligatorio"]];
    }

    $lista = leerJson($archivo); // ✅ no cambia

    $nuevo = [
        "id"     => generarNuevoId($lista),    // ✅ no cambia
        "titulo" => $datosEntrada["titulo"],    // ⚠️ cambiar por tus campos
        "genero" => $datosEntrada["genero"]     // ⚠️ cambiar por tus campos
    ];

    $lista[] = $nuevo;
    guardarJson($archivo, $lista); // ✅ no cambia

    return [
        "codigo" => 201,
        "datos"  => [
            "mensaje"     => "Videojuego añadido correctamente", // ⚠️ cambiar texto
            "videojuego"  => $nuevo                              // ⚠️ cambiar clave
        ]
    ];
}
```

---

### `leerJson()` / `guardarJson()` / `generarNuevoId()` — servicioVideojuegos.php

```php
// ✅ Copiar tal cual — no necesitan cambios
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
    return $mayorId + 1;
}
```

---

### `cargarVideojuegos()` — cliente.html

```javascript
// ⚠️ Cambiar: nombre función, constante API y función mostrar
function cargarVideojuegos() {
  limpiarMensaje(mensajeVideojuegos); // ⚠️ cambiar variable de mensaje

  fetch(API_VIDEOJUEGOS) // ⚠️ cambiar constante de URL
    .then(function (respuesta) {
      return respuesta.json(); // ✅ no cambia
    })
    .then(function (videojuegos) {
      mostrarVideojuegos(videojuegos); // ⚠️ cambiar por tu función mostrar
    })
    .catch(function (error) {
      mostrarMensaje(mensajeVideojuegos, "Error al cargar", "error");
    });
}
```

---

### `añadirVideojuego()` — cliente.html

```javascript
// ⚠️ Cambiar: ids de inputs, constante API, campos del JSON y variables de mensaje/form
function añadirVideojuego() {
  const titulo = document.getElementById("tituloVideojuego").value; // ⚠️ cambiar id
  const genero = document.getElementById("generoVideojuego").value; // ⚠️ cambiar id

  limpiarMensaje(mensajeVideojuegos); // ⚠️ cambiar variable

  fetch(API_VIDEOJUEGOS, {
    // ⚠️ cambiar constante
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({
      titulo: titulo, // ⚠️ cambiar por tus campos
      genero: genero, // ⚠️ cambiar por tus campos
    }),
  })
    .then(function (respuesta) {
      // ✅ Este patrón no cambia — mezcla código HTTP y datos
      return respuesta.json().then(function (datos) {
        return { codigo: respuesta.status, datos: datos };
      });
    })
    .then(function (resultado) {
      if (resultado.codigo === 201) {
        mostrarMensaje(mensajeVideojuegos, resultado.datos.mensaje, "ok");
        formVideojuego.reset(); // ⚠️ cambiar variable del form
        cargarVideojuegos(); // ⚠️ cambiar por tu función cargar
      } else {
        mostrarMensaje(mensajeVideojuegos, resultado.datos.error, "error");
      }
    })
    .catch(function (error) {
      mostrarMensaje(mensajeVideojuegos, "Error al añadir", "error");
    });
}
```

---

### `mostrarVideojuegos(lista)` — cliente.html

```javascript
// ⚠️ Cambiar: nombre función, variable contenedor y campos del <li>
function mostrarVideojuegos(videojuegos) {
  listadoVideojuegos.innerHTML = ""; // ⚠️ cambiar variable contenedor

  if (videojuegos.length === 0) {
    listadoVideojuegos.innerHTML = "<p>No hay videojuegos.</p>"; // ⚠️ cambiar texto y variable
    return;
  }

  const ul = document.createElement("ul"); // ✅ no cambia

  videojuegos.forEach(function (videojuego) {
    const li = document.createElement("li");
    // ⚠️ Cambiar los campos que muestra
    li.textContent =
      videojuego.id +
      " - " +
      videojuego.titulo +
      " (" +
      videojuego.genero +
      ")";
    ul.appendChild(li);
  });

  listadoVideojuegos.appendChild(ul); // ⚠️ cambiar variable contenedor
}
```

---

### `mostrarMensaje()` / `limpiarMensaje()` — cliente.html

```javascript
// ✅ Copiar tal cual — no necesitan cambios
function mostrarMensaje(elemento, texto, tipo) {
  elemento.textContent = texto;
  elemento.className = tipo; // "ok" → verde / "error" → rojo (según CSS)
}

function limpiarMensaje(elemento) {
  elemento.textContent = "";
  elemento.className = "";
}
```

---

| Pregunta                           | Respuesta                                                         |
| ---------------------------------- | ----------------------------------------------------------------- |
| ¿Qué hace `controlador.php`?       | Lee ruta y método, carga el servicio correcto, devuelve JSON      |
| ¿Cómo lee PHP la ruta?             | `$_SERVER["PATH_INFO"]` → `/videojuegos` → `trim` → `videojuegos` |
| ¿Cómo añado un recurso nuevo?      | Archivo JSON + archivo servicio + entrada en `$servicios[]`       |
| ¿Qué devuelve siempre un servicio? | `["codigo" => 2xx/4xx, "datos" => [...]]`                         |
| ¿Cómo lee PHP el JSON del POST?    | `file_get_contents("php://input")` + `json_decode()`              |
| ¿Cómo creo un ID sin MySQL?        | `generarNuevoId()` → busca el mayor y suma 1                      |
| ¿Qué hace `.then()` anidado?       | Combina `respuesta.status` y `respuesta.json()` en un objeto      |
| Error típico de examen             | Nombre del archivo distinto al que referencia el controlador      |
