# Despliegue seguro

## 1. Variables de entorno

Configure en Apache las variables documentadas en `.env.example`. El archivo `.env`
es únicamente una referencia local: la aplicación no lo carga ni debe publicarse.

En producción son obligatorias:

- `APP_ENV=prod`
- `APP_DEBUG=false`
- `APP_COOKIE_VALIDATION_KEY` aleatoria, con mínimo 32 caracteres
- un usuario MySQL exclusivo, sin privilegios administrativos
- `MAILER_DSN` del servidor SMTP institucional
- correos institucionales para `ADMIN_EMAIL` y `SENDER_EMAIL`

Ejecute después:

```powershell
php yii production/check
php yii migrate/up --interactive=0
php yii production/backup-check
```

Antes de habilitar el sitio, rote las tres cuentas iniciales. Defina temporalmente
`NEW_USER_PASSWORD` en la sesión protegida de la terminal y ejecute:

```powershell
php yii user/rotate admin
php yii user/rotate revisor
php yii user/rotate demo
```

Use una contraseña distinta para cada ejecución y elimine inmediatamente la variable.
Para nuevas cuentas:

```powershell
php yii user/create nombre_usuario revisor
```

## 2. Apache

El `DocumentRoot` debe apuntar exclusivamente a `web/`, nunca a la raíz del proyecto.
Habilite HTTPS, redirección permanente desde HTTP y HSTS únicamente después de
confirmar que el certificado funciona. Bloquee acceso a `index-test.php`.

Directorios que requieren escritura por la cuenta de Apache:

- `runtime/`
- `web/assets/`

El resto del código debe ser de solo lectura.

## 3. Respaldos

Mantenga respaldos cifrados de MySQL y `runtime/solicitudes/` en una ubicación externa.
La política mínima recomendada es:

- respaldo diario;
- 30 copias diarias;
- 12 copias mensuales;
- prueba de restauración trimestral.

No incluya contraseñas en argumentos de línea de comandos. Use variables protegidas
del servicio de tareas y restrinja el directorio de respaldos.

## 4. Verificación posterior

- comprobar inicio de sesión y permisos;
- registrar una solicitud de prueba;
- verificar el correo institucional;
- descargar un documento desde el panel;
- revisar `runtime/logs/app.log`;
- confirmar que debug y Gii no están disponibles.
