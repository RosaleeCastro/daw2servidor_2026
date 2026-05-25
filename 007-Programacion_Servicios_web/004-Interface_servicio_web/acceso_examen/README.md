# Acceso a examen SOAP

## Que hace

Valida si un alumno puede acceder a un examen.

Regla:

```text
permitido = edad > 16 y matriculado = true
```

## Herramientas

- HTML
- JavaScript
- PHP
- SOAP
- XML
- WSDL

## Archivos

```text
accesoExamen.html
accesoExamen.php
accesoExamen.wsdl
README.md
```

## Peticion SOAP

Consulta tambien el indice general: `../../README.md` y el README de la carpeta padre `../README.md`.

## Acceso rapido a funciones reutilizables

| Funcion o patron | Archivo | Para que sirve |
| --- | --- | --- |
| Lectura de `php://input` | `accesoExamen.php` | Recibir el XML SOAP enviado por el cliente. |
| `DOMDocument` + `DOMXPath` | `accesoExamen.php` | Buscar nodos del XML aunque cambie el prefijo SOAP. |
| `responderFault()` | `accesoExamen.php` | Devolver errores SOAP cuando faltan datos o son invalidos. |
| Validacion de booleanos | `accesoExamen.php` | Convertir texto `true` / `false` en valores logicos. |
| Regla de negocio `edad > 16 && matriculado` | `accesoExamen.php` | Separar la decision del formato SOAP. |
| Cliente SOAP con `fetch()` | `accesoExamen.html` | Enviar XML y mostrar la respuesta en navegador. |
| Contrato WSDL | `accesoExamen.wsdl` | Documentar entrada y salida del servicio. |

## Que copiar en otro ejercicio

Si el examen pide validar acceso, permisos o autorizaciones, copia esta estructura:

1. El cliente envia datos dentro de `soap:Body`.
2. Opcionalmente envia informacion tecnica dentro de `soap:Header`.
3. PHP valida cada campo.
4. Si algo esta mal, responde `soap:Fault`.
5. Si todo esta bien, aplica la regla y devuelve una respuesta SOAP normal.

```xml
<soap:Envelope>
  <soap:Header>
    <clienteInfo>
      <aplicacion>DAW2</aplicacion>
      <requestId>EXAM-123</requestId>
    </clienteInfo>
  </soap:Header>
  <soap:Body>
    <validarAccesoExamen>
      <nombre>Ana</nombre>
      <edad>18</edad>
      <matriculado>true</matriculado>
    </validarAccesoExamen>
  </soap:Body>
</soap:Envelope>
```

## Respuesta SOAP

```xml
<validarAccesoExamenResponse>
  <permitido>true</permitido>
  <mensaje>Ana puede acceder al examen...</mensaje>
</validarAccesoExamenResponse>
```

## Validaciones reutilizables

```php
if ($nombre === "") {
    responderFault("El nombre no puede estar vacio.");
}

if (!is_numeric($edadTexto)) {
    responderFault("La edad debe ser numerica.");
}

if ($matriculadoTexto !== "true" && $matriculadoTexto !== "false") {
    responderFault("El campo matriculado debe ser true o false.");
}
```

## Para examen

Si falta un campo o es invalido, devuelve `soap:Fault`.

Si todo es valido, devuelve una respuesta normal con `permitido` y `mensaje`.
