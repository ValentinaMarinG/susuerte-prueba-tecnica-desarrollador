# Susuerte
# Prueba Técnica - Desarrollador de Software 

Sistema de registro de apuestas.

---

## Tabla de contenido

1. [Requisitos](#requisitos)
2. [Instalación y configuración](#instalación-y-configuración)
3. [Ejecución](#ejecución)
4. [Endpoints de la API](#endpoints-de-la-api)
5. [Pruebas manuales](#pruebas-manuales-con-curl)
6. [Parte 1 – PHP y Recursividad](#parte-1--php-y-recursividad)
7. [Parte 2 – Consultas SQL](#parte-2--consultas-sql)
8. [Parte 6 – Mejora: Idempotencia](#parte-6--mejora-idempotencia)
9. [Decisiones técnicas y supuestos](#decisiones-técnicas-y-supuestos)
10. [Si tuviera más tiempo…](#si-tuviera-más-tiempo)

---

## Requisitos

- PHP 8.1 o superior 
- MySQL 8.0.26 o superior 
- Git

---

## Instalación y configuración

```bash
# 1. Clonar el repositorio
git clone https://github.com/ValentinaMarinG/susuerte-prueba-tecnica-desarrollador.git
cd prueba_tecnica_susuerte

# 2. Crear la base de datos y ejecutar el esquema
mysql -u root -p < database/schema.sql
```

### Variables de entorno (opcional)

Por defecto la aplicación conecta a `127.0.0.1` con la base de datos `susuerte` y el usuario `root` sin contraseña.

Si tu instancia de MySQL tiene contraseña, edita `config/database.php` y reemplaza el valor de `DB_PASS`:

```php
define('DB_PASS', getenv('DB_PASS') ?: 'tu_contraseña');
```
O expórtala como variable de entorno antes de levantar el servidor en un archivo .env en la raíz del proyecto:

```bash
export DB_HOST=127.0.0.1
export DB_PORT=3306
export DB_NAME=susuerte
export DB_USER=tu_usuario
export DB_PASS=tu_contraseña
```

---

## Ejecución

```bash
# 1. Levantar el servidor de desarrollo de PHP
php -S localhost:8000 -t public public/index.php
```

Abre `http://localhost:8000` en el navegador para ver el formulario frontend.

---

## Endpoints de la API

### `POST /api/tiquetes`

Crea un tiquete y descuenta el monto del saldo del usuario dentro de una transacción.

**Body JSON:**
```json
{ "usuario_id": 1, "monto": 5000.00 }
```

**Respuesta exitosa (201):**
```json
{
  "mensaje": "Tiquete creado correctamente.",
  "tiquete": {
    "id": 7,
    "usuario_id": 1,
    "monto": 5000.00,
    "estado": "pendiente"
  }
}
```

---

### `GET /api/usuarios/{id}/tiquetes`

Devuelve el usuario y la lista de sus tiquetes ordenados por fecha descendente.

**Respuesta exitosa (200):**
```json
{
  "usuario": { "id": 1, "nombre": "Carlos Pérez", "saldo": 495000.00 },
  "tiquetes": [
    { "id": 7, "monto": 5000.00, "estado": "pendiente", "creado_en": "2025-06-01 10:30:00" }
  ],
  "total": 1
}
```

Para ambos endpointst devuelve `404` si el usuario no existe.

---

## Pruebas manuales con curl

### LINUX
```bash
# Crear tiquete exitoso
curl -s -X POST http://localhost:8000/api/tiquetes \
  -H "Content-Type: application/json" \
  -d '{"usuario_id": 1, "monto": 5000}' | python3 -m json.tool

# Saldo insuficiente (usuario 6 tiene saldo 0)
curl -s -X POST http://localhost:8000/api/tiquetes \
  -H "Content-Type: application/json" \
  -d '{"usuario_id": 5, "monto": 1000}' | python3 -m json.tool

# Usuario inexistente
curl -s -X POST http://localhost:8000/api/tiquetes \
  -H "Content-Type: application/json" \
  -d '{"usuario_id": 999, "monto": 1000}' | python3 -m json.tool

# JSON inválido
curl -s -X POST http://localhost:8000/api/tiquetes \
  -H "Content-Type: application/json" \
  -d 'no-es-json' | python3 -m json.tool

# Listar tiquetes del usuario 1
curl -s http://localhost:8000/api/usuarios/1/tiquetes | python3 -m json.tool
```

### WINDOWS
```bash
# Crear tiquete exitoso
curl.exe -i -X POST "http://localhost:8000/api/tiquetes" `
-H "Content-Type: application/json" `
-d "{\"usuario_id\":1,\"monto\":5000}"

# Saldo insuficiente (usuario 6 tiene saldo 0)
curl.exe -i -X POST "http://localhost:8000/api/tiquetes" `
-H "Content-Type: application/json" `
-d "{\"usuario_id\":5,\"monto\":1000}"

# Usuario inexistente
curl.exe -i -X POST "http://localhost:8000/api/tiquetes" `
-H "Content-Type: application/json" `
-d "{\"usuario_id\":999,\"monto\":1000}"

# JSON inválido
curl.exe -i -X POST "http://localhost:8000/api/tiquetes" `
-H "Content-Type: application/json" `
-d "no-es-json"

# Listar tiquetes del usuario 1
curl.exe -i "http://localhost:8000/api/usuarios/1/tiquetes"
```

---

## Parte 1 – PHP y Recursividad

La función `calcularPremioAcumulado(array $niveles): float` se encuentra en `src/premio_acumulado.php`.

**Caso base:** el nodo tiene el array hijos vacío (`'hijos' => []`). `array_reduce([])` recibe en la llamada recursiva un array vacío y retorna el valor inicial (`0.0`) directamente, sin más llamadas recursivas.

**Riesgo con estructuras muy profundas:** PHP mantiene un límite de profundidad de pila de llamadas configurado en el archivo php.ini. Una jerarquía con cientos de niveles anidados lanzaría fallaría con un error de desbordamiento de pila (Stack Overflow).  En ese caso, la solución sería reescribir la función usando un bucle con una pila explícita (SplStack) en lugar de llamadas recursivas.

**Verificación rápida:**

```php
require 'src/premio_acumulado.php';
$niveles = [
    ['monto' => 1000, 'hijos' => [
        ['monto' => 500, 'hijos' => []],
        ['monto' => 250, 'hijos' => [
            ['monto' => 100, 'hijos' => []]
        ]]
    ]]
];
echo calcularPremioAcumulado($niveles); // 1850
```

---

## Parte 2 – Consultas SQL

Las consultas están en `database/schema.sql`. 

**2.2** Top 3 usuarios por monto total en tiquetes ganadores:
```sql
SELECT u.nombre, SUM(t.monto) AS total
FROM usuarios u
INNER JOIN tiquete t ON t.usuario_id = u.id_usuario
WHERE t.estado = 'ganador'
GROUP BY u.id_usuario, u.nombre
ORDER BY total DESC
LIMIT 3;
```

**2.3** Usuarios sin tiquetes:
```sql
SELECT DISTINCT u.id_usuario, u.nombre 
FROM usuarios u
LEFT JOIN tiquete t ON t.usuario_id = u.id_usuario
WHERE t.usuario_id IS NULL;
```

**2.4** ¿Por qué una transacción al descontar del saldo del usuario?  

Restar el saldo y crear el tiquete son dos operaciones separadas que se ejecutan sobre diferentes tablas a la vez. 
Si una de ellas se completa exitosamente y la otra falla por cualquier motivo, la información quedaría en un estado inconsistente. Por ejemplo, el usuario podría ver descontado su saldo sin que el tiquete haya sido registrado, o podría existir un tiquete registrado sin que se haya realizado el descuento correspondiente. Por esta razón es necesario utilizar una transacción, garantizando que ambas operaciones se ejecuten o ninguna de ellas se aplica. De esta manera se preserva la integridad y consistencia de los datos, evitando afectar la operación y la lógica del negocio.


---

## Parte 6 – Mejora: Idempotencia

**Propuesta:** agregar una clave de idempotencia al endpoint `POST /api/tiquetes`.

**Problema que resuelve:** si el cliente envía la misma request dos veces por un reintento de red o un doble clic, sin idempotencia el sistema crearía dos tiquetes y descontaría el saldo dos veces. Por ejemplo, en un sistema de apuestas, donde cada operación implica movimientos de dinero y afecta el saldo de los usuarios, la idempotencia es un mecanismo clave para preservar la consistencia de los datos, evitar duplicidades y proteger tanto al negocio como a los jugadores.

**Implementación propuesta:**

1. El cliente genera un UUID v4 en un dato del cliente por cada intento de apuesta y lo envía en el header.
2. El servidor guarda en base de datos el par `(idempotency_key, tiquete_id, respuesta_json)` en una tabla `idempotency_keys` con un índice único sobre la clave.
3. Si llega una request con una clave ya procesada, el servidor retorna la respuesta original almacenada sin ejecutar la transacción nuevamente.
4. Las claves expiran tras una cierta cantidad de minutos/horas mediante un proceso de limpieza periódico.

**Valor para el negocio:** elimina cobros duplicados accidentales y mantiene la confianza del cliente en la plataforma transaccional.

---

## Decisiones técnicas y supuestos

| Decisión | Justificación |
|----------|---------------|
| `SELECT ... FOR UPDATE` en la transacción | Evita race conditions si el mismo usuario hace dos requests concurrentes |
| `DECIMAL(12,2)` para montos | Evita errores de punto flotante en operaciones financieras |
| `ENUM` para estado del tiquete | Garantiza a nivel de BD que solo existan los valores válidos (ganador, perdedor, pendiente) |
| Router en `index.php` | Centraliza el enrutamiento |

**Supuestos:**
- El monto mínimo de apuesta es > 0 (no se permiten apuestas de $0).

---

## Si tuviera más tiempo…

- **Autenticación:** agregar autenticación con Tokens JWT para que solo el usuario autenticado pueda crear y listar sus apuestas.
- **Idempotencia real:** implementar la propuesta de la Parte 6 con código completo.
- **Listado de tiquetes para un usuario desde el formulario:** implementar la consulta y visualización de todos los tiquetes asociados a un usuario específico directamente desde la interfaz.
- **Logging estructurado:** reemplazar `error_log()` por un logger que escriba JSON estructurado, facilitando el monitoreo en producción.
