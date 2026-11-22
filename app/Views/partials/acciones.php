<?php /** Botones editar / eliminar de una fila. Parámetros: ruta, id, nombre */ ?>
<div class="acciones">
    <a href="<?= url("$ruta/editar", ['id' => $id]) ?>" class="btn-icono btn-icono--sm" title="Editar">
        <i data-lucide="pencil"></i>
    </a>
    <form method="post" action="<?= url("$ruta/eliminar") ?>" data-confirmar="¿Eliminar «<?= e($nombre) ?>»? Esta acción no se puede deshacer.">
        <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
        <input type="hidden" name="id" value="<?= (int) $id ?>">
        <button class="btn-icono btn-icono--sm btn-icono--peligro" title="Eliminar"><i data-lucide="trash-2"></i></button>
    </form>
</div>
