<?php

namespace App\Models;

use App\Core\Config;
use App\Core\Model;
use InvalidArgumentException;
use PDO;
use RuntimeException;
use Throwable;

class Venta extends Model
{
    public const METODOS_PAGO = [
        'efectivo'      => 'Efectivo',
        'tarjeta'       => 'Tarjeta',
        'transferencia' => 'Transferencia',
        'nequi'         => 'Nequi',
    ];

    protected string $tabla = 'ventas';
    protected array $campos = ['codigo', 'cliente_id', 'usuario_id', 'subtotal', 'impuesto', 'total', 'metodo_pago', 'tasa_usd'];

    /**
     * Registra una venta completa dentro de una transacción:
     * valida stock, calcula totales con precios de la BD (no del navegador),
     * guarda el detalle y descuenta inventario.
     *
     * @param array $items [['id' => int, 'cantidad' => int], ...]
     */
    public function registrar(array $items, int $clienteId, int $usuarioId, string $metodoPago, float $tasaUsd): int
    {
        if (!isset(self::METODOS_PAGO[$metodoPago])) {
            throw new InvalidArgumentException('Método de pago no válido.');
        }

        $this->db->beginTransaction();
        try {
            $lineas   = $this->construirLineas($items);
            $subtotal = array_sum(array_column($lineas, 'importe'));
            $impuesto = round($subtotal * (float) Config::get('app.iva', 0), 2);

            $ventaId = $this->crear([
                'cliente_id'  => $clienteId,
                'usuario_id'  => $usuarioId,
                'subtotal'    => $subtotal,
                'impuesto'    => $impuesto,
                'total'       => $subtotal + $impuesto,
                'metodo_pago' => $metodoPago,
                'tasa_usd'    => $tasaUsd,
            ]);
            $this->actualizar($ventaId, ['codigo' => self::generarCodigo($ventaId)]);

            $detalle = new DetalleVenta($this->db); // misma conexión = misma transacción
            foreach ($lineas as $linea) {
                $detalle->crear(['venta_id' => $ventaId] + $linea);
                $this->ejecutar('UPDATE productos SET stock = stock - ? WHERE id = ?', [$linea['cantidad'], $linea['producto_id']]);
            }

            $this->db->commit();
            return $ventaId;
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public static function generarCodigo(int $id): string
    {
        return 'VTX-' . str_pad((string) $id, 6, '0', STR_PAD_LEFT);
    }

    /** Bloquea cada producto (FOR UPDATE) y arma las líneas de la venta. */
    private function construirLineas(array $items): array
    {
        // El bloqueo de filas solo existe en MySQL/MariaDB (SQLite bloquea toda la base en la transacción)
        $bloqueo = $this->db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql' ? ' FOR UPDATE' : '';

        $lineas = [];
        foreach ($items as $item) {
            $id       = (int) ($item['id'] ?? 0);
            $cantidad = (int) ($item['cantidad'] ?? 0);
            if ($id <= 0 || $cantidad <= 0) {
                continue;
            }

            $producto = $this->consultarUno('SELECT id, nombre, precio, stock FROM productos WHERE id = ? AND activo = 1' . $bloqueo, [$id]);
            if ($producto === null) {
                throw new RuntimeException("El producto #{$id} ya no está disponible.");
            }
            if ((int) $producto['stock'] < $cantidad) {
                throw new RuntimeException("Stock insuficiente para «{$producto['nombre']}» (disponible: {$producto['stock']}).");
            }

            $precio = (float) $producto['precio'];
            $lineas[] = [
                'producto_id'     => $id,
                'cantidad'        => $cantidad,
                'precio_unitario' => $precio,
                'importe'         => $precio * $cantidad,
            ];
        }

        if ($lineas === []) {
            throw new InvalidArgumentException('El carrito está vacío.');
        }
        return $lineas;
    }

    /* ---------- Consultas para listados y reportes ---------- */

    public function listarFiltrado(array $filtros): array
    {
        $sql = 'SELECT v.*, c.nombre AS cliente, u.nombre AS vendedor,
                       (SELECT SUM(cantidad) FROM detalle_ventas d WHERE d.venta_id = v.id) AS unidades
                FROM ventas v
                LEFT JOIN clientes c ON c.id = v.cliente_id
                JOIN usuarios u ON u.id = v.usuario_id
                WHERE 1';
        $parametros = [];

        if (!empty($filtros['q'])) {
            $sql .= ' AND (v.codigo LIKE :q1 OR c.nombre LIKE :q2)';
            $parametros['q1'] = $parametros['q2'] = "%{$filtros['q']}%";
        }
        if (!empty($filtros['desde'])) {
            $sql .= ' AND DATE(v.created_at) >= :desde';
            $parametros['desde'] = $filtros['desde'];
        }
        if (!empty($filtros['hasta'])) {
            $sql .= ' AND DATE(v.created_at) <= :hasta';
            $parametros['hasta'] = $filtros['hasta'];
        }
        if (!empty($filtros['metodo']) && isset(self::METODOS_PAGO[$filtros['metodo']])) {
            $sql .= ' AND v.metodo_pago = :metodo';
            $parametros['metodo'] = $filtros['metodo'];
        }

        return $this->consultar($sql . ' ORDER BY v.created_at DESC', $parametros);
    }

    public function conDetalle(int $id): ?array
    {
        $venta = $this->consultarUno(
            'SELECT v.*, c.nombre AS cliente, c.documento, c.email AS cliente_email,
                    c.telefono AS cliente_telefono, c.direccion AS cliente_direccion,
                    u.nombre AS vendedor
             FROM ventas v
             LEFT JOIN clientes c ON c.id = v.cliente_id
             JOIN usuarios u ON u.id = v.usuario_id
             WHERE v.id = ?',
            [$id]
        );

        if ($venta !== null) {
            $venta['items'] = (new DetalleVenta($this->db))->deVenta($id);
        }
        return $venta;
    }

    public function recientes(int $limite = 5): array
    {
        return $this->consultar(
            'SELECT v.id, v.codigo, v.total, v.metodo_pago, v.created_at, c.nombre AS cliente
             FROM ventas v LEFT JOIN clientes c ON c.id = v.cliente_id
             ORDER BY v.created_at DESC LIMIT ' . (int) $limite
        );
    }

    /** Totales de hoy y del mes en curso. */
    public function resumen(): array
    {
        $fila = $this->consultarUno(
            "SELECT
                COALESCE(SUM(CASE WHEN DATE(created_at) = CURDATE() THEN total END), 0) AS total_hoy,
                COALESCE(SUM(DATE(created_at) = CURDATE()), 0)                           AS ventas_hoy,
                COALESCE(SUM(CASE WHEN created_at >= DATE_FORMAT(CURDATE(), '%Y-%m-01') THEN total END), 0) AS total_mes,
                COALESCE(SUM(created_at >= DATE_FORMAT(CURDATE(), '%Y-%m-01')), 0)                           AS ventas_mes
             FROM ventas"
        );

        $ventasMes = (int) $fila['ventas_mes'];
        return [
            'total_hoy'       => (float) $fila['total_hoy'],
            'ventas_hoy'      => (int) $fila['ventas_hoy'],
            'total_mes'       => (float) $fila['total_mes'],
            'ventas_mes'      => $ventasMes,
            'ticket_promedio' => $ventasMes > 0 ? (float) $fila['total_mes'] / $ventasMes : 0.0,
        ];
    }

    /** Ventas de los últimos N días, incluyendo días sin ventas (en 0). */
    public function porDia(int $dias = 7): array
    {
        $filas = $this->consultar(
            'SELECT DATE(created_at) AS dia, SUM(total) AS total, COUNT(*) AS cantidad
             FROM ventas
             WHERE created_at >= CURDATE() - INTERVAL ? DAY
             GROUP BY DATE(created_at)',
            [$dias - 1]
        );
        $porFecha = array_column($filas, null, 'dia');
        $nombres  = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'];

        $resultado = [];
        for ($i = $dias - 1; $i >= 0; $i--) {
            $marca = strtotime("-{$i} day");
            $dia   = date('Y-m-d', $marca);
            $resultado[] = [
                'dia'      => $dia,
                'etiqueta' => $nombres[(int) date('w', $marca)] . ' ' . date('j', $marca),
                'total'    => (float) ($porFecha[$dia]['total'] ?? 0),
                'cantidad' => (int) ($porFecha[$dia]['cantidad'] ?? 0),
            ];
        }
        return $resultado;
    }

    public function topProductos(int $limite = 5): array
    {
        return $this->consultar(
            'SELECT p.id, p.nombre, p.codigo, SUM(d.cantidad) AS unidades, SUM(d.importe) AS ingresos
             FROM detalle_ventas d
             JOIN productos p ON p.id = d.producto_id
             GROUP BY p.id, p.nombre, p.codigo
             ORDER BY unidades DESC
             LIMIT ' . (int) $limite
        );
    }
}
