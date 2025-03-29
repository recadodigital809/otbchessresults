<?php
function generateRoundRobinRounds($players)
{
    $n = count($players);
    $rounds = [];
    $bye = 'bye'; // Usamos 'bye' como marcador para indicar que un jugador no juega

    // Si el número de jugadores es impar, agrega un jugador "bye"
    if ($n % 2 != 0) {
        $players[] = $bye; // Añadimos un "bye"
        $n++; // Ahora el número de jugadores es par
    }

    // Inicializar estructuras de control
    $colorBalance = array_fill_keys($players, 0); // +1 si blancas, -1 si negras
    $nextEvenColor = array_fill_keys($players, 'white'); // Asignación inicial de color

    for ($round = 0; $round < $n - 1; $round++) {
        $isEvenRound = ($round % 2) == 0;
        $matches = [];
        $tempAssignments = [];

        // Emparejamientos de jugadores
        for ($i = 0; $i < $n / 2; $i++) {
            $p1 = $players[$i];
            $p2 = $players[$n - 1 - $i];

            // Si un jugador tiene "bye", no lo emparejamos
            if ($p1 === $bye || $p2 === $bye) {
                // El jugador con "bye" no participa en esta ronda
                if ($p1 === $bye) {
                    $matches[] = ['blancas' => null, 'negras' => $p2]; // El jugador con "bye" no juega
                } else {
                    $matches[] = ['blancas' => $p1, 'negras' => null]; // El jugador con "bye" no juega
                }
                continue;
            }

            if ($isEvenRound) {
                // Asignación de colores en rondas pares
                $color1 = $nextEvenColor[$p1];
                $color2 = ($color1 === 'white') ? 'black' : 'white';

                $tempAssignments[$p1] = $color1;
                $tempAssignments[$p2] = $color2;

                // Emparejar jugadores con sus colores
                $matches[] = [
                    'blancas' => $color1 === 'white' ? $p1 : $p2,
                    'negras' => $color1 === 'white' ? $p2 : $p1
                ];
            } else {
                // En rondas impares, asignación dinámica de colores
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
            // Para rondas impares, actualizar balance de colores
            foreach ($matches as $match) {
                if ($match['blancas'] !== null) {
                    $colorBalance[$match['blancas']]++;
                }
                if ($match['negras'] !== null) {
                    $colorBalance[$match['negras']]--;
                }
            }
        }

        // Agregar los emparejamientos de la ronda al array de rondas
        $rounds[] = $matches;

        // Rotar jugadores (el último jugador pasa a la segunda posición)
        $last = array_pop($players);
        array_splice($players, 1, 0, $last);
    }

    return $rounds;
}
