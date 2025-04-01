<?php
function generateRoundRobinRounds($players)
{
    $n = count($players);
    $rounds = [];
    $bye = null;

    if ($n % 2 != 0) {
        $players[] = $bye;
        $n++;
    }

    // Inicializar estructuras de control
    $colorBalance = array_fill_keys($players, 0); // +1 si blancas, -1 si negras
    $nextEvenColor = array_fill_keys($players, 'white');

    for ($round = 0; $round < $n - 1; $round++) {
        $isEvenRound = ($round % 2) == 0;
        $matches = [];
        $tempAssignments = [];

        for ($i = 0; $i < $n / 2; $i++) {
            $p1 = $players[$i];
            $p2 = $players[$n - 1 - $i];

            if ($p1 === $bye || $p2 === $bye) continue;

            if ($isEvenRound) {
                // Asignación determinista para equilibrio perfecto
                $color1 = $nextEvenColor[$p1];
                $color2 = ($color1 === 'white') ? 'black' : 'white';

                $tempAssignments[$p1] = $color1;
                $tempAssignments[$p2] = $color2;

                $matches[] = [
                    'blancas' => $color1 === 'white' ? $p1 : $p2,
                    'negras' => $color1 === 'white' ? $p2 : $p1
                ];
            } else {
                // Balance dinámico para diferencia máxima 1
                $diff = $colorBalance[$p1] - $colorBalance[$p2];

                if ($diff <= 0) {
                    $matches[] = ['blancas' => $p1, 'negras' => $p2];
                } else {
                    $matches[] = ['blancas' => $p2, 'negras' => $p1];
                }
            }
        }

        // Actualizar balances y colores para la próxima ronda par
        if ($isEvenRound) {
            foreach ($tempAssignments as $player => $color) {
                $colorBalance[$player] += ($color === 'white') ? 1 : -1;
                $nextEvenColor[$player] = ($color === 'white') ? 'black' : 'white';
            }
        } else {
            foreach ($matches as $match) {
                $colorBalance[$match['blancas']]++;
                $colorBalance[$match['negras']]--;
            }
        }

        $rounds[] = $matches;

        // Rotar jugadores
        $last = array_pop($players);
        array_splice($players, 1, 0, $last);
    }

    return $rounds;
}
