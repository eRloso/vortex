# Vortex · Sistema de ventas

Sistema de ventas web hecho en **PHP 8 + MariaDB**, con **Programación Orientada a Objetos**, arquitectura **MVC** propia (sin frameworks), **API REST interna** y consumo de una **API externa** de tasa de cambio.

---

## 1. Instalación (XAMPP / Laragon)

1. Copia la carpeta `vortex` dentro de `htdocs` (XAMPP) o `www` (Laragon).
2. Inicia **Apache** y **MySQL/MariaDB**.
3. Abre phpMyAdmin → pestaña **Importar** → selecciona `database/vortex.sql` → **Continuar**.
   (o por consola: `mysql -u root -p < database/vortex.sql`)
4. Si tu usuario/clave de MariaDB no es `root` sin contraseña, edítalo en `config/config.php`.
5. Entra a **http://localhost/vortex/**

**Usuario de prueba:** `admin@vortex.com` · **Contraseña:** `admin123`

Requisitos: PHP 8.0 o superior con las extensiones `pdo_mysql` y `curl` (vienen activas en XAMPP).

---

## 2. Funcionalidades

| Módulo | Qué hace |
|---|---|
| **Login** | Autenticación con `password_hash` / `password_verify`, sesión segura y token CSRF. |
| **Dashboard** | Ventas de hoy, ingresos del mes, ticket promedio, gráfico de 7 días (Chart.js), más vendidos, alertas de stock y tasa del dólar. |
| **Punto de venta** | Búsqueda en vivo (vía API), carrito con cantidades, IVA, equivalente en USD, método de pago y cobro sin recargar la página. Atajos: `/` busca, `Enter` agrega el primer resultado, `F2` abre una venta nueva desde cualquier pantalla. |
| **Historial de ventas** | Filtros por texto, rango de fechas y método de pago; total del periodo. |
| **Comprobante** | Factura imprimible (`Ctrl + P`) con la tasa del dólar usada al momento de la venta. |
| **Productos** | CRUD con categoría, precio, stock y stock mínimo. El borrado es lógico para no dañar el historial. |
| **Categorías** | CRUD con color identificador que se usa en toda la interfaz. |
| **Clientes** | CRUD con total de compras. No deja eliminar clientes con ventas ni el "Consumidor final". |
| **Tema** | Modo oscuro / claro, recordado en el navegador. Diseño responsive. |

---

## 3. Uso de APIs

### 3.1 API externa (consumo)
`App\Services\TasaCambioService` consulta **https://open.er-api.com/v6/latest/COP** (gratuita, sin API key) para convertir los totales de COP a USD.

- Usa `App\Services\HttpClient` (cURL con *timeout*).
- Guarda la respuesta en caché (`storage/cache/tasa_usd.json`) durante 6 horas.
- Si no hay internet, usa la última tasa guardada o una tasa de respaldo (`config.php`). La interfaz indica la fuente: *API en vivo*, *Caché local* o *Tasa de respaldo*.
- La tasa se guarda en cada venta (`ventas.tasa_usd`).

### 3.2 API REST propia (exposición)
Todas responden JSON con el formato `{ "ok": true, "datos": ... }` o `{ "ok": false, "mensaje": "..." }` y requieren sesión iniciada.

| Método | Ruta | Descripción |
|---|---|---|
| GET  | `index.php?r=api/productos&q=texto` | Productos activos (búsqueda opcional). |
| GET  | `index.php?r=api/clientes&q=texto` | Clientes. |
| GET  | `index.php?r=api/tasa` | Tasa COP→USD (desde la API externa). |
| GET  | `index.php?r=api/estadisticas` | Resumen, ventas por día y top de productos. |
| POST | `index.php?r=api/ventas` | Registra una venta. Encabezado `X-CSRF-Token`. |

Ejemplo de cuerpo para registrar una venta:
```json
{ "cliente_id": 2, "metodo_pago": "nequi", "items": [ { "id": 3, "cantidad": 2 }, { "id": 10, "cantidad": 1 } ] }
```
El navegador solo envía `id` y `cantidad`: **los precios y totales los calcula el servidor** desde la base de datos, dentro de una transacción que bloquea el stock (`SELECT ... FOR UPDATE`).

---

## 4. Arquitectura y POO

```
vortex/
├── public/                 ← Única carpeta pública
│   ├── index.php           ← Front controller (todas las peticiones entran aquí)
│   └── assets/ (css, js, img)
├── app/
│   ├── Core/               ← Núcleo reutilizable
│   │   ├── Config.php          Configuración con notación de puntos
│   │   ├── Database.php        Conexión PDO única (Singleton)
│   │   ├── Model.php           Modelo base abstracto: CRUD genérico
│   │   ├── Controller.php      Controlador base: render, json, redirect, CSRF
│   │   ├── CrudController.php  CRUD completo reutilizable (Template Method)
│   │   ├── Router.php          Enrutador por ?r=
│   │   ├── View.php            Plantillas + layout + parciales
│   │   ├── Session.php         Sesión, mensajes flash y CSRF
│   │   └── Validador.php       Validación encadenable
│   ├── Models/             ← Producto, Categoria, Cliente, Venta, DetalleVenta, Usuario
│   ├── Controllers/        ← Auth, Dashboard, Producto, Categoria, Cliente, Venta, Api
│   ├── Services/           ← HttpClient, TasaCambioService (API externa)
│   └── Views/              ← layouts, partials reutilizables y vistas por módulo
├── config/ (config.php, rutas.php)
├── database/vortex.sql
└── storage/cache/
```

**Conceptos de POO aplicados**

- **Herencia:** todos los modelos extienden `Model`; los controladores extienden `Controller`; Productos, Categorías y Clientes extienden `CrudController`.
- **Abstracción:** `Model` y `CrudController` son clases abstractas con métodos abstractos (`crearModelo()`, `validar()`).
- **Polimorfismo:** `Producto::eliminar()` hace borrado lógico y `Cliente::eliminar()` valida reglas de negocio; ambos sobrescriben `Model::eliminar()`. `listar()` cambia en cada modelo.
- **Encapsulamiento:** propiedades `private`/`protected`, lista blanca `$campos` para evitar asignación masiva, constantes de clase (`Venta::METODOS_PAGO`, `Cliente::CONSUMIDOR_FINAL`).
- **Composición e inyección de dependencias:** `TasaCambioService` recibe un `HttpClient`.
- **Patrones:** Singleton (`Database`), Front Controller, MVC, Template Method (`CrudController`), Service Layer.
- **Reutilización:** un solo `CrudController` sirve tres módulos; parciales de vista (`campo`, `buscador`, `acciones`, `vacio`, `flash`, `form-acciones`); variables CSS para toda la paleta; en JS las clases `Vortex.Api`, `Carrito` y `PuntoVenta`.

**Seguridad:** consultas preparadas (PDO), `htmlspecialchars` en toda salida (`e()`), token CSRF en formularios y en `fetch`, contraseñas con bcrypt, regeneración de ID de sesión, carpetas privadas bloqueadas con `.htaccess`.

---

## 5. Paleta de colores

| Token | Color | Uso |
|---|---|---|
| `--vx-primario` | `#7C5CFF` | Violeta vórtice: botones, enlaces, selección |
| `--vx-acento` | `#22D3EE` | Cian eléctrico: datos en USD, detalles |
| `--vx-degradado` | `#7C5CFF → #4F7BFF → #22D3EE` | Logo, totales, botón de venta rápida |
| `--vx-exito` | `#22C55E` | Confirmaciones, pago en efectivo |
| `--vx-alerta` | `#F59E0B` | Stock bajo |
| `--vx-peligro` | `#F43F5E` | Errores y eliminar |
| `--vx-fondo` / `--vx-superficie` | `#0A0E1A` / `#111729` | Fondo y tarjetas (modo oscuro) |

Tipografías: **Plus Jakarta Sans** (interfaz) y **JetBrains Mono** (cifras y códigos). Íconos: **Lucide**.
Todos los colores están en variables CSS al inicio de `public/assets/css/vortex.css`, así que cambiar la marca es cuestión de editar unas pocas líneas.

---

## 6. Pruebas unitarias

```bash
php tests/run_tests.php          # las 23 pruebas
php tests/run_tests.php --base   # solo las 18 pruebas base
```

- Cada prueba crea una base **SQLite en memoria** (`sqlite::memory:`) y la inyecta en los modelos por el constructor (`new Venta($db)`). La base MariaDB de producción nunca se toca.
- Todas siguen el patrón **AAA** (Arrange, Act, Assert) con `assertTrue`, `assertEquals` y `assertThrows`.
- La API externa se reemplaza por un doble de prueba (`HttpClientFalso`), así que no hace falta internet.
- Requiere la extensión `pdo_sqlite` (activa por defecto en XAMPP). Si aparece "could not find driver", quita el `;` de `extension=pdo_sqlite` en `php.ini`.
- El comando devuelve código de salida `1` si alguna prueba falla, listo para usar en CI/CD.
