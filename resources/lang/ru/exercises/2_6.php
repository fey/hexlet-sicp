<?php

return [
    'title' => 'Числа Чёрча',
    'description' => [
        '1' =>
        "Если представление пар как процедур было для Вас еще недостаточно сумасшедшим, то заметьте, что в языке, который способен манипулировать процедурами, мы можем обойтись и без чисел (по крайней мере, пока речь идет о неотрицательных числах), определив 0 и операцию прибавления 1 так:",
        '2' =>
        "Такое представление известно как числа Чёрча (Church numerals), по имени его изобретателя, Алонсо Чёрча, того самого логика, который придумал λ-исчисление.",
        '3' =>
        "Определите one (единицу) и two (двойку) напрямую (не через zero и add-1). " .
        "(Подсказка: вычислите (add-1 zero) с помощью подстановки.) " .
        "Дайте прямое определение процедуры сложения + (не в терминах повторяющегося применения add-1).",
    ],
];