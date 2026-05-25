# Servicio SOAP de acceso a examen

Este ejercicio resuelve el enunciado `accesoExamen.txt`.

## Archivos

- `accesoExamen.html`: cliente web que genera y envia la peticion SOAP.
- `accesoExamen.php`: servidor SOAP que valida los datos y responde XML.
- `accesoExamen.wsdl`: contrato formal del servicio.

## Regla del ejercicio

Un alumno puede acceder al examen si:

- tiene mas de 16 anos,
- esta matriculado.

## Datos que recibe

```xml
<validarAccesoExamen>
  <nombre>Ana</nombre>
  <edad>18</edad>
  <matriculado>true</matriculado>
</validarAccesoExamen>
```

## Datos que devuelve

```xml
<validarAccesoExamenResponse>
  <permitido>true</permitido>
  <mensaje>Ana puede acceder al examen...</mensaje>
</validarAccesoExamenResponse>
```

## URL de prueba

```text
http://localhost/daw2servidor_RCT/daw2servidor_2026/007-Programacion_Servicios_web/004-Interface_servicio_web/acceso_examen/accesoExamen.html
```
