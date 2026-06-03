<?php

/**
 * Parte 1 – Función recursiva para calcular el premio acumulado.
 */
function calcularPremioAcumulado(array $niveles): float
{
    $premio_acumulado = array_reduce($niveles, 
        function (float $acumulado, array $item): float {
            return $acumulado
                + (float) $item['monto']
                + calcularPremioAcumulado($item['hijos']); 
        },
        0.0);

    return $premio_acumulado;
};