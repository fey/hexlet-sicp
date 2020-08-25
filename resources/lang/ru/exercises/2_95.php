<?php

return [
    'title' => "Псевдоделение",
    'description' => [
        '1' =>
        "Пусть P₁, P₂ и P₃ – многочлены",
        '2' =>
        "Теперь пусть Q1 будет произведение P₁ и P₂, а Q₂ произведение P₁ и P₃. При помощи greatest-common-divisor (упражнение 2.94) вычислите НОД Q₁ и Q₂. " .
        "Обратите внимание, что ответ несовпадает с P₁. Этот пример вводит в вычисление операции с нецелыми числами, и это создает сложности для алгоритма вычисления НОД. " .
        "Чтобы понять, что здесь происходит, попробуйте включить трассировку в gcd-terms при вычислении НОД либо проведите деление вручную.",
        '3' =>
        "Проблему, которую демонстрирует упражнение 2.95, можно решить, если мы используем следующий вариант алгоритма вычисления НОД (который работает только для многочленов с целыми коэффициентами). " .
        "Прежде, чем проводить деление многочленов при вычислении НОД, мы умножаем делимое на целую константу, которая выбирается так, чтобы в процессе деления не возникло никаких дробей. " .
        "Результат вычисления будет отличаться от настоящего НОД на целую константу, но при приведении рациональных функций к наименьшему знаменателю это несущественно; " .
        "будет проведено деление и числителя, и знаменателя на НОД, так что константный множитель сократится.",
        '4' =>
        "Выражаясь более точно, если P и Q — многочлены, определим O₁ как порядок P (то есть порядок его старшего терма), а O₂ как порядок Q. " .
        "Пусть c будет коэффициент старшего терма Q. В таком случае, можно показать, что если мы домножим P на множитель целости (integerizing factor) c^(1 + O₁ − O₂), " .
        "то получившийся многочлен можно будет поделить на Q алгоритмом div-terms, получив результат, в котором не будет никаких дробей. " .
        "Операция домножения делимого на такую константу, а затем деления, иногда называется псевдоделением (pseudodivision) P на Q. " .
        "Остаток такого деления называется псевдоостатком (pseudoremainder).",
    ],
];