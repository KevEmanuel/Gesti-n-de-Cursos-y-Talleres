# Gestión de Cursos y Talleres

Sistema de gestión de cursos y talleres, aplicación web desarrollada mediante el uso de patron MVC.

## Estructura y flujo de la aplicación

- `public/index.php` carga dependencias, modelos y controladores, y luego entrega
  la solicitud al enrutador.
- `public/router.php` permite que los recursos estáticos se sirvan directamente
  y envía las demás solicitudes a la aplicación.
- `routes/web.php` asocia cada combinación de método HTTP y ruta a una acción.
- `app/controllers/` valida solicitudes, aplica autorización y coordina modelos
  con vistas.
- `app/models/` contiene consultas PDO preparadas y reglas de persistencia.
- `app/views/` contiene las pantallas PHP; `layouts/main.php` provee el marco
  común y `public/css/` y `public/js/` los recursos visuales e interactivos.
- `database/schema.sql` define la estructura completa para una instalación
  nueva; `database/migrations/` conserva cambios para instalaciones existentes.

En una solicitud normal, el enrutador invoca el controlador, el controlador
verifica permisos y CSRF cuando corresponde, el modelo consulta o actualiza la
base de datos y la vista presenta el resultado. Las contraseñas se guardan con
`password_hash`; los valores enviados por formularios se escapan con `e()` y
las consultas que reciben datos usan parámetros PDO.

## Funciones principales

| Componente | Función |
| --- | --- |
| `BaseController::requireAdministrator()`, `requireStudent()` y `requireInstructor()` | Restringen las acciones a sesiones del rol esperado. |
| `BaseController::verifyCsrfToken()` | Rechaza formularios cuyo token no coincide con la sesión. |
| `BaseController::render()` y `redirect()` | Renderizan una vista dentro del diseño común y redirigen después de una acción. |
| `AuthController::authenticate()` | Valida credenciales, determina el rol asociado y dirige al panel correspondiente. |
| `AuthController::requestPasswordReset()` y `resetPassword()` | Solicitan el restablecimiento por correo y consumen tokens de un solo uso. |
| `Usuario::byCredentials()` | Busca la cuenta y comprueba que tenga exactamente un rol asociado. |
| `Usuario::setManagedPassword()` | Permite al administrador asignar una contraseña nueva a un estudiante o instructor. |
| `Usuario::createPasswordResetToken()` y `resetPassword()` | Crean tokens limitados y actualizan la contraseña de forma transaccional. |
| `Curso::publicCatalog()`, `publicCourse()` y `publicSchedule()` | Preparan el catálogo público, el detalle del curso y sus horarios. |
| `Solicitud::changeStatus()` | Cambia el estado de una inscripción y aplica sus validaciones de cupo y horario. |
| `Reporte::courseOccupancy()` | Calcula ocupación y cupos disponibles según los filtros del reporte. |
| `e()` en `app/helpers.php` | Escapa valores al incluirlos en HTML. |

## Configuración de la base de datos

La conexión lee sus credenciales de variables de entorno; no guardes contraseñas
ni datos de acceso en archivos PHP que vayan al repositorio. Configura estas
variables en el entorno local o en el servidor:

- `DB_HOST` (por defecto: `127.0.0.1`)
- `DB_NAME` (por defecto: `gestiondedatos`)
- `DB_USER` (por defecto: `root`)
- `DB_PASSWORD` (por defecto: vacío)

En PowerShell, puedes definirlas para la sesión actual antes de iniciar PHP:

```powershell
$env:DB_HOST = "127.0.0.1"
$env:DB_NAME = "gestiondedatos"
$env:DB_USER = "usuario_local"
$env:DB_PASSWORD = "tu_contraseña_local"
```

No subas un archivo `.env` ni publiques contraseñas reales. `.gitignore` excluye
los archivos `.env`; la aplicación usa variables de entorno del proceso y no
carga automáticamente archivos `.env`.

## Instalación local

Desde la carpeta del proyecto, instala las dependencias y configura las
variables de base de datos descritas arriba. Después, inicia el servidor PHP:

```powershell
composer install
php -S 127.0.0.1:8000 -t public public/router.php
```

Abre `http://127.0.0.1:8000`. Para una base vacía, importa `database/schema.sql`
una sola vez; este archivo ya representa el esquema actual.

## Migraciones de base de datos

Las migraciones son el historial de cambios aplicado a bases existentes y no
deben eliminarse solo porque el esquema nuevo ya incluya su resultado. En el
orden de evolución del proyecto:

| Archivo | Cambio y dependencia |
| --- | --- |
| `20260929_create_student_account_applications.sql` | Crea la tabla inicial para solicitudes de cuentas estudiantiles. |
| `20260929_separate_account_requests_and_generate_carnets.sql` | Evoluciona esa tabla (ejecutar después de la anterior) y agrega carnets a instructores. |
| `20260929_add_enrollment_rejection_reason.sql` | Añade el motivo de rechazo y el estado de inscripción correspondiente; requiere la tabla `inscripciones`. |
| `20261024_create_password_reset_tokens.sql` | Añade el almacenamiento de tokens de recuperación y requiere `usuarios`. |

En una base existente, verifica primero qué cambios ya recibió y aplica cada
migración pendiente una sola vez, respetando sus dependencias. El proyecto aún
no mantiene una tabla automática de versiones de esquema; por ello, no ejecutes
el directorio completo a ciegas. Si el estado de la base no está claro, realiza
un respaldo y compara sus tablas con `database/schema.sql` antes de modificarla.
Para una instalación nueva, importa solo `schema.sql`; no vuelvas a aplicar
migraciones históricas encima del esquema final.

## Recuperación de contraseña

La recuperación está disponible para cuentas de administrador, instructor y
estudiante. Instala las dependencias PHP desde la carpeta raíz del proyecto:

```powershell
composer install
```

Aplica una sola vez la migración a la base de datos existente. En PowerShell,
desde la carpeta `cursoTaller`:

```powershell
Get-Content .\database\migrations\20261024_create_password_reset_tokens.sql | mysql -h 127.0.0.1 -u usuario_local -p gestiondedatos
```

Al crear una base de datos nueva, la tabla también está incluida en
`database/schema.sql`.

Configura en el entorno del servidor la URL pública y los datos de un servidor
SMTP con STARTTLS o TLS implícito. Por ejemplo, en PowerShell:

```powershell
$env:APP_URL = "https://tu-dominio.example"
$env:SMTP_HOST = "smtp.tu-proveedor.example"
$env:SMTP_PORT = "587"
$env:SMTP_USERNAME = "cuenta-smtp"
$env:SMTP_PASSWORD = "secreto-configurado-en-el-servidor"
$env:SMTP_ENCRYPTION = "tls"
$env:MAIL_FROM_ADDRESS = "no-reply@tu-dominio.ues"
$env:MAIL_FROM_NAME = "Formación Continua"
```

Usa `SMTP_ENCRYPTION=ssl` con el puerto que indique el proveedor para TLS
