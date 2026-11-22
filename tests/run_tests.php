<?php
/**
 * ==========================================================================
 *  VORTEX · Suite de pruebas unitarias
 * ==========================================================================
 *  Ejecución (desde la carpeta raíz del proyecto):
 *      php tests/run_tests.php           -> las 23 pruebas
 *      php tests/run_tests.php --base    -> solo las 18 pruebas base (Parte A)
 *
 *  Aislamiento: cada prueba recibe una base de datos SQLite NUEVA en memoria
 *  (sqlite::memory:) que se inyecta a los modelos por el constructor
 *  (?PDO $db = null). Nunca se toca la base MariaDB de producción.
 *
 *  Todas las pruebas siguen el patrón AAA:
 *      Arrange (preparar) -> Act (ejecutar) -> Assert (verificar)
 * ==========================================================================
 */

define('BASE_PATH', dirname(__DIR__));
require BASE_PATH . '/app/autoload.php';
require BASE_PATH . '/app/helpers.php';

use App\Core\Config;
use App\Models\Cliente;
use App\Models\Producto;
use App\Models\Usuario;
use App\Models\Venta;
use App\Services\HttpClient;
use App\Services\TasaCambioService;

Config::cargar(BASE_PATH . '/config/config.php');

if (!extension_loaded('pdo_sqlite')) {
    fwrite(STDERR, "Falta la extensión pdo_sqlite. Actívala en php.ini (extension=pdo_sqlite) y vuelve a ejecutar.\n");
    exit(1);
}

/* ==========================================================================
 *  1. MINI FRAMEWORK DE PRUEBAS
 * ========================================================================== */

/** Error lanzado cuando una aserción no se cumple. */
final class FalloAsercion extends Exception
{
}

final class SuitePruebas
{
    private array $pruebas = [];
    private bool $colores;

    public function __construct()
    {
        // Colores ANSI solo si la consola los soporta (en Windows se activan explícitamente)
        if (function_exists('sapi_windows_vt100_support')) {
            @sapi_windows_vt100_support(STDOUT, true);
        }
        $this->colores = function_exists('stream_isatty') && stream_isatty(STDOUT);
    }

    private function estado(bool $aprobada): string
    {
        $texto = $aprobada ? '[PASS]' : '[FAIL]';
        return $this->colores ? ($aprobada ? "\033[32m{$texto}\033[0m" : "\033[31m{$texto}\033[0m") : $texto;
    }

    public function agregar(string $id, string $clase, string $descripcion, callable $prueba, bool $esBase = true): void
    {
        $this->pruebas[] = compact('id', 'clase', 'descripcion', 'prueba', 'esBase');
    }

    public function ejecutar(bool $soloBase = false): int
    {
        $pruebas = $soloBase ? array_filter($this->pruebas, fn ($p) => $p['esBase']) : $this->pruebas;
        $aprobadas = 0;
        $fallos = [];
        $inicio = microtime(true);

        self::linea('=', 78);
        echo " VORTEX · Suite de pruebas unitarias  (PHP " . PHP_VERSION . " · SQLite en memoria)\n";
        self::linea('=', 78);

        $grupoActual = null;
        foreach ($pruebas as $p) {
            $grupo = $p['esBase'] ? 'PRUEBAS BASE' : 'PARTE B · NUEVOS CASOS BORDE';
            if ($grupo !== $grupoActual) {
                echo "\n " . $grupo . "\n";
                self::linea('-', 78);
                $grupoActual = $grupo;
            }

            try {
                ($p['prueba'])();
                $aprobadas++;
                printf(" %s %s | %-24s | %s\n", $this->estado(true), $p['id'], $p['clase'], $p['descripcion']);
            } catch (Throwable $e) {
                $fallos[] = $p['id'];
                printf(" %s %s | %-24s | %s\n", $this->estado(false), $p['id'], $p['clase'], $p['descripcion']);
                printf("        └─ %s: %s\n", get_class($e), $e->getMessage());
            }
        }

        $total = count($pruebas);
        $milisegundos = (microtime(true) - $inicio) * 1000;

        echo "\n";
        self::linea('=', 78);
        printf(" Total: %d | Aprobadas: %d | Fallidas: %d | Éxito: %.2f %% | Tiempo: %.2f ms\n",
            $total, $aprobadas, count($fallos), $total ? $aprobadas / $total * 100 : 0, $milisegundos);
        if ($fallos) {
            echo " Pruebas fallidas: " . implode(', ', $fallos) . "\n";
        }
        self::linea('=', 78);

        return $fallos ? 1 : 0; // código de salida útil para CI/CD
    }

    private static function linea(string $caracter, int $largo): void
    {
        echo str_repeat($caracter, $largo) . "\n";
    }
}

function assertTrue($condicion, string $mensaje = 'Se esperaba verdadero'): void
{
    if ($condicion !== true) {
        throw new FalloAsercion($mensaje . ' (se obtuvo: ' . var_export($condicion, true) . ')');
    }
}

function assertEquals($esperado, $obtenido, string $mensaje = 'Los valores no coinciden'): void
{
    if ($esperado != $obtenido || gettype($esperado) === 'NULL' && $obtenido !== null) {
        throw new FalloAsercion(sprintf('%s. Esperado: %s | Obtenido: %s', $mensaje, var_export($esperado, true), var_export($obtenido, true)));
    }
}

/** Verifica que el código lance exactamente la excepción indicada. */
function assertThrows(string $claseExcepcion, callable $codigo): void
{
    try {
        $codigo();
    } catch (Throwable $e) {
        if (get_class($e) !== $claseExcepcion) {
            throw new FalloAsercion("Se esperaba {$claseExcepcion} pero se lanzó " . get_class($e) . ': ' . $e->getMessage());
        }
        return;
    }
    throw new FalloAsercion("Se esperaba {$claseExcepcion} pero no se lanzó ninguna excepción");
}

/* ==========================================================================
 *  2. ENTORNO AISLADO (base SQLite en memoria + dobles de prueba)
 * ========================================================================== */

/** Crea una base SQLite en RAM con el mismo esquema de Vortex y datos mínimos. */
function nuevaBaseDePruebas(): PDO
{
    $db = new PDO('sqlite::memory:', null, null, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    $db->exec("
        CREATE TABLE usuarios (id INTEGER PRIMARY KEY AUTOINCREMENT, nombre TEXT NOT NULL, email TEXT NOT NULL UNIQUE,
            password TEXT NOT NULL, rol TEXT NOT NULL DEFAULT 'vendedor', created_at TEXT DEFAULT CURRENT_TIMESTAMP);
        CREATE TABLE categorias (id INTEGER PRIMARY KEY AUTOINCREMENT, nombre TEXT NOT NULL UNIQUE,
            color TEXT NOT NULL DEFAULT '#7C5CFF', created_at TEXT DEFAULT CURRENT_TIMESTAMP);
        CREATE TABLE productos (id INTEGER PRIMARY KEY AUTOINCREMENT, codigo TEXT NOT NULL UNIQUE, nombre TEXT NOT NULL,
            descripcion TEXT NULL, categoria_id INTEGER NULL, precio REAL NOT NULL DEFAULT 0,
            stock INTEGER NOT NULL DEFAULT 0 CHECK (stock >= 0), stock_minimo INTEGER NOT NULL DEFAULT 5,
            activo INTEGER NOT NULL DEFAULT 1, created_at TEXT DEFAULT CURRENT_TIMESTAMP, updated_at TEXT DEFAULT CURRENT_TIMESTAMP);
        CREATE TABLE clientes (id INTEGER PRIMARY KEY AUTOINCREMENT, documento TEXT NOT NULL UNIQUE, nombre TEXT NOT NULL,
            email TEXT NULL, telefono TEXT NULL, direccion TEXT NULL, created_at TEXT DEFAULT CURRENT_TIMESTAMP);
        CREATE TABLE ventas (id INTEGER PRIMARY KEY AUTOINCREMENT, codigo TEXT NULL UNIQUE, cliente_id INTEGER NULL,
            usuario_id INTEGER NOT NULL, subtotal REAL NOT NULL DEFAULT 0, impuesto REAL NOT NULL DEFAULT 0,
            total REAL NOT NULL DEFAULT 0, metodo_pago TEXT NOT NULL DEFAULT 'efectivo', tasa_usd REAL NULL,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP);
        CREATE TABLE detalle_ventas (id INTEGER PRIMARY KEY AUTOINCREMENT, venta_id INTEGER NOT NULL,
            producto_id INTEGER NOT NULL, cantidad INTEGER NOT NULL, precio_unitario REAL NOT NULL, importe REAL NOT NULL);

        INSERT INTO usuarios (nombre, email, password, rol) VALUES ('Vendedor Prueba', 'vendedor@vortex.com', 'x', 'vendedor');
        INSERT INTO categorias (nombre, color) VALUES ('Periféricos', '#22D3EE');
        INSERT INTO clientes (id, documento, nombre) VALUES (1, '222222222222', 'Consumidor final');
        INSERT INTO clientes (id, documento, nombre) VALUES (2, '1110456789', 'Laura Gómez');
        INSERT INTO productos (codigo, nombre, categoria_id, precio, stock, stock_minimo) VALUES
            ('VTX-T-001', 'Mouse inalámbrico', 1, 50000, 10, 3),
            ('VTX-T-002', 'Teclado mecánico',  1, 100000, 5, 5),
            ('VTX-T-003', 'Cable HDMI',        1, 20000, 2, 1);
    ");

    return $db;
}

/** Doble de prueba: simula la API externa sin usar internet. */
final class HttpClientFalso extends HttpClient
{
    public function __construct(private ?array $respuesta)
    {
        parent::__construct(1);
    }

    public function getJson(string $url): ?array
    {
        return $this->respuesta;
    }
}

/** Ruta temporal de caché para que las pruebas no toquen storage/cache. */
function cacheTemporal(): string
{
    $ruta = sys_get_temp_dir() . '/vortex_tasa_' . uniqid() . '.json';
    register_shutdown_function(fn () => @unlink($ruta));
    return $ruta;
}

const USUARIO_ID = 1;
const CLIENTE_ID = 2;

/* ==========================================================================
 *  3. CASOS DE PRUEBA BASE (CP-01 a CP-18)
 * ========================================================================== */

$suite = new SuitePruebas();

/* ---------- Usuario ---------- */

$suite->agregar('CP-01', 'Usuario::registrar()', 'Registro válido guarda la contraseña cifrada', function () {
    // Arrange
    $db = nuevaBaseDePruebas();
    $usuarios = new Usuario($db);
    // Act
    $id = $usuarios->registrar('Ana Pérez', 'ana@vortex.com', 'clave123');
    $guardado = $usuarios->buscar($id);
    // Assert
    assertTrue($id > 0, 'Debe retornar el id del nuevo usuario');
    assertTrue($guardado['password'] !== 'clave123', 'La contraseña no debe quedar en texto plano');
    assertTrue(password_verify('clave123', $guardado['password']), 'El hash debe corresponder a la clave');
});

$suite->agregar('CP-02', 'Usuario::registrar()', 'Control de correo duplicado', function () {
    // Arrange
    $usuarios = new Usuario(nuevaBaseDePruebas());
    $usuarios->registrar('Ana Pérez', 'ana@vortex.com', 'clave123');
    // Act + Assert (mismo correo en mayúsculas: se normaliza y se detecta)
    assertThrows(RuntimeException::class, fn () => $usuarios->registrar('Ana Otra', 'ANA@vortex.com', 'clave456'));
});

$suite->agregar('CP-03', 'Usuario::registrar()', 'Longitud mínima del nombre (< 3)', function () {
    $usuarios = new Usuario(nuevaBaseDePruebas());
    assertThrows(InvalidArgumentException::class, fn () => $usuarios->registrar('Al', 'al@vortex.com', 'clave123'));
});

$suite->agregar('CP-04', 'Usuario::registrar()', 'Correo con formato inválido', function () {
    $usuarios = new Usuario(nuevaBaseDePruebas());
    assertThrows(InvalidArgumentException::class, fn () => $usuarios->registrar('Ana Pérez', 'ana-arroba-vortex', 'clave123'));
});

$suite->agregar('CP-05', 'Usuario::registrar()', 'Longitud mínima de contraseña (< 6)', function () {
    $usuarios = new Usuario(nuevaBaseDePruebas());
    assertThrows(InvalidArgumentException::class, fn () => $usuarios->registrar('Ana Pérez', 'ana@vortex.com', '1234'));
});

$suite->agregar('CP-06', 'Usuario::autenticar()', 'Autenticación exitosa', function () {
    // Arrange
    $usuarios = new Usuario(nuevaBaseDePruebas());
    $usuarios->registrar('Ana Pérez', 'ana@vortex.com', 'clave123');
    // Act
    $resultado = $usuarios->autenticar('ana@vortex.com', 'clave123');
    // Assert
    assertEquals('ana@vortex.com', $resultado['email'] ?? null, 'Debe retornar el usuario autenticado');
});

$suite->agregar('CP-07', 'Usuario::autenticar()', 'Autenticación con clave errónea', function () {
    $usuarios = new Usuario(nuevaBaseDePruebas());
    $usuarios->registrar('Ana Pérez', 'ana@vortex.com', 'clave123');
    assertEquals(null, $usuarios->autenticar('ana@vortex.com', 'incorrecta'), 'No debe autenticar');
});

/* ---------- Producto ---------- */

$suite->agregar('CP-08', 'Producto::crear()', 'Registro válido de producto', function () {
    // Arrange
    $productos = new Producto(nuevaBaseDePruebas());
    // Act
    $id = $productos->crear(['codigo' => 'VTX-T-099', 'nombre' => 'Audífonos', 'precio' => 229000, 'stock' => 4, 'stock_minimo' => 2]);
    // Assert
    assertEquals('Audífonos', $productos->buscar($id)['nombre'], 'Debe guardarse el producto');
});

$suite->agregar('CP-09', 'Producto::existe()', 'Detección de código duplicado', function () {
    $productos = new Producto(nuevaBaseDePruebas());
    assertTrue($productos->existe('codigo', 'VTX-T-001'), 'Otro producto ya usa el código');
    assertTrue(!$productos->existe('codigo', 'VTX-T-001', 1), 'Al editar el mismo producto no es duplicado');
});

$suite->agregar('CP-10', 'Producto::existe()', 'Campo fuera de la lista blanca', function () {
    // Un nombre de columna malicioso no debe llegar al SQL
    $productos = new Producto(nuevaBaseDePruebas());
    assertThrows(InvalidArgumentException::class, fn () => $productos->existe('1=1; DROP TABLE productos; --', 'x'));
});

$suite->agregar('CP-11', 'Producto::eliminar()', 'Borrado lógico conserva el historial', function () {
    // Arrange
    $productos = new Producto(nuevaBaseDePruebas());
    // Act
    $productos->eliminar(1);
    $codigosVisibles = array_column($productos->listar(), 'codigo');
    // Assert
    assertEquals(0, (int) $productos->buscar(1)['activo'], 'Debe quedar inactivo');
    assertTrue(!in_array('VTX-T-001', $codigosVisibles, true), 'No debe aparecer en el listado');
});

$suite->agregar('CP-12', 'Producto::stockBajo()', 'Alerta cuando stock <= stock mínimo', function () {
    $codigos = array_column((new Producto(nuevaBaseDePruebas()))->stockBajo(), 'codigo');
    assertEquals(['VTX-T-002'], $codigos, 'Solo el teclado (5/5) está en el mínimo');
});

/* ---------- Venta ---------- */

$suite->agregar('CP-13', 'Venta::registrar()', 'Totales e IVA calculados con precios de la BD', function () {
    // Arrange
    $ventas = new Venta(nuevaBaseDePruebas());
    $items = [['id' => 1, 'cantidad' => 2, 'precio' => 1], ['id' => 3, 'cantidad' => 1]]; // 'precio' falso del navegador: se ignora
    // Act
    $id = $ventas->registrar($items, CLIENTE_ID, USUARIO_ID, 'efectivo', 0.00025);
    $venta = $ventas->conDetalle($id);
    // Assert: 2 x 50.000 + 1 x 20.000 = 120.000; IVA 19 % = 22.800
    assertEquals(120000.0, (float) $venta['subtotal'], 'Subtotal');
    assertEquals(22800.0, (float) $venta['impuesto'], 'IVA');
    assertEquals(142800.0, (float) $venta['total'], 'Total');
    assertEquals('VTX-000001', $venta['codigo'], 'Código consecutivo');
    assertEquals(2, count($venta['items']), 'Líneas del detalle');
});

$suite->agregar('CP-14', 'Venta::registrar()', 'Descuento de inventario tras la venta', function () {
    $db = nuevaBaseDePruebas();
    (new Venta($db))->registrar([['id' => 1, 'cantidad' => 3]], CLIENTE_ID, USUARIO_ID, 'tarjeta', 0.00025);
    assertEquals(7, (int) (new Producto($db))->buscar(1)['stock'], 'El stock debe pasar de 10 a 7');
});

$suite->agregar('CP-15', 'Venta::registrar()', 'Stock insuficiente revierte la transacción', function () {
    // Arrange
    $db = nuevaBaseDePruebas();
    $ventas = new Venta($db);
    $items = [['id' => 1, 'cantidad' => 1], ['id' => 3, 'cantidad' => 50]]; // la segunda línea falla
    // Act + Assert
    assertThrows(RuntimeException::class, fn () => $ventas->registrar($items, CLIENTE_ID, USUARIO_ID, 'efectivo', 0.00025));
    assertEquals(0, $ventas->contar(), 'No debe quedar ninguna venta a medias');
    assertEquals(10, (int) (new Producto($db))->buscar(1)['stock'], 'El stock de la primera línea no debe cambiar');
});

$suite->agregar('CP-16', 'Venta::registrar()', 'Método de pago no permitido', function () {
    $ventas = new Venta(nuevaBaseDePruebas());
    assertThrows(InvalidArgumentException::class, fn () => $ventas->registrar([['id' => 1, 'cantidad' => 1]], CLIENTE_ID, USUARIO_ID, 'bitcoin', 0.00025));
});

/* ---------- Cliente ---------- */

$suite->agregar('CP-17', 'Cliente::eliminar()', 'Protección del cliente "Consumidor final"', function () {
    $clientes = new Cliente(nuevaBaseDePruebas());
    assertThrows(RuntimeException::class, fn () => $clientes->eliminar(Cliente::CONSUMIDOR_FINAL));
});

/* ---------- Servicio de API externa ---------- */

$suite->agregar('CP-18', 'TasaCambioService', 'API caída usa la tasa de respaldo', function () {
    // Arrange: la API "no responde" (null)
    $servicio = new TasaCambioService(new HttpClientFalso(null), cacheTemporal());
    // Act
    $tasa = $servicio->obtener();
    // Assert
    assertEquals('respaldo', $tasa['fuente'], 'Debe indicar que usó el respaldo');
    assertEquals((float) Config::get('api.tasa_respaldo'), $tasa['tasa'], 'Debe usar la tasa configurada');
});

/* ==========================================================================
 *  4. PARTE B · NUEVOS CASOS BORDE (CP-19 a CP-23)
 * ========================================================================== */

$suite->agregar('CP-19', 'Usuario::registrar()', 'Nombre con solo espacios en blanco', function () {
    // Arrange
    $usuarios = new Usuario(nuevaBaseDePruebas());
    // Act + Assert: trim('   ') queda vacío y no cumple la longitud mínima
    assertThrows(InvalidArgumentException::class, fn () => $usuarios->registrar('   ', 'nuevo@vortex.com', 'clave123'));
}, false);

$suite->agregar('CP-20', 'Venta::registrar()', 'Frontera inferior válida: cantidad = 1', function () {
    // Arrange
    $ventas = new Venta(nuevaBaseDePruebas());
    // Act
    $id = $ventas->registrar([['id' => 2, 'cantidad' => 1]], CLIENTE_ID, USUARIO_ID, 'nequi', 0.00025);
    // Assert
    assertTrue($id > 0, 'La cantidad mínima permitida debe registrarse');
}, false);

$suite->agregar('CP-21', 'Venta::registrar()', 'Frontera superior válida: cantidad = stock', function () {
    // Arrange: el cable tiene exactamente 2 unidades
    $db = nuevaBaseDePruebas();
    $ventas = new Venta($db);
    // Act
    $id = $ventas->registrar([['id' => 3, 'cantidad' => 2]], CLIENTE_ID, USUARIO_ID, 'efectivo', 0.00025);
    // Assert
    assertTrue($id > 0, 'Vender todo el stock disponible debe ser válido');
    assertEquals(0, (int) (new Producto($db))->buscar(3)['stock'], 'El stock debe quedar exactamente en 0');
}, false);

$suite->agregar('CP-22', 'Venta::registrar()', 'Cantidad no numérica', function () {
    // Arrange
    $ventas = new Venta(nuevaBaseDePruebas());
    // Act + Assert: 'abc' no es una cantidad válida, el carrito queda vacío
    assertThrows(InvalidArgumentException::class, fn () => $ventas->registrar([['id' => 1, 'cantidad' => 'abc']], CLIENTE_ID, USUARIO_ID, 'efectivo', 0.00025));
}, false);

$suite->agregar('CP-23', 'Venta::conDetalle()', 'Búsqueda de ID inexistente', function () {
    // Arrange
    $ventas = new Venta(nuevaBaseDePruebas());
    // Act
    $resultado = $ventas->conDetalle(9999);
    // Assert: retorna null sin errores fatales
    assertEquals(null, $resultado, 'Debe retornar null de forma segura');
}, false);

/* ==========================================================================
 *  5. EJECUCIÓN
 * ========================================================================== */

exit($suite->ejecutar(in_array('--base', $argv, true)));
