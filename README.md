php-databag
===========

Обертка над массивами данных, предоставляющая удобный интерфейс для добавления и получения данных.
Реализовано 3 вида оберток:
- Cms\DataBag простая обертка, позволяет получать данные по ключу, добавлять, удалять, проверять на наличие
- Cms\DataBag\ReferenceDataBag обертка, позволяющая получить данные по ссылке
- Cms\DataBag\Expression обертка, которая проверяет хранимые данные на наличие выражений, и исполнении их

Обертка, выполняющая работу с выражениями позволяет получать данные из еще не исполненных выражений, например:

    // исходные данные
    $databag = new Cms\DataBag\Expression([
        'a' => '{{ c|default(0) }}',
        'b' => '{% return ["d" => 2] %}',
    ]);

    var_dump($databag->get('b.d')); // вернет (int) 2
    var_dump($databag->get('c')); // вернет NULL
    var_dump($databag->get('a')); // вернет (int) 0 - так как в массиве данных нет ключа "c", то вернется значение из default
