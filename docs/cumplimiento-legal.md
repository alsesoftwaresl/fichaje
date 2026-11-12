# Cumplimiento legal

Qué normas aplican a Achrono, cómo las cumple la aplicación y qué le toca hacer a cada empresa
cliente. **No sustituye a un abogado o graduado social**: antes de vender, que un profesional
revise los textos legales (`resources/views/legal/`) y este documento.

Estado a noviembre de 2026.

## 1. Normas que aplican

| Norma | Qué exige | Situación |
|---|---|---|
| **Art. 34.9 del Estatuto de los Trabajadores** (RD-ley 8/2019) | Registro diario de jornada con hora de inicio y fin de cada persona; conservarlo **4 años**; a disposición de los trabajadores, sus representantes y la Inspección. | En vigor. |
| **Art. 12.4.c ET** | En el tiempo parcial, registro diario **totalizado cada mes** y entrega de copia del resumen con la nómina. | En vigor. |
| **Art. 35.5 ET** | Las horas extraordinarias se registran día a día y se totalizan; copia del resumen al trabajador. | En vigor. |
| **LISOS** (art. 7.5) | No llevar el registro es infracción grave. | En vigor. |
| **Proyecto de real decreto de registro digital** | Registro solo digital, inalterable, con trazabilidad de cambios, acceso del trabajador y acceso remoto de la Inspección. | **No aprobado** a 12 de noviembre de 2026 (nada en el BOE). Dictamen desfavorable del Consejo de Estado (marzo de 2026); Trabajo dio por terminada la revisión del texto en octubre y el Gobierno ha dicho que quiere aprobarlo antes de las elecciones del 29 de noviembre. Tendrá un plazo de adaptación desde que se publique. Achrono ya cumple lo técnico conocido del borrador salvo el acceso remoto de la Inspección (ver 5). **Revisar el texto final en cuanto salga en el BOE.** |
| **RGPD** y **LOPDGDD** | Licitud, minimización, seguridad (art. 32), encargo de tratamiento (art. 28), derechos (arts. 15-22), brechas (arts. 33-34), plazos de conservación. | En vigor. |
| **Art. 88 LOPDGDD y art. 20 bis ET** | Derecho a la desconexión digital. | Lo regula cada empresa (ver 3). |
| **LSSI** | Aviso legal (art. 10) y cookies (art. 22.2). | En vigor. |

## 2. Cómo lo cumple la aplicación

### 2.1 Registro de jornada

| Requisito | Dónde está |
|---|---|
| Registro diario de entrada y salida, con la hora del servidor (nunca la del dispositivo) | `RegistroFichajes`, `KioskoController`, `FichajeController` |
| Personal: cada fichaje es de una persona identificada (PIN, DNI o su cuenta) | Kiosco y cuenta propia |
| **Inalterable**: no se puede editar ni borrar desde la app | Eventos `updating`/`deleting` de `Fichaje` y `FichajeCorreccion` |
| **Demostrable**: sello HMAC encadenado; cualquier cambio o borrado hecho en la base de datos se detecta | `IntegridadRegistro`, Admin → Fichajes → *Comprobar integridad*, `php artisan registro:verificar` |
| Trazabilidad de los cambios: la corrección guarda hora nueva, motivo, autor y fecha, y el original se conserva | `FichajeCorreccion`; salidas olvidadas como fichaje nuevo con autor y motivo (`SalidaOlvidadaController`) |
| Horas que no cambian solas: las fechas legales son `DATETIME` | Migración `2026_11_03_090000_fechas_legales_sin_actualizacion_automatica` |
| Conservación 4 años | Los fichajes nunca se borran; empleados y empresas se desactivan, no se eliminan |
| A disposición del trabajador | *Mis fichajes* y su informe mensual en PDF, aunque la empresa no pague |
| A disposición de representantes e Inspección | Exportación completa (CSV/Excel) con hora registrada y válida, correcciones, autor, origen y sello; PDF; informes mensuales |
| Totalizado mensual (tiempo parcial, horas extra) | `InformeMensual`, Admin → *Informes*: total del mes, horas previstas y horas por encima del horario, con firmas |
| Turnos de noche, olvidos y doble toque | `RegistroFichajes`, `JornadasAgrupador`, `IncidenciasCalculador` |

Las «horas por encima del horario» se calculan con el horario configurado: si son horas
extraordinarias, complementarias o se compensan lo decide la empresa según contrato y convenio.

### 2.2 Protección de datos

| Requisito | Cómo |
|---|---|
| Papeles | La empresa cliente es **responsable**; ALSE SOFTWARE es **encargado** de los datos de la plantilla (contrato de encargo aceptado al darse de alta, versión guardada en `legal_aceptaciones`). Para la web y los clientes, ALSE es responsable. |
| Minimización | Sin geolocalización ni biometría. Las bajas se guardan como tipo de ausencia, sin diagnóstico; los formularios lo recuerdan y los informes dicen «ausencia justificada». |
| Seguridad (art. 32) | Ver [`seguridad.md`](seguridad.md). |
| Derechos de acceso y portabilidad | Cada persona descarga sus datos desde su perfil (JSON); el admin, los de un empleado desde su ficha. |
| Rectificación | El admin edita los datos del empleado; los fichajes se corrigen con trazabilidad. |
| Supresión | Los datos del registro no se pueden suprimir durante 4 años (obligación legal, art. 17.3.b RGPD). Al terminar el contrato con un cliente, ver el procedimiento en el encargo (30 días para descargar y supresión en 3 meses). |
| Conservación | Registro: 4 años. Mensajes de contacto: 12 meses, se borran solos (`contactos:purgar`, mensual). |
| Auditoría | `audit_logs`: correcciones, salidas añadidas, exportaciones, informes, descargas de datos y de nóminas de terceros, comprobaciones de integridad, cambios en la verificación en dos pasos. |
| Subencargados | Loading.es (alojamiento y correo), Stripe (cobro, sin datos de la plantilla) y Google (solo si el admin entra con Google). Stripe y Google, con el Marco de Privacidad de Datos UE-EE. UU. |
| Brechas | Procedimiento en [`seguridad.md`](seguridad.md#5-si-hay-una-brecha-de-seguridad). |
| Cookies | Solo técnicas: no hace falta banner. |

## 3. Lo que tiene que hacer cada empresa cliente

La app no puede hacerlo por ella. Conviene recordárselo al darla de alta (y está en los términos):

1. **Informar a la plantilla** del sistema de registro y del tratamiento de sus datos (art. 13
   RGPD). Puede usar la [política de privacidad](../resources/views/legal/privacidad.blade.php) como base.
2. **Negociar o consultar con la representación legal** de los trabajadores la organización del
   registro, si la hay (art. 34.9 ET y convenio).
3. Tener una **política de desconexión digital** (art. 88 LOPDGDD).
4. **Configurar bien horarios y revisar las incidencias**, corrigiendo con motivo cuando haga falta.
5. Si hay **tiempo parcial**, entregar cada mes el informe mensual junto con la nómina.
6. **Conservar el registro 4 años**: si se da de baja, descargarlo antes.
7. Incluir el tratamiento en su **registro de actividades** (art. 30 RGPD).
8. Activar la **verificación en dos pasos** en las cuentas de administración.

## 4. Lo que tiene que hacer el titular (ALSE SOFTWARE)

- Que un profesional revise los textos legales; completar tomo, folio y hoja del Registro Mercantil.
- Mantener las **copias de seguridad** del servidor (Plesk) y comprobar que se pueden restaurar.
- Llevar su propio **registro de actividades de tratamiento** (clientes, contacto) y como encargado.
- Aplicar el procedimiento de **brechas** y de **fin de contrato** cuando ocurra.
- Avisar a los clientes antes de cambiar de subencargado y cuando cambien los textos legales
  (subir la versión en `config/legal.php`).

## 5. Pendiente

- **Acceso de la Inspección**: hoy se le entrega la exportación completa. Si el real decreto exige
  acceso remoto, añadir un acceso de solo lectura y con caducidad que la empresa pueda dar.
- **Fin de los 4 años**: los fichajes no se borran solos. Pasado el plazo (más el de prescripción
  de posibles reclamaciones) habría que bloquearlos o borrarlos (art. 5.1.e RGPD y art. 32
  LOPDGDD). No corre prisa: los primeros datos cumplen 4 años en 2030. Hay que hacerlo sin romper
  el sello encadenado (empezar la cadena en el primer registro que se conserve).
