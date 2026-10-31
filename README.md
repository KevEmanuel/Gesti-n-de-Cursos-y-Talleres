# Gestión de Cursos y Talleres

Aplicación web para administrar cursos, estudiantes, instructores e
inscripciones, desarrollada en PHP, mySql, mediante el MVC

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

`database/schema.sql` es para instalaciones nuevas; no lo importes sobre una
base con datos.