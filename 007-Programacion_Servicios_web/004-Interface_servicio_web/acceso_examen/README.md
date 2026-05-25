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
