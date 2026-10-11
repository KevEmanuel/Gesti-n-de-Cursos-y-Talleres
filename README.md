# Gestión de Cursos y Talleres

Sistema de gestión de cursos y talleres, desarrollado como aplicación web
mediante el patrón MVC.

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
