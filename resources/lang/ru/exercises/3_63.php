<?php

return [
    'title' => "Эффективность процедуры sqrt-stream",
    'description' => [
        '1' =>
        "Хьюго Дум спрашивает, почему нельзя было написать sqrt-stream более простым способом, без внутренней переменной guesses:",
        '2' =>
        "Лиза П. Хакер отвечает, что эта версия процедуры значительно менее эффективна, поскольку производит избыточные вычисления. Объясните Лизин ответ. " .
        "Сохранилось бы отличие в эффективности, если бы реализация delay использовала только (lambda () <выражение>), без оптимизации через memo-proc (см. раздел 3.5.1)?",
    ],
];
