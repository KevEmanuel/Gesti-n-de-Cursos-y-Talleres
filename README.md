# Gestión de Cursos y Talleres

Aplicación web para administrar cursos, estudiantes, instructores e
inscripciones. Está desarrollada en PHP y usa MySQL o MariaDB.

## Requisitos

- PHP con las extensiones PDO y PDO MySQL.
- MySQL o MariaDB.
- Composer.

## Instalación

1. Abre la carpeta `cursoTaller` en la terminal.
2. Crea la base de datos importando `database/schema.sql`. En phpMyAdmin,
   selecciona **Importar**, elige ese archivo y pulsa **Importar**. Este esquema
   es para una base nueva y crea `gestiondedatos`.
3. Si tu usuario de MySQL no es `root` o tiene contraseña, configura los datos
   de conexión en PowerShell:

   ```powershell
   $env:DB_HOST = "127.0.0.1"
   $env:DB_NAME = "gestiondedatos"
   $env:DB_USER = "root"
   $env:DB_PASSWORD = "colocar password"
   ```

   Ademas usa `gestiondedatos` como nombre de base de datos para evitar cualquier error.

4. Instala la dependencia de correo:

   ```powershell
   composer install
   ```

5. Iniciar el servidor local:

   ```powershell
   php -S 127.0.0.1:8000 -t public public/router.php
   ```

6. Abre <http://127.0.0.1:8000>. En la primera visita, sigue las instrucciones
   para crear la cuenta administradora.

## Si ya tienes una base de datos

No importes `schema.sql` sobre una base con datos: es para instalaciones
nuevas, no para actualizar bases existentes. Antes de ejecutar cambios, haz una
copia de seguridad y confirma qué actualizaciones ya aplicaste.

Las migraciones se ejecutan una sola vez y en este orden:

1. `20260929_create_student_account_applications.sql`
2. `20260929_separate_account_requests_and_generate_carnets.sql`
3. `20260929_add_enrollment_rejection_reason.sql` (requiere la tabla
   `inscripciones`).
4. `20261024_create_password_reset_tokens.sql` (requiere la tabla `usuarios`).

No ejecutes una migración si sus cambios ya existen en tu base. Para una base
nueva, usa solamente `database/schema.sql`.
